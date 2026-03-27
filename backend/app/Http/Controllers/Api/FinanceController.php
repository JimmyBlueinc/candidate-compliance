<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Placement;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $active = Placement::query()
            ->with(['jobOrder:id,facility_name,bill_rate,pay_rate'])
            ->where('tenant_id', $orgId)
            ->where('stage', 'active')
            ->get();

        $offered = Placement::query()
            ->with(['jobOrder:id,facility_name,bill_rate,pay_rate'])
            ->where('tenant_id', $orgId)
            ->where('stage', 'offered')
            ->get();

        $gross = $active->sum(fn ($p) => (float) ($p->jobOrder?->bill_rate ?? 0));
        $labor = $active->sum(fn ($p) => (float) ($p->jobOrder?->pay_rate ?? 0));
        $margin = $gross - $labor;
        $marginPct = $gross > 0 ? ($margin / $gross) * 100 : 0;

        $projectedGross = $offered->sum(fn ($p) => (float) ($p->jobOrder?->bill_rate ?? 0));

        $facility = [];
        foreach ($active as $p) {
            $facilityName = (string) ($p->jobOrder?->facility_name ?? 'Unknown');
            $bill = (float) ($p->jobOrder?->bill_rate ?? 0);
            $pay = (float) ($p->jobOrder?->pay_rate ?? 0);

            if (!isset($facility[$facilityName])) {
                $facility[$facilityName] = [
                    'facility_name' => $facilityName,
                    'gross' => 0,
                    'labor' => 0,
                    'margin' => 0,
                    'count' => 0,
                ];
            }

            $facility[$facilityName]['gross'] += $bill;
            $facility[$facilityName]['labor'] += $pay;
            $facility[$facilityName]['margin'] += ($bill - $pay);
            $facility[$facilityName]['count'] += 1;
        }

        $facilityRows = array_values($facility);
        usort($facilityRows, fn ($a, $b) => ($b['margin'] <=> $a['margin']));

        return response()->json([
            'data' => [
                'totals' => [
                    'gross_revenue' => round($gross, 2),
                    'labor_cost' => round($labor, 2),
                    'margin' => round($margin, 2),
                    'margin_pct' => round($marginPct, 2),
                    'projected_revenue' => round($projectedGross, 2),
                ],
                'facility_profitability' => array_slice($facilityRows, 0, 20),
            ],
        ]);
    }
}
