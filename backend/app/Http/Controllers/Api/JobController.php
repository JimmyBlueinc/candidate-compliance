<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $organizationId = $request->user()->organization_id;

        if (!$organizationId) {
            return response()->json([
                'message' => 'User does not belong to an organization.',
            ], 403);
        }

        $jobs = Job::forOrganization($organizationId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'jobs' => $jobs,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $organizationId = $request->user()->organization_id;

        if (!$organizationId) {
            return response()->json([
                'message' => 'User does not belong to an organization.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:draft,published',
            'visibility' => 'in:public,private',
        ]);

        $job = Job::create([
            'organization_id' => $organizationId,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'draft',
            'visibility' => $validated['visibility'] ?? 'public',
        ]);

        return response()->json([
            'message' => 'Job created successfully.',
            'job' => $job,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $organizationId = $request->user()->organization_id;

        if (!$organizationId) {
            return response()->json([
                'message' => 'User does not belong to an organization.',
            ], 403);
        }

        $job = Job::forOrganization($organizationId)->find($id);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        return response()->json([
            'job' => $job,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $organizationId = $request->user()->organization_id;

        if (!$organizationId) {
            return response()->json([
                'message' => 'User does not belong to an organization.',
            ], 403);
        }

        $job = Job::forOrganization($organizationId)->find($id);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:draft,published',
            'visibility' => 'sometimes|in:public,private',
        ]);

        $job->update($validated);

        return response()->json([
            'message' => 'Job updated successfully.',
            'job' => $job,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $organizationId = $request->user()->organization_id;

        if (!$organizationId) {
            return response()->json([
                'message' => 'User does not belong to an organization.',
            ], 403);
        }

        $job = Job::forOrganization($organizationId)->find($id);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $job->delete();

        return response()->json([
            'message' => 'Job deleted successfully.',
        ]);
    }
}
