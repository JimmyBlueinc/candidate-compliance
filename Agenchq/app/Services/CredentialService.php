<?php

namespace App\Services;

use App\Events\CredentialRejected;
use App\Events\CredentialUploaded;
use App\Events\CredentialVerified;
use App\Models\CandidateCredential;
use App\Models\CredentialVerification;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CredentialService
{
    public function uploadCredential(
        int $tenantId,
        int $candidateId,
        int $credentialTypeId,
        $documentPath,
        $issuedAt,
        $expiresAt
    ): CandidateCredential {
        if ($documentPath instanceof \Illuminate\Http\UploadedFile) {
            $storedPath = Storage::disk('credentials')->putFile('tenant_' . $tenantId . '/candidate_' . $candidateId, $documentPath);
            $documentPath = $storedPath;
        }

        $credential = CandidateCredential::create([
            'tenant_id' => $tenantId,
            'candidate_id' => $candidateId,
            'credential_type_id' => $credentialTypeId,
            'document_path' => $documentPath,
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAt,
            'status' => 'pending',
            'verified_at' => null,
            'verified_by' => null,
        ]);

        Event::dispatch(new CredentialUploaded($tenantId, $credential, auth()->user()));

        return $credential;
    }

    public function signedDocumentUrl(CandidateCredential $credential, int $minutes = 60): ?string
    {
        if (!$credential->document_path) {
            return null;
        }

        $path = ltrim((string) $credential->document_path, '/');
        $disk = Storage::disk('credentials');

        // Production path: let S3 own access control via pre-signed object URLs.
        if (Config::get('filesystems.disks.credentials.driver') === 's3') {
            if (!$disk->exists($path)) {
                $this->attemptLegacyDocumentMigrationToS3($path, $disk);
            }

            if (!$disk->exists($path)) {
                return null;
            }

            return $disk->temporaryUrl($path, now()->addMinutes($minutes));
        }

        // Local/dev path (no S3): app-signed route.
        $expires = now()->addMinutes($minutes)->timestamp;
        $signature = hash_hmac('sha256', $path . '|' . $expires, (string) config('app.key'));
        return url('/api/credentials/documents/' . $path) . '?expires=' . $expires . '&signature=' . $signature;
    }

    private function attemptLegacyDocumentMigrationToS3(string $targetPath, $credentialsDisk): void
    {
        $legacyLocalDisk = Storage::build([
            'driver' => 'local',
            'root' => storage_path('app/credentials'),
            'throw' => false,
        ]);

        $uploadsDiskName = (string) config('filesystems.uploads_disk', config('filesystems.default'));
        $uploadsDisk = Storage::disk($uploadsDiskName);

        $candidatePaths = array_values(array_unique([
            $targetPath,
            ltrim(preg_replace('#^credentials/#', '', $targetPath) ?? $targetPath, '/'),
            'credentials/' . ltrim($targetPath, '/'),
        ]));

        foreach ($candidatePaths as $legacyPath) {
            // 1) Legacy local credentials directory.
            if ($legacyLocalDisk->exists($legacyPath)) {
                $stream = $legacyLocalDisk->readStream($legacyPath);
                if ($stream !== false) {
                    $credentialsDisk->put($targetPath, $stream);
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                    if ($credentialsDisk->exists($targetPath)) {
                        return;
                    }
                }
            }

            // 2) Legacy uploads disk (could be local or S3 on older setups).
            if ($uploadsDisk->exists($legacyPath)) {
                $stream = $uploadsDisk->readStream($legacyPath);
                if ($stream !== false) {
                    $credentialsDisk->put($targetPath, $stream);
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                    if ($credentialsDisk->exists($targetPath)) {
                        return;
                    }
                }
            }
        }

        Log::warning('Credential document missing on S3 and not recoverable from legacy locations.', [
            'path' => $targetPath,
            'uploads_disk' => $uploadsDiskName,
        ]);
    }

    public function attachDocument(CandidateCredential $credential, \Illuminate\Http\UploadedFile $file): CandidateCredential
    {
        $tenantId = (int) $credential->tenant_id;
        $candidateId = (int) $credential->candidate_id;

        $storedPath = Storage::disk('credentials')->putFile('tenant_' . $tenantId . '/candidate_' . $candidateId, $file);

        $credential->document_path = $storedPath;
        $credential->status = 'pending';
        $credential->verified_at = null;
        $credential->verified_by = null;
        $credential->save();

        Event::dispatch(new CredentialUploaded($tenantId, $credential, auth()->user()));

        return $credential;
    }

    public function verifyCredential(int $credentialId, int $verifiedBy): CandidateCredential
    {
        $credential = CandidateCredential::query()->findOrFail($credentialId);

        $credential->status = 'verified';
        $credential->verified_at = now();
        $credential->verified_by = $verifiedBy;
        $credential->save();

        CredentialVerification::create([
            'tenant_id' => $credential->tenant_id,
            'credential_id' => $credential->id,
            'verified_by' => $verifiedBy,
            'status' => 'verified',
            'notes' => null,
        ]);

        $actor = User::query()->find($verifiedBy);
        Event::dispatch(new CredentialVerified((int) $credential->tenant_id, $credential, $actor));

        return $credential;
    }

    public function rejectCredential(int $credentialId, int $verifiedBy, ?string $notes): CandidateCredential
    {
        $credential = CandidateCredential::query()->findOrFail($credentialId);

        $credential->status = 'rejected';
        $credential->verified_at = now();
        $credential->verified_by = $verifiedBy;
        $credential->save();

        CredentialVerification::create([
            'tenant_id' => $credential->tenant_id,
            'credential_id' => $credential->id,
            'verified_by' => $verifiedBy,
            'status' => 'rejected',
            'notes' => $notes,
        ]);

        $actor = User::query()->find($verifiedBy);
        Event::dispatch(new CredentialRejected((int) $credential->tenant_id, $credential, $actor, $notes));

        return $credential;
    }

    public function markCredentialNeedsCorrection(int $credentialId, int $verifiedBy, ?string $notes): CandidateCredential
    {
        $credential = CandidateCredential::query()->findOrFail($credentialId);

        $credential->status = 'needs_correction';
        $credential->verified_at = now();
        $credential->verified_by = $verifiedBy;
        $credential->save();

        CredentialVerification::create([
            'tenant_id' => $credential->tenant_id,
            'credential_id' => $credential->id,
            'verified_by' => $verifiedBy,
            'status' => 'needs_correction',
            'notes' => $notes,
        ]);

        return $credential;
    }
}
