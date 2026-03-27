<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
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

        $orgId = Org::id($request);
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

        if (!$candidate) {
            return response()->api([]);
        }

        $specialty = trim((string) ($candidate->specialty ?? ''));

        $jobs = JobOrder::query()
            ->where('tenant_id', $orgId)
            ->where('published', true)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->api($jobs);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || (string) ($user->role ?? '') !== 'candidate') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $orgId = Org::id($request);
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

        if (!$candidate) {
            return response()->json(['message' => 'Candidate profile not found.'], 404);
        }

        $specialty = trim((string) ($candidate->specialty ?? ''));

        $job = JobOrder::query()
            ->where('tenant_id', $orgId)
            ->where('published', true)
            ->findOrFail($id);

        return response()->api($job);
    }
}
