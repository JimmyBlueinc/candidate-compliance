<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateJobBookmark;
use App\Models\JobOrder;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalJobsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || (string) ($user->role ?? '') !== 'candidate') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $orgId = Org::id($request) ?: (int) ($user->organization_id ?? 0);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $candidate = Candidate::query()
            ->where('tenant_id', $orgId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->first();

        $jobs = JobOrder::query()
            ->where('tenant_id', $orgId)
            ->where('published', true)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $bookmarkIds = [];
        if ($candidate) {
            $bookmarkIds = CandidateJobBookmark::query()
                ->where('tenant_id', $orgId)
                ->where('candidate_id', $candidate->id)
                ->pluck('job_order_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $bookmarkedLookup = array_flip($bookmarkIds);

        $jobs = $jobs->map(function (JobOrder $job) use ($bookmarkedLookup) {
            $row = $job->toArray();
            $row['is_bookmarked'] = isset($bookmarkedLookup[(int) $job->id]);
            return $row;
        })->values();

        return response()->api($jobs);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || (string) ($user->role ?? '') !== 'candidate') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $orgId = Org::id($request) ?: (int) ($user->organization_id ?? 0);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $candidate = Candidate::query()
            ->where('tenant_id', $orgId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->first();

        $job = JobOrder::query()
            ->where('tenant_id', $orgId)
            ->where('published', true)
            ->findOrFail($id);

        $isBookmarked = false;
        if ($candidate) {
            $isBookmarked = CandidateJobBookmark::query()
                ->where('tenant_id', $orgId)
                ->where('candidate_id', $candidate->id)
                ->where('job_order_id', $job->id)
                ->exists();
        }

        $row = $job->toArray();
        $row['is_bookmarked'] = $isBookmarked;

        return response()->api($row);
    }

    public function bookmarks(Request $request): JsonResponse
    {
        $context = $this->resolveCandidateContext($request);
        if (isset($context['error'])) {
            return $context['error'];
        }

        $orgId = $context['org_id'];
        $candidate = $context['candidate'];

        $bookmarks = CandidateJobBookmark::query()
            ->where('tenant_id', $orgId)
            ->where('candidate_id', $candidate->id)
            ->with('jobOrder')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(function (CandidateJobBookmark $bookmark) {
                return [
                    'id' => $bookmark->id,
                    'job_order_id' => $bookmark->job_order_id,
                    'created_at' => optional($bookmark->created_at)->toIso8601String(),
                    'job' => $bookmark->jobOrder,
                ];
            })
            ->values();

        return response()->api($bookmarks);
    }

    public function upsertBookmark(Request $request, int $jobOrderId): JsonResponse
    {
        $context = $this->resolveCandidateContext($request);
        if (isset($context['error'])) {
            return $context['error'];
        }

        $orgId = $context['org_id'];
        $candidate = $context['candidate'];

        $job = JobOrder::query()
            ->where('tenant_id', $orgId)
            ->where('published', true)
            ->findOrFail($jobOrderId);

        CandidateJobBookmark::query()->firstOrCreate([
            'tenant_id' => $orgId,
            'candidate_id' => $candidate->id,
            'job_order_id' => $job->id,
        ]);

        return response()->api(['bookmarked' => true], 200, [], 'Job bookmarked.');
    }

    public function removeBookmark(Request $request, int $jobOrderId): JsonResponse
    {
        $context = $this->resolveCandidateContext($request);
        if (isset($context['error'])) {
            return $context['error'];
        }

        $orgId = $context['org_id'];
        $candidate = $context['candidate'];

        CandidateJobBookmark::query()
            ->where('tenant_id', $orgId)
            ->where('candidate_id', $candidate->id)
            ->where('job_order_id', $jobOrderId)
            ->delete();

        return response()->api(['bookmarked' => false], 200, [], 'Bookmark removed.');
    }

    private function resolveCandidateContext(Request $request): array
    {
        $user = $request->user();
        if (!$user || (string) ($user->role ?? '') !== 'candidate') {
            return ['error' => response()->json(['message' => 'Unauthorized.'], 403)];
        }

        $orgId = Org::id($request);
        if (!$orgId) {
            return ['error' => response()->json(['message' => 'Organization context missing.'], 400)];
        }

        $candidate = Candidate::query()
            ->where('tenant_id', $orgId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->first();

        if (!$candidate) {
            return ['error' => response()->json(['message' => 'Candidate profile not found.'], 404)];
        }

        return [
            'org_id' => $orgId,
            'candidate' => $candidate,
        ];
    }
}
