<?php

namespace App\Http\Controllers\Api;

use App\Events\TimesheetApproved;
use App\Events\TimesheetSubmitted;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Candidate;
use App\Models\Scopes\TenantScope;
use App\Models\Timesheet;
use App\Services\InvoiceGenerationService;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TimesheetController extends Controller
{
    public function __construct(
        private InvoiceGenerationService $invoiceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || (string) ($user->role ?? '') !== 'candidate') {
            return response()->json(['message' => 'Unauthorized. Candidate access required.'], 403);
        }

        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $candidate = Candidate::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $orgId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->first();

        if (!$candidate) {
            return response()->json(['message' => 'Candidate profile not found.'], 404);
        }

        $timesheets = Timesheet::query()
            ->with([
                'assignment:id,tenant_id,candidate_id,job_order_id,status,facility_name',
                'entries:id,timesheet_id,work_date,hours_worked,overtime_hours,notes',
            ])
            ->where('tenant_id', $orgId)
            ->where('candidate_id', $candidate->id)
            ->orderByDesc('week_start_date')
            ->limit(52)
            ->get();

        $data = $timesheets->map(function (Timesheet $t) {
            return [
                'id' => $t->id,
                'assignment_id' => $t->assignment_id,
                'candidate_id' => $t->candidate_id,
                'week_start_date' => $t->week_start_date?->format('Y-m-d'),
                'status' => $t->status,
                'submitted_at' => $t->submitted_at?->toIso8601String(),
                'approved_at' => $t->approved_at?->toIso8601String(),
                'entries' => $t->entries?->map(function ($e) {
                    return [
                        'id' => $e->id,
                        'work_date' => $e->work_date?->format('Y-m-d'),
                        'hours_worked' => $e->hours_worked,
                        'overtime_hours' => $e->overtime_hours,
                        'notes' => $e->notes,
                    ];
                })->values(),
            ];
        });

        return response()->api($data);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || (string) ($user->role ?? '') !== 'candidate') {
            return response()->json(['message' => 'Unauthorized. Candidate access required.'], 403);
        }

        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $validated = $request->validate([
            'assignment_id' => ['required', 'integer'],
            'week_start_date' => ['required', 'date'],
        ]);

        $candidate = Candidate::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $orgId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->first();

        if (!$candidate) {
            return response()->json(['message' => 'Candidate profile not found.'], 404);
        }

        $assignment = Assignment::query()
            ->where('tenant_id', $orgId)
            ->where('id', (int) $validated['assignment_id'])
            ->first();

        if (!$assignment || (int) $assignment->candidate_id !== (int) $candidate->id) {
            return response()->json(['message' => 'Assignment not found.'], 404);
        }

        if ((string) $assignment->status !== 'active') {
            return response()->json(['message' => 'Timesheets can only be created for active assignments.'], 422);
        }

        $timesheet = Timesheet::firstOrCreate([
            'tenant_id' => $orgId,
            'assignment_id' => $assignment->id,
            'week_start_date' => $validated['week_start_date'],
        ], [
            'candidate_id' => $candidate->id,
            'status' => 'draft',
        ]);

        return response()->api([
            'id' => $timesheet->id,
            'assignment_id' => $timesheet->assignment_id,
            'candidate_id' => $timesheet->candidate_id,
            'week_start_date' => $timesheet->week_start_date?->format('Y-m-d'),
            'status' => $timesheet->status,
            'submitted_at' => $timesheet->submitted_at?->toIso8601String(),
            'approved_at' => $timesheet->approved_at?->toIso8601String(),
        ], 201);
    }

    public function submit(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || (string) ($user->role ?? '') !== 'candidate') {
            return response()->json(['message' => 'Unauthorized. Candidate access required.'], 403);
        }

        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $candidate = Candidate::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $orgId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->first();

        if (!$candidate) {
            return response()->json(['message' => 'Candidate profile not found.'], 404);
        }

        $timesheet = Timesheet::query()
            ->where('tenant_id', $orgId)
            ->where('candidate_id', $candidate->id)
            ->find($id);

        if (!$timesheet) {
            return response()->json(['message' => 'Timesheet not found.'], 404);
        }

        if (!in_array((string) $timesheet->status, ['draft', 'rejected'], true)) {
            return response()->json(['message' => 'Timesheet cannot be submitted in its current status.'], 422);
        }

        $timesheet->status = 'submitted';
        $timesheet->submitted_at = now();
        $timesheet->facility_approved_at = null;
        $timesheet->facility_approved_by_user_id = null;
        $timesheet->agency_approved_at = null;
        $timesheet->agency_approved_by_user_id = null;
        $timesheet->approved_at = null;
        $timesheet->rejected_at = null;
        $timesheet->rejected_by_user_id = null;
        $timesheet->rejection_reason = null;
        $timesheet->save();

        TimesheetSubmitted::dispatch($timesheet->loadMissing(['assignment', 'candidate']), $orgId, $user);

        return response()->api([
            'id' => $timesheet->id,
            'status' => $timesheet->status,
            'submitted_at' => $timesheet->submitted_at?->toIso8601String(),
        ]);
    }

    public function pending(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $timesheets = Timesheet::query()
            ->with([
                'candidate:id,name,first_name,last_name,email',
                'assignment:id,tenant_id,candidate_id,job_order_id,status,facility_name',
                'entries:id,timesheet_id,work_date,hours_worked,overtime_hours,notes',
            ])
            ->where('tenant_id', $orgId)
            ->where('status', 'facility_approved')
            ->orderByDesc('facility_approved_at')
            ->limit(200)
            ->get();

        $data = $timesheets->map(function (Timesheet $t) {
            $daily = array_fill(0, 7, 0.0);
            $notes = null;
            $total = 0.0;

            foreach ($t->entries as $e) {
                $hours = (float) ($e->hours_worked ?? 0) + (float) ($e->overtime_hours ?? 0);
                $total += $hours;

                $idx = null;
                if ($t->week_start_date && $e->work_date) {
                    $idx = (int) $t->week_start_date->diffInDays($e->work_date, false);
                }

                if ($idx !== null && $idx >= 0 && $idx <= 6) {
                    $daily[$idx] = round(((float) $daily[$idx]) + $hours, 2);
                }

                if (!$notes && (string) ($e->notes ?? '') !== '') {
                    $notes = (string) $e->notes;
                }
            }

            return [
                'id' => $t->id,
                'assignment_id' => $t->assignment_id,
                'candidate' => $t->candidate ? [
                    'id' => $t->candidate->id,
                    'name' => $t->candidate->name,
                    'first_name' => $t->candidate->first_name,
                    'last_name' => $t->candidate->last_name,
                    'email' => $t->candidate->email,
                ] : null,
                'facility' => (string) ($t->assignment?->facility_name ?? ''),
                'week_start' => $t->week_start_date?->format('Y-m-d'),
                'total_hours' => round($total, 2),
                'daily_hours' => $daily,
                'notes' => $notes,
                'status' => $t->status,
                'submitted_at' => $t->submitted_at?->toIso8601String(),
                'facility_approved_at' => $t->facility_approved_at?->toIso8601String(),
                'entries' => $t->entries?->map(function ($e) {
                    return [
                        'id' => $e->id,
                        'work_date' => $e->work_date?->format('Y-m-d'),
                        'hours_worked' => $e->hours_worked,
                        'overtime_hours' => $e->overtime_hours,
                        'notes' => $e->notes,
                    ];
                })->values(),
            ];
        });

        return response()->api($data);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $timesheet = Timesheet::query()
            ->where('tenant_id', $orgId)
            ->find($id);

        if (!$timesheet) {
            return response()->json(['message' => 'Timesheet not found.'], 404);
        }

        if ((string) $timesheet->status !== 'facility_approved') {
            return response()->json(['message' => 'Only facility-approved timesheets can be approved by the agency.'], 422);
        }

        $timesheet->status = 'agency_approved';
        $timesheet->agency_approved_at = now();
        $timesheet->agency_approved_by_user_id = $request->user()?->id;
        // Legacy field retained for compatibility
        $timesheet->approved_at = $timesheet->agency_approved_at;
        $timesheet->rejected_at = null;
        $timesheet->rejected_by_user_id = null;
        $timesheet->rejection_reason = null;
        $timesheet->save();

        TimesheetApproved::dispatch($timesheet, $orgId, $request->user());

        return response()->api([
            'id' => $timesheet->id,
            'status' => $timesheet->status,
            'agency_approved_at' => $timesheet->agency_approved_at?->toIso8601String(),
        ]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $timesheet = Timesheet::query()
            ->where('tenant_id', $orgId)
            ->find($id);

        if (!$timesheet) {
            return response()->json(['message' => 'Timesheet not found.'], 404);
        }

        if ((string) $timesheet->status !== 'facility_approved') {
            return response()->json(['message' => 'Only facility-approved timesheets can be rejected by the agency.'], 422);
        }

        $timesheet->status = 'rejected';
        $timesheet->agency_approved_at = null;
        $timesheet->agency_approved_by_user_id = null;
        $timesheet->approved_at = null;
        $timesheet->rejected_at = now();
        $timesheet->rejected_by_user_id = $request->user()?->id;
        $timesheet->rejection_reason = $validated['reason'];
        $timesheet->save();

        return response()->api([
            'id' => $timesheet->id,
            'status' => $timesheet->status,
        ]);
    }
}
