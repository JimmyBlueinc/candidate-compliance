<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Job;
use Illuminate\Http\JsonResponse;

class PublicJobController extends Controller
{
    public function index(string $orgSlug): JsonResponse
    {
        $organization = Organization::where('slug', $orgSlug)->first();

        if (!$organization) {
            return response()->json([
                'message' => 'Organization not found.',
            ], 404);
        }

        $jobs = Job::forOrganization($organization->id)
            ->published()
            ->public()
            ->get(['id', 'title', 'description', 'created_at']);

        return response()->json([
            'organization' => [
                'name' => $organization->name,
                'slug' => $organization->slug,
            ],
            'jobs' => $jobs,
        ]);
    }

    public function show(string $orgSlug, int $jobId): JsonResponse
    {
        $organization = Organization::where('slug', $orgSlug)->first();

        if (!$organization) {
            return response()->json([
                'message' => 'Organization not found.',
            ], 404);
        }

        $job = Job::forOrganization($organization->id)
            ->published()
            ->public()
            ->find($jobId, ['id', 'title', 'description', 'created_at']);

        if (!$job) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        return response()->json([
            'organization' => [
                'name' => $organization->name,
                'slug' => $organization->slug,
            ],
            'job' => $job,
        ]);
    }
}
