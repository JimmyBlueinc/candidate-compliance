<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavedFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Support\Org;

class FilterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        $filters = SavedFilter::where('user_id', $request->user()->id)
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($filters);
    }

    public function store(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'filters' => 'required|array',
        ]);

        $filter = SavedFilter::create([
            'organization_id' => $orgId,
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'filters' => $validated['filters'],
        ]);

        return response()->json($filter, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $orgId = Org::id($request);
        $filter = SavedFilter::findOrFail($id);

        if ($orgId && (int) $filter->organization_id !== (int) $orgId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Only owner can update
        if ($filter->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'filters' => 'sometimes|array',
        ]);

        $filter->update($validated);

        return response()->json($filter);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $orgId = Org::id($request);
        $filter = SavedFilter::findOrFail($id);

        if ($orgId && (int) $filter->organization_id !== (int) $orgId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Only owner can delete
        if ($filter->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $filter->delete();

        return response()->json(['message' => 'Filter deleted successfully']);
    }
}
