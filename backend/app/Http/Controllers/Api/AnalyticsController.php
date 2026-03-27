<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Credential;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

    public function revenue(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $totalRevenue = (float) Invoice::query()
            ->where('tenant_id', $orgId)
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        $monthly = DB::table('invoices')
            ->where('tenant_id', $orgId)
            ->where('status', '!=', 'cancelled')
            ->selectRaw("DATE_FORMAT(invoices.created_at, '%Y-%m') as month")
            ->selectRaw('SUM(total_amount) as total')
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get()
            ->map(fn ($r) => ['month' => $r->month, 'total' => round((float) $r->total, 2)])
            ->values();

        $perFacility = DB::table('invoices')
            ->where('tenant_id', $orgId)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('COALESCE(facility_id, 0) as facility_id')
            ->selectRaw("COALESCE(NULLIF(facility_name, ''), 'Unknown') as facility_name")
            ->selectRaw('SUM(total_amount) as total')
            ->groupBy('facility_id', 'facility_name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'facility_id' => (int) $r->facility_id,
                'facility_name' => (string) $r->facility_name,
                'total' => round((float) $r->total, 2),
            ])
            ->values();

        return response()->json([
            'data' => [
                'total_revenue' => round($totalRevenue, 2),
                'monthly_revenue' => $monthly,
                'revenue_per_facility' => $perFacility,
                'currency' => 'USD',
            ],
        ]);
    }

    public function facilityPerformance(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $activeAssignments = DB::table('assignments')
            ->where('assignments.tenant_id', $orgId)
            ->where('assignments.status', 'active')
            ->selectRaw('COALESCE(assignments.facility_id, 0) as facility_id')
            ->selectRaw('COUNT(*) as active_assignments')
            ->groupBy('facility_id')
            ->get()
            ->keyBy(fn ($r) => (string) $r->facility_id);

        $billing = DB::table('invoices')
            ->where('invoices.tenant_id', $orgId)
            ->where('invoices.status', '!=', 'cancelled')
            ->selectRaw('COALESCE(invoices.facility_id, 0) as facility_id')
            ->selectRaw('SUM(invoices.total_amount) as total_billing')
            ->groupBy('facility_id')
            ->get()
            ->keyBy(fn ($r) => (string) $r->facility_id);

        $payments = DB::table('payments')
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->where('payments.tenant_id', $orgId)
            ->where('invoices.tenant_id', $orgId)
            ->where('invoices.status', '!=', 'cancelled')
            ->selectRaw('COALESCE(invoices.facility_id, 0) as facility_id')
            ->selectRaw('SUM(payments.amount) as total_collected')
            ->groupBy('facility_id')
            ->get()
            ->keyBy(fn ($r) => (string) $r->facility_id);

        $facilityIds = collect([$activeAssignments, $billing, $payments])
            ->flatMap(fn ($m) => $m->keys())
            ->unique()
            ->values()
            ->map(fn ($id) => (int) $id);

        $facilityNames = DB::table('facilities')
            ->where('organization_id', $orgId)
            ->whereIn('id', $facilityIds->filter(fn ($id) => $id !== 0)->values())
            ->pluck('name', 'id');

        $rows = $facilityIds->map(function (int $fid) use ($activeAssignments, $billing, $payments, $facilityNames) {
            $a = $activeAssignments->get((string) $fid);
            $b = $billing->get((string) $fid);
            $p = $payments->get((string) $fid);

            $totalBilling = (float) ($b->total_billing ?? 0);
            $totalCollected = (float) ($p->total_collected ?? 0);

            return [
                'facility_id' => $fid,
                'facility_name' => $fid === 0 ? 'Unknown' : (string) ($facilityNames[$fid] ?? 'Unknown'),
                'active_assignments' => (int) ($a->active_assignments ?? 0),
                'total_billing' => round($totalBilling, 2),
                'outstanding_balance' => round(max(0, $totalBilling - $totalCollected), 2),
            ];
        })->sortByDesc('total_billing')->values();

        return response()->json([
            'data' => [
                'facilities' => $rows,
                'currency' => 'USD',
            ],
        ]);
    }

    public function recruiterPerformance(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        if (!Schema::hasColumn('placements', 'submission_id')) {
            return response()->json([
                'message' => 'Schema missing placements.submission_id. Add the column and backfill it to enable correct recruiter performance analytics.',
            ], 501);
        }

        $placementCounts = DB::table('placements')
            ->where('tenant_id', $orgId)
            ->whereNotNull('recruiter_id')
            ->selectRaw('recruiter_id, COUNT(*) as placements_count')
            ->groupBy('recruiter_id')
            ->get()
            ->keyBy('recruiter_id');


        $placementSubmissionRows = DB::table('placements')
            ->join('submissions', function ($join) {
                $join->on('submissions.id', '=', 'placements.submission_id')
                    ->on('submissions.tenant_id', '=', 'placements.tenant_id');
            })
            ->where('placements.tenant_id', $orgId)
            ->where('submissions.tenant_id', $orgId)
            ->whereNotNull('placements.recruiter_id')
            ->select([
                'placements.recruiter_id',
                'placements.created_at as placement_created_at',
                'submissions.created_at as submission_created_at',
            ])
            ->get();

        $submissionsCountByRecruiter = [];
        $diffDaysByRecruiter = [];

        foreach ($placementSubmissionRows as $row) {
            $rid = (int) $row->recruiter_id;
            $submissionsCountByRecruiter[$rid] = ($submissionsCountByRecruiter[$rid] ?? 0) + 1;

            if ($row->submission_created_at && $row->placement_created_at) {
                $submittedAt = Carbon::parse($row->submission_created_at);
                $placedAt = Carbon::parse($row->placement_created_at);
                $diffDaysByRecruiter[$rid][] = max(0, $submittedAt->diffInDays($placedAt, false));
            }
        }

        $recruiterIds = collect([$placementCounts->keys(), array_keys($submissionsCountByRecruiter)])
            ->flatten()
            ->unique()
            ->values()
            ->map(fn ($id) => (int) $id);

        $names = User::query()
            ->where('organization_id', $orgId)
            ->whereIn('id', $recruiterIds)
            ->pluck('name', 'id');

        $rows = $recruiterIds->map(function (int $rid) use ($placementCounts, $submissionsCountByRecruiter, $diffDaysByRecruiter, $names) {
            $placements = (int) (($placementCounts->get($rid)->placements_count ?? 0));
            $submissions = (int) ($submissionsCountByRecruiter[$rid] ?? 0);

            $diffs = $diffDaysByRecruiter[$rid] ?? [];
            $avg = count($diffs) > 0 ? (array_sum($diffs) / count($diffs)) : 0.0;

            return [
                'recruiter_id' => $rid,
                'recruiter_name' => (string) ($names[$rid] ?? 'Unknown'),
                'submissions_count' => $submissions,
                'placements_count' => $placements,
                'avg_submission_to_placement_days' => round($avg, 1),
            ];
        })->sortByDesc('placements_count')->values();

        return response()->json([
            'data' => [
                'recruiters' => $rows,
            ],
        ]);
    }

    public function jobFillTime(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $rows = DB::table('job_orders')
            ->join('placements', function ($join) {
                $join->on('placements.job_order_id', '=', 'job_orders.id')
                    ->on('placements.tenant_id', '=', 'job_orders.tenant_id');
            })
            ->where('job_orders.tenant_id', $orgId)
            ->where('job_orders.status', 'filled')
            ->select([
                'job_orders.id as job_order_id',
                'job_orders.created_at as job_created_at',
                DB::raw('MIN(placements.created_at) as first_placement_created_at'),
            ])
            ->groupBy('job_orders.id', 'job_orders.created_at')
            ->get();

        $diffs = [];
        foreach ($rows as $row) {
            if (!$row->job_created_at || !$row->first_placement_created_at) {
                continue;
            }
            $jobCreated = Carbon::parse($row->job_created_at);
            $placedAt = Carbon::parse($row->first_placement_created_at);
            $diffs[] = max(0, $jobCreated->diffInDays($placedAt, false));
        }

        $avg = count($diffs) > 0 ? (array_sum($diffs) / count($diffs)) : 0.0;

        return response()->json([
            'data' => [
                'avg_job_fill_time_days' => round($avg, 1),
                'jobs_count' => count($diffs),
            ],
        ]);
    }
}