<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Placement;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalPlacementController extends Controller
{
    public function myTravel(Request $request): JsonResponse
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
            return response()->api(null);
        }

        $placement = Placement::query()
            ->with([
                'jobOrder:id,title,facility_name,specialty,bill_rate,pay_rate,status',
                'housingRecord:id,tenant_id,placement_id,address,landlord_contact,lease_start,lease_end',
                'travelLogs:id,tenant_id,placement_id,type,details,confirmation_number,start_date,end_date',
            ])
            ->where('tenant_id', $orgId)
            ->where('candidate_id', $candidate->id)
            ->whereIn('stage', ['placed', 'active'])
            ->orderByRaw("case stage when 'active' then 0 else 1 end")
            ->orderByDesc('updated_at')
            ->first();

        if (!$placement) {
            return response()->api(null);
        }

        return response()->api([
                'id' => $placement->id,
                'stage' => $placement->stage,
                'start_date' => $placement->start_date?->format('Y-m-d'),
                'end_date' => $placement->end_date?->format('Y-m-d'),
                'arrival_confirmed_at' => $placement->arrival_confirmed_at?->toIso8601String(),
                'job_order' => $placement->jobOrder ? [
                    'id' => $placement->jobOrder->id,
                    'title' => $placement->jobOrder->title,
                    'facility_name' => $placement->jobOrder->facility_name,
                    'specialty' => $placement->jobOrder->specialty,
                ] : null,
                'housing' => $placement->housingRecord ? [
                    'address' => $placement->housingRecord->address,
                    'landlord_contact' => $placement->housingRecord->landlord_contact,
                    'lease_start' => $placement->housingRecord->lease_start?->format('Y-m-d'),
                    'lease_end' => $placement->housingRecord->lease_end?->format('Y-m-d'),
                ] : null,
                'travel' => $placement->travelLogs
                    ? $placement->travelLogs->map(function ($t) {
                        return [
                            'id' => $t->id,
                            'type' => $t->type,
                            'details' => $t->details,
                            'confirmation_number' => $t->confirmation_number,
                            'start_date' => $t->start_date?->format('Y-m-d'),
                            'end_date' => $t->end_date?->format('Y-m-d'),
                        ];
                    })->values()
                    : [],
            ]);
    }

    public function confirmArrival(Request $request, int $id): JsonResponse
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

        $placement = Placement::query()
            ->where('tenant_id', $orgId)
            ->where('candidate_id', $candidate->id)
            ->where('id', $id)
            ->whereIn('stage', ['placed', 'active'])
            ->firstOrFail();

        if (!$placement->arrival_confirmed_at) {
            $placement->arrival_confirmed_at = now();
            $placement->save();
        }

        return response()->api([
                'id' => $placement->id,
                'arrival_confirmed_at' => $placement->arrival_confirmed_at?->toIso8601String(),
            ], 200, [], 'Arrival confirmed.');
    }
}
