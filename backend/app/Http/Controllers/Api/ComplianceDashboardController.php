<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Services\ComplianceService;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplianceDashboardController extends Controller
{
    public function index(Request $request, ComplianceService $complianceService): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $limit = (int) $request->integer('limit', 200);
        if ($limit < 1) {
            $limit = 200;
        }
        if ($limit > 500) {
            $limit = 500;
        }

        $candidates = Candidate::query()
            ->where('tenant_id', $orgId)
            ->orderByDesc('last_applied_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'first_name', 'last_name', 'name', 'email']);

        $blocked = [];
        $pending = [];
        $expiringSoon = [];
        $readyCount = 0;

        foreach ($candidates as $c) {
            $eval = $complianceService->evaluateWorkerCompliance($orgId, (int) $c->id, null);
            $global = $eval['global'] ?? [];
            $status = (string) ($eval['status'] ?? ($global['status'] ?? 'ready'));

            $missing = $global['missing'] ?? [];
            $expired = $global['expired'] ?? [];
            $pendingList = $global['pending'] ?? [];
            $soon = $global['expiring_soon'] ?? [];

            $reason = null;
            $reasonType = null;

            if (is_array($missing) && count($missing) > 0) {
                $reason = $missing[0];
                $reasonType = 'missing';
            } elseif (is_array($expired) && count($expired) > 0) {
                $reason = $expired[0];
                $reasonType = 'expired';
            } elseif (is_array($pendingList) && count($pendingList) > 0) {
                $reason = $pendingList[0];
                $reasonType = 'pending';
            }

            $row = [
                'candidate' => [
                    'id' => $c->id,
                    'name' => $c->name ?: trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? '')),
                    'email' => $c->email,
                ],
                'status' => $status,
                'reason_type' => $reasonType,
                'reason' => $reason,
                'counts' => [
                    'missing' => is_array($missing) ? count($missing) : 0,
                    'expired' => is_array($expired) ? count($expired) : 0,
                    'pending' => is_array($pendingList) ? count($pendingList) : 0,
                    'expiring_soon' => is_array($soon) ? count($soon) : 0,
                ],
            ];

            if ($status === 'blocked') {
                $blocked[] = $row;
            } elseif ($status === 'pending') {
                $pending[] = $row;
            } else {
                $readyCount++;
            }

            if (is_array($soon) && count($soon) > 0) {
                $expiringSoon[] = $row + ['expiring_soon' => $soon];
            }
        }

        return response()->api([
            'summary' => [
                'blocked_count' => count($blocked),
                'pending_count' => count($pending),
                'expiring_soon_count' => count($expiringSoon),
                'ready_count' => $readyCount,
            ],
            'blocked' => $blocked,
            'pending_verification' => $pending,
            'expiring_soon' => $expiringSoon,
        ]);
    }
}
