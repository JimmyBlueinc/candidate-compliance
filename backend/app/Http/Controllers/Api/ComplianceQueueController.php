<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CandidateCredential;
use App\Models\Notification;
use App\Models\User;
use App\Services\CredentialService;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplianceQueueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $validated = $request->validate([
            'status' => ['sometimes', 'string', 'in:all,pending,verified,rejected,expired'],
            'candidate' => ['sometimes', 'string', 'max:255'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:1000'],
        ]);

        $statusFilter = (string) ($validated['status'] ?? 'pending');
        $candidateSearch = trim((string) ($validated['candidate'] ?? ''));
        $limit = (int) ($validated['limit'] ?? 500);

        $rows = CandidateCredential::query()
            ->where('tenant_id', $orgId)
            ->when($statusFilter !== 'all', fn ($q) => $q->where('status', $statusFilter))
            ->with([
                'candidate:id,user_id,name,first_name,last_name,email',
                'credentialType:id,name,category',
                'verifier:id,name,email',
            ])
            ->when($candidateSearch !== '', function ($q) use ($candidateSearch) {
                $q->whereHas('candidate', function ($candidateQ) use ($candidateSearch) {
                    $candidateQ->where('name', 'like', '%' . $candidateSearch . '%')
                        ->orWhere('first_name', 'like', '%' . $candidateSearch . '%')
                        ->orWhere('last_name', 'like', '%' . $candidateSearch . '%')
                        ->orWhere('email', 'like', '%' . $candidateSearch . '%');
                });
            })
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        $service = app(CredentialService::class);

        $items = $rows
            ->map(function (CandidateCredential $cred) use ($service) {
                $candidateName = $cred->candidate?->name
                    ?: trim(((string) ($cred->candidate?->first_name ?? '')) . ' ' . ((string) ($cred->candidate?->last_name ?? '')));

                return [
                    'id' => $cred->id,
                    'candidate' => $cred->candidate ? [
                        'id' => $cred->candidate->id,
                        'user_id' => $cred->candidate->user_id,
                        'name' => $candidateName,
                        'email' => $cred->candidate->email,
                    ] : null,
                    'credential_type' => $cred->credentialType ? [
                        'id' => $cred->credentialType->id,
                        'name' => $cred->credentialType->name,
                        'category' => $cred->credentialType->category,
                    ] : null,
                    'issued_at' => $cred->issued_at?->toIso8601String(),
                    'expires_at' => $cred->expires_at?->toIso8601String(),
                    'status' => $cred->status,
                    'verified_at' => $cred->verified_at?->toIso8601String(),
                    'verified_by' => $cred->verifier ? [
                        'id' => (int) $cred->verifier->id,
                        'name' => (string) $cred->verifier->name,
                        'email' => (string) $cred->verifier->email,
                    ] : null,
                    'document_path' => $cred->document_path,
                    'preview_url' => $service->signedDocumentUrl($cred),
                    'created_at' => $cred->created_at?->toIso8601String(),
                ];
            })
            ->values();

        return response()->api($items);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $credential = CandidateCredential::query()
            ->where('tenant_id', $orgId)
            ->where('id', $id)
            ->with(['candidate:id,email', 'credentialType:id,name'])
            ->firstOrFail();

        app(CredentialService::class)->verifyCredential((int) $credential->id, (int) ($user?->id ?? 0));

        ActivityLog::create([
            'organization_id' => $orgId,
            'user_id' => $user?->id,
            'action' => 'verified',
            'entity' => 'candidate_credential',
            'entity_name' => $credential->credentialType?->name ?: 'Credential',
            'entity_id' => $credential->id,
            'description' => 'Candidate credential verified.',
            'metadata' => [
                'candidate_id' => $credential->candidate_id,
                'candidate_email' => $credential->candidate?->email,
                'candidate_credential_id' => $credential->id,
                'credential_type_id' => $credential->credential_type_id,
            ],
        ]);

        $this->notifyComplianceDecision(
            orgId: (int) $orgId,
            actorId: (int) ($user?->id ?? 0),
            credential: $credential,
            decision: 'approved',
            reason: null
        );

        return response()->api(null, 200, [], 'Verified.');
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $credential = CandidateCredential::query()
            ->where('tenant_id', $orgId)
            ->where('id', $id)
            ->with(['candidate:id,email', 'credentialType:id,name'])
            ->firstOrFail();

        app(CredentialService::class)->rejectCredential((int) $credential->id, (int) ($user?->id ?? 0), (string) $validated['reason']);

        ActivityLog::create([
            'organization_id' => $orgId,
            'user_id' => $user?->id,
            'action' => 'rejected',
            'entity' => 'candidate_credential',
            'entity_name' => $credential->credentialType?->name ?: 'Credential',
            'entity_id' => $credential->id,
            'description' => 'Candidate credential rejected.',
            'metadata' => [
                'candidate_id' => $credential->candidate_id,
                'candidate_email' => $credential->candidate?->email,
                'candidate_credential_id' => $credential->id,
                'credential_type_id' => $credential->credential_type_id,
                'reason' => (string) $validated['reason'],
            ],
        ]);

        $this->notifyComplianceDecision(
            orgId: (int) $orgId,
            actorId: (int) ($user?->id ?? 0),
            credential: $credential,
            decision: 'rejected',
            reason: (string) $validated['reason']
        );

        return response()->api(null, 200, [], 'Rejected.');
    }

    private function notifyComplianceDecision(int $orgId, int $actorId, CandidateCredential $credential, string $decision, ?string $reason): void
    {
        $credentialName = (string) ($credential->credentialType?->name ?? 'Credential');
        $candidateName = (string) ($credential->candidate?->name ?? 'Candidate');
        $candidateUserId = (int) ($credential->candidate?->user_id ?? 0);

        $recipientIds = [];
        if ($candidateUserId > 0) {
            $recipientIds[] = $candidateUserId;
        }

        $staffRecipients = User::query()
            ->where('organization_id', $orgId)
            ->whereIn('role', ['org_super_admin', 'admin', 'recruiter', 'compliance'])
            ->limit(100)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $recipientIds = array_values(array_unique(array_filter(array_merge($recipientIds, $staffRecipients), fn ($id) => $id > 0 && $id !== $actorId)));
        if (count($recipientIds) === 0) {
            return;
        }

        $type = $decision === 'approved' ? 'compliance_approved' : 'compliance_rejected';
        $message = $decision === 'approved'
            ? "{$candidateName}'s {$credentialName} was approved."
            : "{$candidateName}'s {$credentialName} was rejected.";

        foreach ($recipientIds as $recipientId) {
            Notification::create([
                'tenant_id' => $orgId,
                'user_id' => $recipientId,
                'type' => $type,
                'entity_type' => 'candidate_credential',
                'entity_id' => (int) $credential->id,
                'data' => [
                    'message' => $message,
                    'candidate_id' => (int) $credential->candidate_id,
                    'candidate_name' => $candidateName,
                    'credential_name' => $credentialName,
                    'decision' => $decision,
                    'reason' => $reason,
                ],
                'created_at' => now(),
            ]);
        }
    }
}
