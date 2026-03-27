<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidatePipeline;
use App\Services\CandidatePipelineService;
use App\Support\ApiResponse;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class CandidatePipelineController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return ApiResponse::error('Organization context missing.', 400);
        }

        $query = CandidatePipeline::query()
            ->with([
                'candidate:id,tenant_id,name,first_name,last_name,email,phone,specialty',
                'assignedRecruiter:id,name',
            ])
            ->where('tenant_id', $orgId);

        if ($request->filled('stage')) {
            $query->where('stage', $request->input('stage'));
        }

        if ($request->filled('assigned_recruiter_id')) {
            $query->where('assigned_recruiter_id', (int) $request->input('assigned_recruiter_id'));
        }

        try {
            $rows = $query
                ->orderByDesc('updated_at')
                ->limit(500)
                ->get();
        } catch (QueryException $e) {
            return ApiResponse::error('Candidate pipeline is not available yet. Please ensure database migrations have been applied.', 500);
        }

        return response()->api($rows);
    }

    public function show(Request $request, int $candidateId): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return ApiResponse::error('Organization context missing.', 400);
        }

        Candidate::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($candidateId);

        $pipeline = CandidatePipeline::query()
            ->with(['assignedRecruiter:id,name'])
            ->firstOrCreate([
                'tenant_id' => $orgId,
                'candidate_id' => $candidateId,
            ], [
                'stage' => 'new',
            ]);

        return response()->api($pipeline);
    }

    public function setStage(Request $request, int $candidateId, CandidatePipelineService $service): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return ApiResponse::error('Organization context missing.', 400);
        }

        $validated = $request->validate([
            'stage' => ['required', 'string', 'in:new,screening,interview,credential_pending,ready_to_submit,submitted,placed,inactive'],
        ]);

        $pipeline = $service->setStage($orgId, $candidateId, (string) $validated['stage'], $request->user());

        return response()->api($pipeline);
    }

    public function assignRecruiter(Request $request, int $candidateId, CandidatePipelineService $service): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return ApiResponse::error('Organization context missing.', 400);
        }

        $validated = $request->validate([
            'recruiter_id' => ['required', 'integer'],
        ]);

        $pipeline = $service->assignRecruiter($orgId, $candidateId, (int) $validated['recruiter_id'], $request->user());

        return response()->api($pipeline);
    }

    public function addNote(Request $request, int $candidateId, CandidatePipelineService $service): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return ApiResponse::error('Organization context missing.', 400);
        }

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $pipeline = $service->addPipelineNote($orgId, $candidateId, (string) $validated['note'], $request->user());

        return response()->api($pipeline);
    }
}
