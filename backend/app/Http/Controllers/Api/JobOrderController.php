<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\JobOrder;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orgId = Org::id($request);

        $query = JobOrder::query();
        if ($orgId) {
            $query->where('tenant_id', $orgId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('published')) {
            $query->where('published', filter_var($request->input('published'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('specialty')) {
            $query->where('specialty', $request->input('specialty'));
        }

        $rows = $query->orderByDesc('created_at')->limit(200)->get();

        return response()->api($rows);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $job = JobOrder::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($id);

        return response()->api($job);
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
            'title' => ['required', 'string', 'max:255'],
            'facility_id' => [
                'nullable',
                'integer',
                Rule::exists('facilities', 'id')->where(static fn ($q) => $q->where('organization_id', $orgId)),
                'required_without:facility_name',
            ],
            'facility_name' => ['required_without:facility_id', 'string', 'max:255'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'bill_rate' => ['nullable', 'numeric', 'min:0'],
            'pay_rate' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'work_mode' => ['nullable', 'string', 'in:remote,on_site'],
            'stipend_weekly' => ['nullable', 'numeric', 'min:0'],
            'published' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', 'in:open,filled,closed'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:80'],
        ]);

        if (isset($validated['facility_id']) && $validated['facility_id'] !== null) {
            $facility = Facility::query()
                ->withoutGlobalScopes()
                ->where('organization_id', $orgId)
                ->findOrFail((int) $validated['facility_id']);

            $validated['facility_name'] = $facility->name;
        }

        $job = JobOrder::create([
            ...$validated,
            'tenant_id' => $orgId,
            'published' => (bool) ($validated['published'] ?? false),
        ]);

        return response()->api($job, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $job = JobOrder::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'facility_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('facilities', 'id')->where(static fn ($q) => $q->where('organization_id', $orgId)),
            ],
            'facility_name' => ['sometimes', 'string', 'max:255'],
            'specialty' => ['sometimes', 'nullable', 'string', 'max:255'],
            'bill_rate' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'pay_rate' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'work_mode' => ['sometimes', 'nullable', 'string', 'in:remote,on_site'],
            'stipend_weekly' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'published' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', 'in:open,filled,closed'],
            'external_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'source' => ['sometimes', 'nullable', 'string', 'max:80'],
        ]);

        if (array_key_exists('facility_id', $validated) && $validated['facility_id'] !== null) {
            $facility = Facility::query()
                ->withoutGlobalScopes()
                ->where('organization_id', $orgId)
                ->findOrFail((int) $validated['facility_id']);

            $validated['facility_name'] = $facility->name;
        }

        $job->update($validated);

        return response()->api($job);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $job = JobOrder::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($id);

        $job->delete();

        return response()->api(['deleted' => true]);
    }
}
