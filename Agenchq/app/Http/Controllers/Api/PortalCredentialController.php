<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateCredential;
use App\Models\CredentialType;
use App\Models\Scopes\TenantScope;
use App\Services\DefaultComplianceCatalogService;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\CredentialService;
use Illuminate\Support\Facades\Log;

class PortalCredentialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || (string) ($user->role ?? '') !== 'candidate') {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $orgId = $this->resolveOrgId($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $candidate = Candidate::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $orgId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->first();

        if (!$candidate) {
            return response()->api([]);
        }

        $service = app(CredentialService::class);

        $credentials = CandidateCredential::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $orgId)
            ->where('candidate_id', $candidate->id)
            ->with([
                'credentialType:id,name,category',
                'latestRejectedVerification:id,credential_id,notes,status,created_at',
                'latestReviewFeedback:id,credential_id,notes,status,created_at',
            ])
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        $items = $credentials->map(function (CandidateCredential $c) use ($service) {
            return [
                'id' => $c->id,
                'credential_type' => $c->credentialType ? [
                    'id' => $c->credentialType->id,
                    'name' => $c->credentialType->name,
                    'category' => $c->credentialType->category,
                ] : null,
                'issued_at' => $c->issued_at?->toIso8601String(),
                'expires_at' => $c->expires_at?->toIso8601String(),
                'status' => $c->status,
                'latest_rejection_reason' => in_array((string) $c->status, ['rejected', 'needs_correction'], true)
                    ? ($c->latestReviewFeedback?->notes ?? $c->latestRejectedVerification?->notes)
                    : null,
                'preview_url' => $service->signedDocumentUrl($c),
                'created_at' => $c->created_at?->toIso8601String(),
            ];
        })->values();

        return response()->api($items);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || (string) ($user->role ?? '') !== 'candidate') {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $orgId = $this->resolveOrgId($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        try {
            app(DefaultComplianceCatalogService::class)->ensureForOrganization((int) $orgId);
        } catch (\Throwable $e) {
            Log::warning('PortalCredentialController store: default compliance seeding skipped after error.', [
                'org_id' => $orgId,
                'error' => $e->getMessage(),
            ]);
        }

        $validated = $request->validate([
            'credential_type' => ['required', 'string', 'max:255'],
            'issue_date' => ['required', 'date'],
            'expiry_date' => ['required', 'date', 'after:issue_date'],
        ]);

        $candidate = Candidate::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $orgId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->first();

        if (!$candidate) {
            return response()->json([
                'message' => 'Candidate profile not found.',
            ], 404);
        }

        $typeName = trim((string) $validated['credential_type']);
        $type = CredentialType::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $orgId)
            ->where('name', $typeName)
            ->first();

        if (!$type) {
            return response()->json([
                'message' => 'Credential type not recognized.',
                'errors' => [
                    'credential_type' => ['Credential type not recognized.'],
                ],
            ], 422);
        }

        $service = app(CredentialService::class);
        $credential = $service->uploadCredential(
            $orgId,
            (int) $candidate->id,
            (int) $type->id,
            null,
            $validated['issue_date'],
            $validated['expiry_date']
        );

        return response()->api([
            'id' => $credential->id,
            'credential_type' => [
                'id' => $type->id,
                'name' => $type->name,
                'category' => $type->category,
            ],
            'issued_at' => $credential->issued_at?->toIso8601String(),
            'expires_at' => $credential->expires_at?->toIso8601String(),
            'status' => $credential->status,
        ], 201, [], 'Credential created. Upload the document to submit for review.');
    }

    public function upload(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || (string) ($user->role ?? '') !== 'candidate') {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $orgId = $this->resolveOrgId($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $candidate = Candidate::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $orgId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->first();

        if (!$candidate) {
            return response()->json([
                'message' => 'Candidate profile not found.',
            ], 404);
        }

        $credential = CandidateCredential::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $orgId)
            ->where('id', $id)
            ->where('candidate_id', $candidate->id)
            ->first();

        if (!$credential) {
            return response()->json([
                'message' => 'Credential not found.',
            ], 404);
        }

        $service = app(CredentialService::class);
        $service->attachDocument($credential, $validated['file']);

        return response()->api([
            'id' => $credential->id,
            'status' => $credential->status,
        ], 201, [], 'Document uploaded and queued for review.');
    }

    private function resolveOrgId(Request $request): int
    {
        $user = $request->user();
        $headerOrgId = (int) ($request->header('X-Tenant-Id') ?: 0);
        if ($headerOrgId > 0) {
            return $headerOrgId;
        }

        $userOrgId = (int) ($user?->organization_id ?? 0);
        if ($userOrgId > 0) {
            return $userOrgId;
        }

        if ($user) {
            $candidateOrgId = (int) (Candidate::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->orWhere('email', $user->email);
                })
                ->orderByDesc('id')
                ->value('tenant_id') ?? 0);

            if ($candidateOrgId > 0) {
                return $candidateOrgId;
            }
        }

        return (int) (Org::id($request) ?: 0);
    }
}
