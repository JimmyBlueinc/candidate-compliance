<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\JobOrder;
use App\Models\Placement;
use App\Models\Submission;
use App\Services\ComplianceService;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubmissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $query = Submission::query()
            ->with([
                'candidate:id,name,first_name,last_name,email,specialty',
                'jobOrder:id,title,facility_id,facility_name,specialty',
            ])
            ->where('tenant_id', $orgId);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('candidate_id')) {
            $query->where('candidate_id', (int) $request->input('candidate_id'));
        }

        if ($request->filled('job_order_id')) {
            $query->where('job_order_id', (int) $request->input('job_order_id'));
        }

        $rows = $query
            ->orderByDesc('created_at')
            ->limit(500)
            ->get();

        return response()->api($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $validated = $request->validate([
            'candidate_id' => ['required', 'integer'],
            'job_order_id' => ['required', 'integer'],
            'expires_in_days' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:90'],
        ]);

        $candidate = Candidate::query()
            ->where('tenant_id', $orgId)
            ->findOrFail((int) $validated['candidate_id']);

        $job = JobOrder::query()
            ->where('tenant_id', $orgId)
            ->findOrFail((int) $validated['job_order_id']);

        if ($job->facility_id) {
            $eval = app(ComplianceService::class)->evaluateWorkerCompliance($orgId, $candidate->id, (int) $job->facility_id);
            $effective = $eval['effective'] ?? $eval['facility'] ?? [];
            $status = (string) ($eval['status'] ?? ($effective['status'] ?? 'ready'));

            if ($status !== 'ready') {
                return response()->json([
                    'message' => 'Worker is not compliance-ready for this facility.',
                    'errors' => [
                        'status' => $status,
                        'missing' => $effective['missing'] ?? [],
                        'expired' => $effective['expired'] ?? [],
                        'pending' => $effective['pending'] ?? [],
                        'rejected' => $effective['rejected'] ?? [],
                    ],
                ], 422);
            }
        }

        $token = Str::random(64);

        $expiresAt = null;
        if (array_key_exists('expires_in_days', $validated) && $validated['expires_in_days']) {
            $expiresAt = now()->addDays((int) $validated['expires_in_days']);
        }

        $submission = Submission::create([
            'tenant_id' => $orgId,
            'candidate_id' => $candidate->id,
            'job_order_id' => $job->id,
            'unique_token' => $token,
            'expires_at' => $expiresAt,
            'view_count' => 0,
        ]);

        $placement = Placement::firstOrCreate([
            'tenant_id' => $orgId,
            'candidate_id' => $candidate->id,
            'job_order_id' => $job->id,
        ], [
            'submission_id' => $submission->id,
            'stage' => 'submitted',
            'recruiter_id' => $user?->id,
        ]);

        if (!$placement->submission_id) {
            $placement->submission_id = $submission->id;
            $placement->save();
        }

        if ($placement->stage === 'applied') {
            $placement->stage = 'submitted';
            $placement->save();
        }

        if (!$placement->recruiter_id && $user) {
            $placement->recruiter_id = $user->id;
            $placement->save();
        }

        $url = url("/view/submission/{$submission->unique_token}");

        return response()->api([
            'id' => $submission->id,
            'url' => $url,
            'unique_token' => $submission->unique_token,
            'expires_at' => $submission->expires_at?->toIso8601String(),
            'view_count' => $submission->view_count,
        ], 201);
    }

    public function history(Request $request, int $candidateId): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        Candidate::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($candidateId);

        $rows = Submission::query()
            ->with(['jobOrder:id,title,facility_name,specialty'])
            ->where('tenant_id', $orgId)
            ->where('candidate_id', $candidateId)
            ->orderByDesc('created_at')
            ->limit(200)
            ->get()
            ->map(function (Submission $s) {
                return [
                    'id' => $s->id,
                    'job_order' => $s->jobOrder ? [
                        'id' => $s->jobOrder->id,
                        'title' => $s->jobOrder->title,
                        'facility_name' => $s->jobOrder->facility_name,
                        'specialty' => $s->jobOrder->specialty,
                    ] : null,
                    'unique_token' => $s->unique_token,
                    'expires_at' => $s->expires_at?->toIso8601String(),
                    'view_count' => (int) ($s->view_count ?? 0),
                    'created_at' => $s->created_at?->toIso8601String(),
                    'url' => url("/view/submission/{$s->unique_token}"),
                ];
            })
            ->values();

        return response()->api($rows);
    }

    public function revoke(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $submission = Submission::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($id);

        if (in_array((string) $submission->status, ['accepted', 'rejected'], true)) {
            return response()->json([
                'message' => 'Submission cannot be revoked in its current status.',
            ], 422);
        }

        $submission->status = 'revoked';
        $submission->expires_at = now();
        $submission->save();

        return response()->api($submission);
    }

    public function expire(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $submission = Submission::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($id);

        $submission->status = 'expired';
        $submission->expires_at = now();
        $submission->save();

        return response()->api($submission);
    }
}
