<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Services\ComplianceService;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplianceStatusController extends Controller
{
    public function show(Request $request, int $candidateId, ComplianceService $complianceService): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $facilityId = $request->query('facility_id');
        if ($facilityId !== null && $facilityId !== '') {
            if (!is_numeric($facilityId) || (int) $facilityId < 1) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'facility_id' => ['The facility_id must be a positive integer.'],
                    ],
                ], 422);
            }
            $facilityId = (int) $facilityId;
        } else {
            $facilityId = null;
        }

        Candidate::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($candidateId);

        $payload = $complianceService->evaluateWorkerCompliance($orgId, $candidateId, $facilityId);

        return response()->api($payload);
    }
}
