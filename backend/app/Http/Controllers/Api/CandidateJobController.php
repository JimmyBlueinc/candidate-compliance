<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CandidateJobController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get organization from user's relationship
        $organization = $user->organization;

        if (!$organization) {
            return response()->json([
                'message' => 'You are not associated with an organization.',
            ], 403);
        }

        // Get published public jobs for this organization only
        $jobs = Job::forOrganization($organization->id)
            ->published()
            ->public()
            ->orderBy('created_at', 'desc')
            ->get(['id', 'title', 'description', 'created_at']);

        return response()->json([
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'primary_color' => $organization->primary_color,
                'logo_path' => $organization->logo_path,
            ],
            'jobs' => $jobs,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $organization = $user->organization;

        if (!$organization) {
            return response()->json([
                'message' => 'You are not associated with an organization.',
            ], 403);
        }

        $job = Job::forOrganization($organization->id)
            ->published()
            ->public()
            ->find($id);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        return response()->json([
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'primary_color' => $organization->primary_color,
                'logo_path' => $organization->logo_path,
            ],
            'job' => $job,
        ]);
    }
}
