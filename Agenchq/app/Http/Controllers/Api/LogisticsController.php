<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HousingRecord;
use App\Models\Placement;
use App\Models\TravelLog;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogisticsController extends Controller
{
    public function show(Request $request, int $placementId): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $placement = Placement::query()
            ->with([
                'candidate:id,first_name,last_name,name,email,specialty',
                'jobOrder:id,title,facility_name,specialty',
                'housingRecord:id,tenant_id,placement_id,address,landlord_contact,lease_start,lease_end',
                'travelLogs:id,tenant_id,placement_id,type,details,confirmation_number,start_date,end_date',
            ])
            ->where('tenant_id', $orgId)
            ->where('id', $placementId)
            ->firstOrFail();

        return response()->api([
            'data' => [
                'placement' => [
                    'id' => $placement->id,
                    'stage' => $placement->stage,
                    'start_date' => $placement->start_date?->format('Y-m-d'),
                    'end_date' => $placement->end_date?->format('Y-m-d'),
                    'arrival_confirmed_at' => $placement->arrival_confirmed_at?->toIso8601String(),
                ],
                'candidate' => $placement->candidate ? [
                    'id' => $placement->candidate->id,
                    'name' => $placement->candidate->name,
                    'email' => $placement->candidate->email,
                    'specialty' => $placement->candidate->specialty,
                ] : null,
                'job_order' => $placement->jobOrder ? [
                    'id' => $placement->jobOrder->id,
                    'title' => $placement->jobOrder->title,
                    'facility_name' => $placement->jobOrder->facility_name,
                    'specialty' => $placement->jobOrder->specialty,
                ] : null,
                'housing' => $placement->housingRecord,
                'travel' => $placement->travelLogs,
            ],
        ]);
    }

    public function upsertHousing(Request $request, int $placementId): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $validated = $request->validate([
            'address' => ['nullable', 'string', 'max:255'],
            'landlord_contact' => ['nullable', 'string', 'max:255'],
            'lease_start' => ['nullable', 'date'],
            'lease_end' => ['nullable', 'date'],
        ]);

        $placement = Placement::query()
            ->where('tenant_id', $orgId)
            ->where('id', $placementId)
            ->firstOrFail();

        $housing = HousingRecord::updateOrCreate([
            'tenant_id' => $orgId,
            'placement_id' => $placement->id,
        ], $validated);

        return response()->api($housing);
    }

    public function storeTravelLog(Request $request, int $placementId): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:flight,drive,hotel'],
            'details' => ['nullable', 'string'],
            'confirmation_number' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $placement = Placement::query()
            ->where('tenant_id', $orgId)
            ->where('id', $placementId)
            ->firstOrFail();

        $log = TravelLog::create([
            ...$validated,
            'tenant_id' => $orgId,
            'placement_id' => $placement->id,
        ]);

        return response()->api($log, 201);
    }

    public function updateTravelLog(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $validated = $request->validate([
            'type' => ['sometimes', 'string', 'in:flight,drive,hotel'],
            'details' => ['sometimes', 'nullable', 'string'],
            'confirmation_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date'],
        ]);

        $log = TravelLog::query()
            ->where('tenant_id', $orgId)
            ->where('id', $id)
            ->firstOrFail();

        $log->update($validated);

        return response()->api($log);
    }

    public function destroyTravelLog(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $log = TravelLog::query()
            ->where('tenant_id', $orgId)
            ->where('id', $id)
            ->firstOrFail();

        $log->delete();

        return response()->api(null, 200, [], 'Deleted.');
    }

    public function needsArrival(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $rows = Placement::query()
            ->with([
                'candidate:id,first_name,last_name,name,email,specialty',
                'jobOrder:id,title,facility_name,specialty',
            ])
            ->where('tenant_id', $orgId)
            ->where('stage', 'active')
            ->whereNull('arrival_confirmed_at')
            ->orderBy('start_date')
            ->limit(200)
            ->get()
            ->map(function (Placement $p) {
                return [
                    'id' => $p->id,
                    'stage' => $p->stage,
                    'start_date' => $p->start_date?->format('Y-m-d'),
                    'arrival_confirmed_at' => null,
                    'candidate' => $p->candidate ? [
                        'id' => $p->candidate->id,
                        'name' => $p->candidate->name,
                        'email' => $p->candidate->email,
                        'specialty' => $p->candidate->specialty,
                    ] : null,
                    'job_order' => $p->jobOrder ? [
                        'id' => $p->jobOrder->id,
                        'title' => $p->jobOrder->title,
                        'facility_name' => $p->jobOrder->facility_name,
                        'specialty' => $p->jobOrder->specialty,
                    ] : null,
                ];
            });

        return response()->api($rows);
    }
}
