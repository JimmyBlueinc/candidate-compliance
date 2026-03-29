<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Models\UserDriveFile;
use App\Models\UserDriveFileShare;
use App\Support\Org;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DriveController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        if ($guard = $this->ensureDriveReady()) {
            return $guard;
        }

        try {
            $owned = UserDriveFile::query()
                ->where('tenant_id', $orgId)
                ->where('owner_user_id', $user->id)
                ->withCount('shares')
                ->orderByDesc('id')
                ->limit(500)
                ->get()
                ->map(fn (UserDriveFile $f) => $this->serializeFile($f, true))
                ->values();

            $shared = UserDriveFileShare::query()
                ->where('tenant_id', $orgId)
                ->where('recipient_user_id', $user->id)
                ->with(['file', 'owner:id,name,email'])
                ->orderByDesc('id')
                ->limit(500)
                ->get()
                ->map(function (UserDriveFileShare $share) {
                    $file = $share->file;
                    if (!$file) {
                        return null;
                    }

                    return [
                        ...$this->serializeFile($file, false),
                        'shared_by' => $share->owner ? [
                            'id' => (int) $share->owner->id,
                            'name' => (string) ($share->owner->name ?: 'Deleted User'),
                            'email' => (string) ($share->owner->email ?: ''),
                        ] : null,
                        'shared_at' => $share->created_at?->toIso8601String(),
                    ];
                })
                ->filter()
                ->values();
        } catch (QueryException $e) {
            Log::error('Drive index query failed', [
                'user_id' => $user->id,
                'organization_id' => $orgId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Drive is temporarily unavailable. Please try again shortly.'], 503);
        }

        return response()->api([
            'owned_files' => $owned,
            'shared_with_me' => $shared,
        ]);
    }

    public function recipients(Request $request): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        if ($guard = $this->ensureDriveReady()) {
            return $guard;
        }

        $q = trim((string) $request->query('q', ''));

        try {
            $rows = User::query()
                ->where('organization_id', $orgId)
                ->whereKeyNot($user->id)
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($sub) use ($q) {
                        $sub->where('name', 'like', '%' . $q . '%')
                            ->orWhere('email', 'like', '%' . $q . '%');
                    });
                })
                ->orderBy('name')
                ->limit(100)
                ->get(['id', 'name', 'email', 'role'])
                ->map(fn (User $u) => [
                    'id' => (int) $u->id,
                    'name' => (string) ($u->name ?: 'Deleted User'),
                    'email' => (string) $u->email,
                    'role' => (string) ($u->role ?: 'user'),
                ])
                ->values();
        } catch (QueryException $e) {
            Log::error('Drive recipients query failed', [
                'user_id' => $user->id,
                'organization_id' => $orgId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Drive is temporarily unavailable. Please try again shortly.'], 503);
        }

        return response()->api($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        if ($guard = $this->ensureDriveReady()) {
            return $guard;
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:102400'], // 100MB
        ]);

        $file = $validated['file'];
        $candidateDisks = $this->resolveDriveDiskCandidates();
        $primaryDisk = $candidateDisks[0] ?? $this->resolveDriveDisk();
        $record = null;
        $lastError = null;

        foreach ($candidateDisks as $disk) {
            try {
                $storedPath = $file->store("drive/{$orgId}/{$user->id}", $disk);

                $record = UserDriveFile::query()->create([
                    'tenant_id' => $orgId,
                    'owner_user_id' => (int) $user->id,
                    'name' => (string) $file->getClientOriginalName(),
                    'path' => (string) $storedPath,
                    'storage_disk' => (string) $disk,
                    'mime_type' => (string) ($file->getClientMimeType() ?: ''),
                    'size_bytes' => (int) $file->getSize(),
                    'extension' => (string) ($file->getClientOriginalExtension() ?: ''),
                ]);

                break;
            } catch (\Throwable $e) {
                $lastError = $e;
                Log::warning('Drive upload attempt failed', [
                    'organization_id' => $orgId,
                    'user_id' => $user->id,
                    'disk' => $disk,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!$record) {
            Log::error('Drive upload failed on all disks', [
                'organization_id' => $orgId,
                'user_id' => $user->id,
                'primary_disk' => $primaryDisk,
                'last_error' => $lastError?->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to upload file. Storage is unavailable. Please contact support if this continues.',
            ], 422);
        }

        return response()->api([
            'file' => $this->serializeFile($record, true),
        ], 201, [], 'File uploaded successfully.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        if ($guard = $this->ensureDriveReady()) {
            return $guard;
        }

        $record = UserDriveFile::query()
            ->where('tenant_id', $orgId)
            ->whereKey($id)
            ->firstOrFail();

        if ((int) $record->owner_user_id !== (int) $user->id) {
            return response()->json(['message' => 'Only file owner can delete this document.'], 403);
        }

        $disk = $this->resolveFileDisk($record);
        Storage::disk($disk)->delete($record->path);
        $record->delete();

        return response()->api(['deleted' => true], 200, [], 'File deleted successfully.');
    }

    public function share(Request $request, int $id): JsonResponse
    {
        $sender = $request->user();
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        if ($guard = $this->ensureDriveReady()) {
            return $guard;
        }

        $validated = $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $file = UserDriveFile::query()
            ->where('tenant_id', $orgId)
            ->whereKey($id)
            ->firstOrFail();

        if ((int) $file->owner_user_id !== (int) $sender->id) {
            return response()->json(['message' => 'Only file owner can share this document.'], 403);
        }

        $recipient = User::query()->findOrFail((int) $validated['recipient_id']);
        if ((int) $recipient->organization_id !== (int) $orgId) {
            return response()->json(['message' => 'Recipient must belong to your organization.'], 403);
        }
        if ((int) $recipient->id === (int) $sender->id) {
            return response()->json(['message' => 'You cannot share file to yourself.'], 422);
        }

        $downloadUrl = url('/api/drive/files/' . (int) $file->id . '/download');
        $note = trim((string) ($validated['note'] ?? ''));
        $body = "Shared a drive file: {$file->name}\nOpen: {$downloadUrl}";
        if ($note !== '') {
            $body .= "\nNote: {$note}";
        }

        try {
            $share = null;
            DB::transaction(function () use ($orgId, $file, $sender, $recipient, $body, &$share): void {
                $message = Message::query()->create([
                    'tenant_id' => $orgId,
                    'user_id' => (int) $sender->id,
                    'recipient_id' => (int) $recipient->id,
                    'body' => $body,
                    'created_at' => now(),
                ]);

                $share = UserDriveFileShare::query()->firstOrCreate(
                    [
                        'tenant_id' => $orgId,
                        'file_id' => (int) $file->id,
                        'recipient_user_id' => (int) $recipient->id,
                    ],
                    [
                        'owner_user_id' => (int) $sender->id,
                        'message_id' => (int) $message->id,
                    ]
                );

                if (!$share->message_id) {
                    $share->message_id = (int) $message->id;
                    $share->save();
                }
            });
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Failed to share this file.',
            ], 422);
        }

        return response()->api([
            'shared' => true,
            'share_id' => (int) $share->id,
            'download_url' => $downloadUrl,
        ], 201, [], 'File shared successfully.');
    }

    public function download(Request $request, int $id): StreamedResponse|JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        if ($guard = $this->ensureDriveReady()) {
            return $guard;
        }

        $file = UserDriveFile::query()
            ->where('tenant_id', $orgId)
            ->whereKey($id)
            ->firstOrFail();

        $isOwner = (int) $file->owner_user_id === (int) $user->id;
        $isRecipient = UserDriveFileShare::query()
            ->where('tenant_id', $orgId)
            ->where('file_id', $file->id)
            ->where('recipient_user_id', $user->id)
            ->exists();

        if (!$isOwner && !$isRecipient) {
            return response()->json(['message' => 'Unauthorized access to file.'], 403);
        }

        $disk = $this->resolveFileDisk($file);
        if (!Storage::disk($disk)->exists($file->path)) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        return Storage::disk($disk)->download($file->path, $file->name);
    }

    private function serializeFile(UserDriveFile $file, bool $includeShares): array
    {
        return [
            'id' => (int) $file->id,
            'name' => (string) $file->name,
            'storage_disk' => (string) ($file->storage_disk ?? ''),
            'mime_type' => (string) ($file->mime_type ?? ''),
            'size_bytes' => (int) ($file->size_bytes ?? 0),
            'extension' => (string) ($file->extension ?? ''),
            'download_url' => url('/api/drive/files/' . (int) $file->id . '/download'),
            'created_at' => $file->created_at?->toIso8601String(),
            'updated_at' => $file->updated_at?->toIso8601String(),
            'shares_count' => $includeShares ? (int) ($file->shares_count ?? 0) : null,
        ];
    }

    private function ensureDriveReady(): ?JsonResponse
    {
        $hasFilesTable = Schema::hasTable('user_drive_files');
        $hasSharesTable = Schema::hasTable('user_drive_file_shares');
        $hasStorageDiskColumn = $hasFilesTable && Schema::hasColumn('user_drive_files', 'storage_disk');

        if ($hasFilesTable && $hasSharesTable && $hasStorageDiskColumn) {
            return null;
        }

        // Self-heal path for environments where deploy boot missed migrations.
        $this->attemptDriveSchemaRecovery();

        $hasFilesTable = Schema::hasTable('user_drive_files');
        $hasSharesTable = Schema::hasTable('user_drive_file_shares');
        $hasStorageDiskColumn = $hasFilesTable && Schema::hasColumn('user_drive_files', 'storage_disk');
        if ($hasFilesTable && $hasSharesTable && $hasStorageDiskColumn) {
            return null;
        }

        return response()->json([
            'message' => 'Drive tables are not ready yet. Please run latest migrations.',
        ], 503);
    }

    private function attemptDriveSchemaRecovery(): void
    {
        try {
            Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]);
        } catch (\Throwable $e) {
            Log::warning('Drive recovery migrate attempt failed', [
                'error' => $e->getMessage(),
            ]);
        }

        try {
            if (!Schema::hasTable('user_drive_files')) {
                Schema::create('user_drive_files', function (Blueprint $table): void {
                    $table->id();
                    $table->foreignId('tenant_id')->constrained('organizations')->cascadeOnDelete();
                    $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
                    $table->string('name');
                    $table->string('path');
                    $table->string('storage_disk', 80)->nullable();
                    $table->string('mime_type', 191)->nullable();
                    $table->unsignedBigInteger('size_bytes')->default(0);
                    $table->string('extension', 32)->nullable();
                    $table->timestamps();
                    $table->index(['tenant_id', 'owner_user_id'], 'user_drive_files_tenant_owner_idx');
                });
            }

            if (!Schema::hasTable('user_drive_file_shares')) {
                Schema::create('user_drive_file_shares', function (Blueprint $table): void {
                    $table->id();
                    $table->foreignId('tenant_id')->constrained('organizations')->cascadeOnDelete();
                    $table->foreignId('file_id')->constrained('user_drive_files')->cascadeOnDelete();
                    $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
                    $table->foreignId('recipient_user_id')->constrained('users')->cascadeOnDelete();
                    $table->foreignId('message_id')->nullable()->constrained('messages')->nullOnDelete();
                    $table->timestamps();
                    $table->unique(['file_id', 'recipient_user_id'], 'user_drive_file_shares_file_recipient_unique');
                    $table->index(['tenant_id', 'recipient_user_id'], 'user_drive_file_shares_tenant_recipient_idx');
                });
            }

            if (Schema::hasTable('user_drive_files') && !Schema::hasColumn('user_drive_files', 'storage_disk')) {
                Schema::table('user_drive_files', function (Blueprint $table): void {
                    $table->string('storage_disk', 80)->nullable()->after('path');
                });
            }
        } catch (\Throwable $e) {
            Log::error('Drive schema recovery failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolveDriveDisk(): string
    {
        $preferred = (string) (config('filesystems.drive_disk') ?: config('filesystems.default') ?: 'local');
        if ($this->isDiskUsable($preferred)) {
            return $preferred;
        }

        // Mirror logo-branding reliability: prefer S3 disks before local fallback.
        foreach (['private_assets', 'public_assets', 'local'] as $fallback) {
            if ($this->isDiskUsable($fallback)) {
                return $fallback;
            }
        }

        return 'local';
    }

    private function resolveDriveDiskCandidates(): array
    {
        $preferred = $this->resolveDriveDisk();
        $candidates = [$preferred, 'private_assets', 'public_assets', 'local'];

        return array_values(array_filter(array_unique($candidates), fn ($disk) => $this->isDiskUsable((string) $disk)));
    }

    private function isDiskUsable(string $disk): bool
    {
        $disks = (array) config('filesystems.disks', []);
        if (!array_key_exists($disk, $disks)) {
            return false;
        }

        $driver = (string) ($disks[$disk]['driver'] ?? '');
        if ($driver !== 's3') {
            return true;
        }

        $bucket = trim((string) ($disks[$disk]['bucket'] ?? ''));
        return $bucket !== '';
    }

    private function resolveFileDisk(UserDriveFile $file): string
    {
        $savedDisk = trim((string) ($file->storage_disk ?? ''));
        $disks = (array) config('filesystems.disks', []);
        if ($savedDisk !== '' && array_key_exists($savedDisk, $disks)) {
            return $savedDisk;
        }

        return $this->resolveDriveDisk();
    }
}
