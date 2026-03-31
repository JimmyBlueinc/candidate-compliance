<?php

namespace App\Http\Controllers;

use App\Models\Credential;
use App\Models\Submission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PublicSubmissionController extends Controller
{
    public function show(Request $request, string $token): JsonResponse
    {
        $submission = Submission::query()
            ->withoutGlobalScopes()
            ->with([
                'tenant:id,name,slug,primary_color,logo_path',
                'candidate:id,tenant_id,first_name,last_name,name,email,phone,specialty,tags',
                'jobOrder:id,tenant_id,title,facility_name,specialty,bill_rate,pay_rate,start_date,work_mode,stipend_weekly',
            ])
            ->where('unique_token', $token)
            ->firstOrFail();

        if ($submission->expires_at && now()->greaterThan($submission->expires_at)) {
            return response()->json([
                'message' => 'This submission link has expired.',
            ], 410);
        }

        Submission::query()
            ->withoutGlobalScopes()
            ->where('id', $submission->id)
            ->increment('view_count');

        $submission->view_count = (int) ($submission->view_count ?? 0) + 1;

        $org = $submission->tenant;
        $candidate = $submission->candidate;
        $job = $submission->jobOrder;

        $credentials = [];
        if ($candidate && $candidate->email) {
            $credentials = Credential::query()
                ->where('organization_id', $submission->tenant_id)
                ->where('email', $candidate->email)
                ->orderByDesc('created_at')
                ->limit(50)
                ->get()
                ->map(function (Credential $cred) {
                    $calc = $cred->getCalculatedStatus();
                    $status = ($cred->status && $cred->status !== '') ? $cred->status : ($calc['status'] ?? '');
                    return [
                        'id' => $cred->id,
                        'credential_type' => $cred->credential_type,
                        'status' => $status,
                        'has_document' => (bool) ($cred->document_url),
                    ];
                })
                ->values();
        }

        return response()->json([
            'data' => [
                'brand' => [
                    'name' => $org?->name,
                    'primary_color' => $org?->primary_color,
                    'logo_url' => $org?->logo_path ? Storage::url($org->logo_path) : null,
                ],
                'submission' => [
                    'id' => $submission->id,
                    'expires_at' => $submission->expires_at?->toIso8601String(),
                    'view_count' => (int) ($submission->view_count ?? 0),
                    'created_at' => $submission->created_at?->toIso8601String(),
                ],
                'candidate' => [
                    'first_name' => $candidate?->first_name,
                    'last_name_masked' => $candidate?->last_name ? substr((string) $candidate->last_name, 0, 1) . '.' : null,
                    'email_masked' => $candidate?->email ? preg_replace('/^(.).+(@.+)$/', '$1***$2', (string) $candidate->email) : null,
                    'phone_masked' => $candidate?->phone ? '***-***-' . substr(preg_replace('/\D+/', '', (string) $candidate->phone), -4) : null,
                    'specialty' => $candidate?->specialty,
                    'skills' => is_array($candidate?->tags) ? $candidate->tags : [],
                    'experience' => null,
                ],
                'job_order' => $job ? [
                    'title' => $job->title,
                    'facility_name' => $job->facility_name,
                    'specialty' => $job->specialty,
                ] : null,
                'credentials' => $credentials,
            ],
        ]);
    }
}
