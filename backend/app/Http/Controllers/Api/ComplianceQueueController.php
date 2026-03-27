<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CandidateCredential;
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

        $rows = CandidateCredential::query()
            ->where('tenant_id', $orgId)
            ->where('status', 'pending')
            ->with([
                'candidate:id,name,first_name,last_name,email',
                'credentialType:id,name,category',
            ])
            ->orderBy('created_at')
            ->limit(500)
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

        return response()->api(null, 200, [], 'Rejected.');
    }
}
