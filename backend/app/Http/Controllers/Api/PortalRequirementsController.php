<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateCredential;
use App\Models\CredentialType;
use App\Models\Organization;
use App\Models\Template;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalRequirementsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || (string) ($user->role ?? '') !== 'candidate') {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $candidate = Candidate::query()
            ->where('tenant_id', $orgId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->first();

        if (!$candidate) {
            return response()->api([]);
        }

        $templates = Template::query()
            ->where('is_active', true)
            ->where('organization_id', $orgId)
            ->orderBy('name')
            ->get();

        if ($templates->count() === 0) {
            $defaultOrgId = Organization::query()
                ->where('slug', 'default')
                ->value('id');

            if ($defaultOrgId) {
                $templates = Template::query()
                    ->where('is_active', true)
                    ->where('organization_id', (int) $defaultOrgId)
                    ->orderBy('name')
                    ->get();
            }
        }

        $types = CredentialType::query()
            ->where('tenant_id', $orgId)
            ->get()
            ->keyBy(fn (CredentialType $t) => strtolower(trim((string) $t->name)));

        $credentials = CandidateCredential::query()
            ->where('tenant_id', $orgId)
            ->where('candidate_id', $candidate->id)
            ->with([
                'credentialType:id,name,category',
                'latestRejectedVerification:id,credential_id,notes,status,created_at',
            ])
            ->orderByDesc('created_at')
            ->limit(500)
            ->get();

        $byTypeId = [];
        foreach ($credentials as $c) {
            $typeId = (int) $c->credential_type_id;
            if ($typeId <= 0) {
                continue;
            }
            if (!array_key_exists($typeId, $byTypeId)) {
                $byTypeId[$typeId] = $c;
            }
        }

        $items = $templates->map(function (Template $t) use ($types, $byTypeId) {
            $typeKey = strtolower(trim((string) $t->credential_type));
            $type = $types[$typeKey] ?? null;
            $cred = $type ? ($byTypeId[(int) $type->id] ?? null) : null;

            $status = 'missing';
            $rejectionReason = null;

            if ($cred) {
                $expiry = $cred->expires_at;
                $isExpired = $expiry ? $expiry->startOfDay()->lte(now()->startOfDay()) : false;

                if ((string) $cred->status === 'verified') {
                    $status = $isExpired ? 'expired' : 'approved';
                } elseif ((string) $cred->status === 'rejected') {
                    $status = 'rejected';
                    $rejectionReason = $cred->latestRejectedVerification?->notes;
                } elseif ((string) $cred->status === 'pending') {
                    $status = 'pending_review';
                } else {
                    $status = (string) $cred->status;
                }
            }

            return [
                'id' => $t->id,
                'name' => $t->name,
                'credential_type' => $t->credential_type,
                'position' => $t->position,
                'description' => $t->description,
                'default_days' => $t->default_days,
                'status' => $status,
                'credential' => $cred ? [
                    'id' => $cred->id,
                    'issue_date' => $cred->issued_at?->format('Y-m-d'),
                    'expiry_date' => $cred->expires_at?->format('Y-m-d'),
                ] : null,
                'review_status' => $cred ? (string) $cred->status : null,
                'rejection_reason' => $rejectionReason,
            ];
        });

        $total = $items->count();
        $approved = $items->filter(fn ($i) => ($i['status'] ?? null) === 'approved')->count();

        return response()->api($items->values(), 200, [
            'total' => $total,
            'approved' => $approved,
        ]);
    }
}
