<?php

namespace App\Http\Controllers\Api;

use App\Console\Commands\JobsSync;
use App\Http\Controllers\Controller;
use App\Models\JobSource;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class JobSourceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $rows = JobSource::query()
            ->where('tenant_id', $orgId)
            ->orderBy('enabled', 'desc')
            ->orderBy('source_key')
            ->get();

        return response()->json([
            'data' => $rows,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $validated = $request->validate([
            'source_key' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:rss,json'],
            'url' => ['required', 'string', 'max:2000'],
            'enabled' => ['sometimes', 'boolean'],
            'archive_missing' => ['sometimes', 'boolean'],
            'mapping' => ['nullable', 'array'],
        ]);

        $row = JobSource::query()->create([
            ...$validated,
            'tenant_id' => $orgId,
            'enabled' => (bool) ($validated['enabled'] ?? true),
            'archive_missing' => (bool) ($validated['archive_missing'] ?? false),
        ]);

        return response()->json([
            'data' => $row,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $row = JobSource::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($id);

        $validated = $request->validate([
            'source_key' => ['sometimes', 'string', 'max:80'],
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'in:rss,json'],
            'url' => ['sometimes', 'string', 'max:2000'],
            'enabled' => ['sometimes', 'boolean'],
            'archive_missing' => ['sometimes', 'boolean'],
            'mapping' => ['nullable', 'array'],
        ]);

        $row->update($validated);

        return response()->json([
            'data' => $row,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $row = JobSource::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($id);

        $row->delete();

        return response()->json([
            'deleted' => true,
        ]);
    }

    public function run(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $row = JobSource::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($id);

        $exit = Artisan::call('jobs:sync', [
            '--source' => $row->source_key,
            '--tenant' => (string) $orgId,
        ]);

        $row->refresh();

        return response()->json([
            'ok' => $exit === 0,
            'data' => $row,
            'output' => Artisan::output(),
        ]);
    }
}
