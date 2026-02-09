<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Support\Org;

class TemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orgId = Org::id($request);

        $templates = Template::where('is_active', true)
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->orderBy('name')
            ->get();

        return response()->json($templates);
    }

    public function store(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'credential_type' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'default_days' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $template = Template::create([
            ...$validated,
            'organization_id' => $orgId,
            'user_id' => $request->user()->id,
            'is_active' => true,
        ]);

        return response()->json($template, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $orgId = Org::id($request);
        $template = Template::when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->findOrFail($id);

        // Only owner or admin can update
        if ($template->user_id !== $request->user()->id && !in_array($request->user()->role, ['admin', 'org_super_admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'credential_type' => 'sometimes|string|max:255',
            'position' => 'nullable|string|max:255',
            'default_days' => 'sometimes|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $template->update($validated);

        return response()->json($template);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $orgId = Org::id($request);
        $template = Template::when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->findOrFail($id);

        // Only owner or admin can delete
        if ($template->user_id !== $request->user()->id && !in_array($request->user()->role, ['admin', 'org_super_admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $template->delete();

        return response()->json(['message' => 'Template deleted successfully']);
    }
}
