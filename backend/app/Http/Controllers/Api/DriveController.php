<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Models\UserDriveFile;
use App\Models\UserDriveFileShare;
use App\Support\Org;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                        'name' => (string) $share->owner->name,
                        'email' => (string) $share->owner->email,
                    ] : null,
                    'shared_at' => $share->created_at?->toIso8601String(),
                ];
            })
            ->filter()
            ->values();

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

        $q = trim((string) $request->query('q', ''));

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

        return response()->api($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:51200'], // 50MB
        ]);

        $file = $validated['file'];
        $disk = config('filesystems.default');
        $storedPath = $file->store("drive/{$orgId}/{$user->id}", $disk);

        $record = UserDriveFile::query()->create([
            'tenant_id' => $orgId,
            'owner_user_id' => (int) $user->id,
            'name' => (string) $file->getClientOriginalName(),
            'path' => (string) $storedPath,
            'mime_type' => (string) ($file->getClientMimeType() ?: ''),
            'size_bytes' => (int) $file->getSize(),
            'extension' => (string) ($file->getClientOriginalExtension() ?: ''),
        ]);

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

        $record = UserDriveFile::query()
            ->where('tenant_id', $orgId)
            ->whereKey($id)
            ->firstOrFail();

        if ((int) $record->owner_user_id !== (int) $user->id) {
            return response()->json(['message' => 'Only file owner can delete this document.'], 403);
        }

        Storage::disk(config('filesystems.default'))->delete($record->path);
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

        $downloadUrl = url('/api/v1/drive/files/' . (int) $file->id . '/download');
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

        $disk = config('filesystems.default');
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
            'mime_type' => (string) ($file->mime_type ?? ''),
            'size_bytes' => (int) ($file->size_bytes ?? 0),
            'extension' => (string) ($file->extension ?? ''),
            'download_url' => url('/api/v1/drive/files/' . (int) $file->id . '/download'),
            'created_at' => $file->created_at?->toIso8601String(),
            'updated_at' => $file->updated_at?->toIso8601String(),
            'shares_count' => $includeShares ? (int) ($file->shares_count ?? 0) : null,
        ];
    }
}
