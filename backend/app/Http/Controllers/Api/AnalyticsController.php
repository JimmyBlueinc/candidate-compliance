<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Credential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\Org;

class AnalyticsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $dateRange = $request->input("date_range", 30);
        $orgId = Org::id($request);

        $query = Credential::query();
        if ($user->role !== 'platform_admin' && $orgId) {
            $query->where('organization_id', $orgId);
        }
        if ($user->role === "candidate") {
            $query->where("email", $user->email);
        }

        $startDate = now()->subDays($dateRange);
        $query->where("created_at", ">=", $startDate);

        $credentials = $query->get();

        $total = $credentials->count();
        $byStatus = $credentials->groupBy("status")->map->count();
        $byType = $credentials->groupBy("credential_type")->map->count();
        $byProvince = $credentials->whereNotNull("province")->groupBy("province")->map->count();
        $bySpecialty = $credentials->whereNotNull("specialty")->groupBy("specialty")->map->count();
        $byPosition = $credentials->groupBy("position")->map->count();

        $now = now();
        $expiringNext30 = $credentials->filter(function ($cred) use ($now) {
            if (!$cred->expiry_date) return false;
            $expiry = \Carbon\Carbon::parse($cred->expiry_date);
            $in30Days = $now->copy()->addDays(30);
            return $expiry->gte($now) && $expiry->lte($in30Days);
        })->count();

        $credentialsWithExpiry = $credentials->filter(fn($c) => $c->expiry_date);
        $averageDaysToExpiry = $credentialsWithExpiry->count() > 0
            ? $credentialsWithExpiry->map(function ($cred) use ($now) {
                $expiry = \Carbon\Carbon::parse($cred->expiry_date);
                return max(0, $now->diffInDays($expiry, false));
            })->avg()
            : 0;

        return response()->json([
            "total" => $total,
            "by_status" => $byStatus,
            "by_type" => $byType,
            "by_province" => $byProvince,
            "by_specialty" => $bySpecialty,
            "by_position" => $byPosition,
            "expiring_next_30" => $expiringNext30,
            "average_days_to_expiry" => round($averageDaysToExpiry, 1),
        ]);
    }
}