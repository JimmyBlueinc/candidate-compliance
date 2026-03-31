<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TimesheetRevenueService;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RevenueController extends Controller
{
    public function __construct(private TimesheetRevenueService $service)
    {
    }

    public function analytics(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $validated = $request->validate([
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date'],
            'tenant_id' => ['sometimes', 'integer'],
        ]);

        $tenantId = array_key_exists('tenant_id', $validated)
            ? (int) $validated['tenant_id']
            : $orgId;

        $data = $this->service->calculate(
            $tenantId,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null
        );

        return response()->api($data);
    }
}
