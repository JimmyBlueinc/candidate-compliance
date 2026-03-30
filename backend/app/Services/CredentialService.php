<?php

namespace App\Services;

use App\Events\CredentialRejected;
use App\Events\CredentialUploaded;
use App\Events\CredentialVerified;
use App\Models\CandidateCredential;
use App\Models\CredentialVerification;
use App\Models\User;
use Illuminate\Support\Facades\Event;
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
        $expires = now()->addMinutes($minutes)->timestamp;
        $signature = hash_hmac('sha256', $path . '|' . $expires, (string) config('app.key'));

        return url('/api/credentials/documents/' . $path) . '?expires=' . $expires . '&signature=' . $signature;
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
}
