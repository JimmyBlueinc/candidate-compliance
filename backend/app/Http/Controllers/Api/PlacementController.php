<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\JobOrder;
use App\Models\Placement;
use App\Services\OperationalPlacementService;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PlacementController extends Controller
{
    public function show(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $placement = Placement::query()
            ->with([
                'candidate',
                'jobOrder',
                'recruiter:id,name',
                'submission',
                'assignment',
            ])
            ->where('tenant_id', $orgId)
            ->findOrFail($id);

        return response()->api($placement);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $placement = Placement::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($id);

        $validated = $request->validate([
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date'],
            'recruiter_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $placement->update($validated);

        return response()->api($placement);
    }

    public function board(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $placements = Placement::query()
            ->with([
                'candidate:id,first_name,last_name,name,email,specialty',
                'jobOrder:id,title,facility_name,specialty,bill_rate,pay_rate,status',
                'recruiter:id,name',
            ])
            ->where('tenant_id', $orgId)
            ->orderByDesc('updated_at')
            ->limit(500)
            ->get();

        $data = $placements->map(function (Placement $p) {
            $bill = (float) ($p->jobOrder?->bill_rate ?? 0);
            $pay = (float) ($p->jobOrder?->pay_rate ?? 0);

            return [
                'id' => $p->id,
                'stage' => $p->stage,
                'start_date' => $p->start_date?->format('Y-m-d'),
                'end_date' => $p->end_date?->format('Y-m-d'),
                'recruiter' => $p->recruiter ? [
                    'id' => $p->recruiter->id,
                    'name' => $p->recruiter->name,
                ] : null,
                'candidate' => $p->candidate ? [
                    'id' => $p->candidate->id,
                    'name' => $p->candidate->name,
                    'first_name' => $p->candidate->first_name,
                    'last_name' => $p->candidate->last_name,
                    'email' => $p->candidate->email,
                    'specialty' => $p->candidate->specialty,
                ] : null,
                'job_order' => $p->jobOrder ? [
                    'id' => $p->jobOrder->id,
                    'title' => $p->jobOrder->title,
                    'facility_name' => $p->jobOrder->facility_name,
                    'specialty' => $p->jobOrder->specialty,
                    'bill_rate' => $p->jobOrder->bill_rate,
                    'pay_rate' => $p->jobOrder->pay_rate,
                    'status' => $p->jobOrder->status,
                ] : null,
                'margin' => $bill - $pay,
                'updated_at' => $p->updated_at?->toIso8601String(),
            ];
        });

        return response()->api($data);
    }

    public function moveStage(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $validated = $request->validate([
            'stage' => ['required', 'string', 'in:applied,submitted,interviewing,offered,placed,active'],
        ]);

        $placement = Placement::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($id);

        $stages = ['applied', 'submitted', 'interviewing', 'offered', 'placed', 'active'];
        $currentIndex = array_search((string) $placement->stage, $stages, true);
        $requestedIndex = array_search((string) $validated['stage'], $stages, true);

        $valid = $currentIndex !== false
            && $requestedIndex !== false
            && (
                $requestedIndex === $currentIndex + 1
                || $requestedIndex === $currentIndex - 1
            );

        if (!$valid) {
            return response()->json([
                'message' => 'Invalid pipeline transition',
            ], 422);
        }

        $placement->stage = $validated['stage'];
        $placement->save();

        $assignmentId = null;
        if ($placement->stage === 'placed') {
            try {
                $assignment = app(OperationalPlacementService::class)
                    ->createFromPipelinePlacement($orgId, $placement->id);

                $assignmentId = $assignment->id;
            } catch (ValidationException $e) {
                return response()->api([
                    'errors' => $e->errors(),
                ], 422, [], 'Operational placement could not be created.');
            }
        }

        return response()->api([
            'id' => $placement->id,
            'stage' => $placement->stage,
            'assignment_id' => $assignmentId,
        ]);
    }

    public function expressInterest(Request $request, int $jobOrderId): JsonResponse
    {
        $user = $request->user();
        if (!$user || (string) ($user->role ?? '') !== 'candidate') {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $candidate = Candidate::query()
            ->where('tenant_id', $orgId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->first();

        if (!$candidate) {
            return response()->json([
                'message' => 'Candidate profile not found.',
            ], 404);
        }

        $job = JobOrder::query()
            ->where('tenant_id', $orgId)
            ->where('status', 'open')
            ->findOrFail($jobOrderId);

        $placement = Placement::firstOrCreate([
            'tenant_id' => $orgId,
            'candidate_id' => $candidate->id,
            'job_order_id' => $job->id,
        ], [
            'stage' => 'applied',
        ]);

        return response()->api([
            'id' => $placement->id,
            'stage' => $placement->stage,
        ], 201, [], 'Interest recorded.');
    }
}
