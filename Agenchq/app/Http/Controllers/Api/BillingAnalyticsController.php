<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AccountsReceivableService;
use App\Services\FinancialSummaryService;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingAnalyticsController extends Controller
{
    public function __construct(
        private FinancialSummaryService $summaryService,
        private AccountsReceivableService $agingService
    ) {}

    /**
     * Get combined billing and AR analytics.
     */
    public function index(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $summary = $this->summaryService->getSummary($orgId);
        $aging = $this->agingService->getAgingSummary($orgId);

        return response()->json([
            'data' => [
                'summary' => $summary,
                'aging' => $aging,
            ],
        ]);
    }
}
