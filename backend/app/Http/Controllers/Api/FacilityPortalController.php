<?php

namespace App\Http\Controllers\Api;

use App\Events\SubmissionAccepted;
use App\Events\PlacementCreated;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Invoice;
use App\Models\JobOrder;
use App\Models\Placement;
use App\Models\Shift;
use App\Models\Submission;
use App\Models\Timesheet;
use App\Services\AccountsReceivableService;
use App\Support\Org;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FacilityPortalController extends Controller
{
    /**
     * Get dashboard summary for the authenticated facility user.
     */
    public function dashboard(Request $request, AccountsReceivableService $arService): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);

        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        if (!$user?->facility_id) {
            Log::warning('Facility portal access denied: user missing facility_id', [
                'user_id' => $user?->id,
                'tenant_id' => $orgId,
            ]);

            return response()->json(['message' => 'Facility access requires an associated facility.'], 403);
        }

        $facilityId = $user->facility_id;

        $activeAssignments = Assignment::query()
            ->with(['candidate:id,name,first_name,last_name,email,specialty', 'jobOrder:id,title,specialty'])
            ->where('tenant_id', $orgId)
            ->where('facility_id', $facilityId)
            ->where('status', 'active')
            ->orderByDesc('start_date')
            ->limit(50)
            ->get();

        $activeWorkers = $activeAssignments->map(function (Assignment $a) {
            return [
                'assignment_id' => $a->id,
                'candidate' => $a->candidate ? [
                    'id' => $a->candidate->id,
                    'name' => $a->candidate->name,
                    'specialty' => $a->candidate->specialty,
                ] : null,
                'role' => (string) ($a->jobOrder?->title ?? $a->jobOrder?->specialty ?? ''),
                'status' => (string) $a->status,
                'start_date' => $a->start_date?->format('Y-m-d'),
            ];
        });

        $upcomingShifts = Shift::query()
            ->with(['assignment:id,candidate_id', 'assignment.candidate:id,name,specialty'])
            ->where('tenant_id', $orgId)
            ->where('facility_id', $facilityId)
            ->whereIn('status', ['open', 'requested', 'assigned'])
            ->where('starts_at', '>=', now()->subDays(1))
            ->orderBy('starts_at')
            ->limit(50)
            ->get()
            ->map(function (Shift $s) {
                return [
                    'id' => $s->id,
                    'candidate' => $s->assignment?->candidate ? [
                        'id' => $s->assignment->candidate->id,
                        'name' => $s->assignment->candidate->name,
                        'specialty' => $s->assignment->candidate->specialty,
                    ] : null,
                    'starts_at' => $s->starts_at?->toIso8601String(),
                    'ends_at' => $s->ends_at?->toIso8601String(),
                    'status' => (string) $s->status,
                ];
            });

        // 1. Active Assignments Count
        $activeAssignmentsCount = Assignment::query()
            ->where('tenant_id', $orgId)
            ->where('facility_id', $facilityId)
            ->where('status', 'active')
            ->count();

        // 2. Pending Timesheets Count (submitted but not yet approved/rejected by facility)
        $pendingTimesheetsCount = Timesheet::query()
            ->where('tenant_id', $orgId)
            ->whereHas('assignment', function ($q) use ($facilityId) {
                $q->where('facility_id', $facilityId);
            })
            ->where('status', 'submitted')
            ->count();

        $pendingTimesheets = Timesheet::query()
            ->with(['candidate:id,name,specialty', 'assignment:id,facility_id,facility_name'])
            ->where('tenant_id', $orgId)
            ->where('status', 'submitted')
            ->whereHas('assignment', function ($q) use ($facilityId) {
                $q->where('facility_id', $facilityId);
            })
            ->orderByDesc('submitted_at')
            ->limit(10)
            ->get()
            ->map(function (Timesheet $t) {
                return [
                    'id' => $t->id,
                    'candidate' => $t->candidate ? [
                        'id' => $t->candidate->id,
                        'name' => $t->candidate->name,
                        'specialty' => $t->candidate->specialty,
                    ] : null,
                    'week_start' => $t->week_start_date?->format('Y-m-d'),
                    'total_hours' => (float) ($t->total_hours ?? 0),
                    'status' => (string) $t->status,
                    'submitted_at' => $t->submitted_at?->toIso8601String(),
                ];
            });

        // 3. Recent Invoices (limit 5)
        $recentInvoices = Invoice::query()
            ->where('tenant_id', $orgId)
            ->where('facility_id', $facilityId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function (Invoice $i) {
                return [
                    'id' => $i->id,
                    'invoice_number' => 'INV-' . str_pad($i->id, 6, '0', STR_PAD_LEFT),
                    'billing_period' => $i->week_start_date?->format('Y-m-d') . ' to ' . $i->week_end_date?->format('Y-m-d'),
                    'amount' => $i->total_amount,
                    'status' => $i->status,
                    'created_at' => $i->created_at?->toIso8601String(),
                ];
            });

        // 4. Outstanding Balance (using AccountsReceivableService)
        // Since getAgingSummary returns by_facility keyed by name, we need to find our facility
        $facilityName = $user->facility?->name;
        $arSummary = $arService->getAgingSummary($orgId);
        $outstandingBalance = 0;

        if ($facilityName && isset($arSummary['by_facility'][$facilityName])) {
            $outstandingBalance = $arSummary['by_facility'][$facilityName]['total_ar'];
        } else {
            // Fallback: sum open invoices for this facility_id if name match fails or facility relation not loaded
            $outstandingBalance = Invoice::query()
                ->where('tenant_id', $orgId)
                ->where('facility_id', $facilityId)
                ->where('status', '!=', 'paid')
                ->sum('total_amount');
        }

        return response()->api([
            'active_assignments' => $activeAssignmentsCount,
            'pending_timesheets' => $pendingTimesheetsCount,
            'recent_invoices' => $recentInvoices,
            'outstanding_balance' => round((float) $outstandingBalance, 2),
            'active_workers' => $activeWorkers,
            'upcoming_shifts' => $upcomingShifts,
            'pending_timesheet_items' => $pendingTimesheets,
        ]);
    }

    public function workers(Request $request): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);

        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        if (!$user?->facility_id) {
            Log::warning('Facility portal access denied: user missing facility_id', [
                'user_id' => $user?->id,
                'tenant_id' => $orgId,
            ]);

            return response()->json(['message' => 'Facility access requires an associated facility.'], 403);
        }

        $facilityId = (int) $user->facility_id;
        $now = Carbon::now('UTC');
        $until = $now->copy()->addDays(14);

        $assignments = Assignment::query()
            ->with([
                'candidate:id,name,first_name,last_name,email,specialty',
                'jobOrder:id,title,specialty',
            ])
            ->where('tenant_id', $orgId)
            ->where('facility_id', $facilityId)
            ->where('status', 'active')
            ->orderByDesc('start_date')
            ->limit(200)
            ->get();

        $assignmentIds = $assignments->pluck('id')->all();

        if (count($assignmentIds) === 0) {
            return response()->api([]);
        }

        $shiftsByAssignment = Shift::query()
            ->with(['assignment:id,candidate_id'])
            ->where('tenant_id', $orgId)
            ->where('facility_id', $facilityId)
            ->whereIn('assignment_id', $assignmentIds)
            ->where('starts_at', '>=', $now)
            ->where('starts_at', '<=', $until)
            ->orderBy('starts_at')
            ->get()
            ->groupBy('assignment_id');

        $data = $assignments->map(function (Assignment $a) use ($shiftsByAssignment) {
            $shifts = ($shiftsByAssignment[$a->id] ?? collect())->take(5)->map(function (Shift $s) {
                return [
                    'id' => $s->id,
                    'starts_at' => $s->starts_at?->toIso8601String(),
                    'ends_at' => $s->ends_at?->toIso8601String(),
                    'status' => (string) $s->status,
                ];
            })->values();

            return [
                'id' => $a->id,
                'status' => (string) $a->status,
                'start_date' => $a->start_date?->format('Y-m-d'),
                'end_date' => $a->end_date?->format('Y-m-d'),
                'candidate' => $a->candidate ? [
                    'id' => $a->candidate->id,
                    'name' => $a->candidate->name,
                    'specialty' => $a->candidate->specialty,
                ] : null,
                'role' => (string) ($a->jobOrder?->title ?? $a->jobOrder?->specialty ?? ''),
                'upcoming_shifts' => $shifts,
            ];
        });

        return response()->api($data);
    }

    public function shifts(Request $request): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);

        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        if (!$user?->facility_id) {
            Log::warning('Facility portal access denied: user missing facility_id', [
                'user_id' => $user?->id,
                'tenant_id' => $orgId,
            ]);

            return response()->json(['message' => 'Facility access requires an associated facility.'], 403);
        }

        $facilityId = (int) $user->facility_id;

        $query = Shift::query()
            ->with(['assignment:id,candidate_id', 'assignment.candidate:id,name,specialty'])
            ->where('tenant_id', $orgId)
            ->where('facility_id', $facilityId);

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('from')) {
            try {
                $query->where('starts_at', '>=', Carbon::parse($request->input('from'))->utc());
            } catch (\Throwable) {
                return response()->json(['message' => 'Invalid from date.'], 422);
            }
        }

        if ($request->filled('to')) {
            try {
                $query->where('starts_at', '<=', Carbon::parse($request->input('to'))->utc());
            } catch (\Throwable) {
                return response()->json(['message' => 'Invalid to date.'], 422);
            }
        }

        $rows = $query->orderByDesc('starts_at')->limit(500)->get();

        $data = $rows->map(function (Shift $s) {
            return [
                'id' => $s->id,
                'candidate' => $s->assignment?->candidate ? [
                    'id' => $s->assignment->candidate->id,
                    'name' => $s->assignment->candidate->name,
                    'specialty' => $s->assignment->candidate->specialty,
                ] : null,
                'starts_at' => $s->starts_at?->toIso8601String(),
                'ends_at' => $s->ends_at?->toIso8601String(),
                'status' => (string) $s->status,
            ];
        });

        return response()->api($data);
    }

    /**
     * Get assignments for the authenticated facility user.
     */
    /**
     * Get assignments for the authenticated facility user.
     */
    public function placements(Request $request): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);

        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        // Check if user has a facility_id assigned
        if (!$user?->facility_id) {
            Log::warning('Facility portal access denied: user missing facility_id', [
                'user_id' => $user?->id,
                'tenant_id' => $orgId,
            ]);

            return response()->json(['message' => 'Facility access requires an associated facility.'], 403);
        }

        $assignments = Assignment::query()
            ->with([
                'candidate:id,first_name,last_name,name,email,specialty',
                'jobOrder:id,title,specialty,bill_rate'
            ])
            ->where('tenant_id', $orgId)
            ->where('facility_id', $user->facility_id)
            ->orderByDesc('start_date')
            ->get()
            ->map(function (Assignment $a) {
                return [
                    'id' => $a->id,
                    'start_date' => $a->start_date?->format('Y-m-d'),
                    'end_date' => $a->end_date?->format('Y-m-d'),
                    'status' => $a->status,
                    'candidate' => $a->candidate ? [
                        'id' => $a->candidate->id,
                        'name' => $a->candidate->name,
                        'specialty' => $a->candidate->specialty,
                    ] : null,
                    'job_order' => $a->jobOrder ? [
                        'id' => $a->jobOrder->id,
                        'title' => $a->jobOrder->title,
                        'specialty' => $a->jobOrder->specialty,
                    ] : null,
                    // Note: 'department' is requested but not in the current schema. 
                    // Mapping specialty or title as a placeholder or returning null.
                    'department' => $a->jobOrder?->specialty, 
                ];
            });

        return response()->api($assignments);
    }

    /**
     * Get submissions for the authenticated facility user.
     */
    public function submissions(Request $request): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);

        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        if (!$user?->facility_id) {
            Log::warning('Facility portal access denied: user missing facility_id', [
                'user_id' => $user?->id,
                'tenant_id' => $orgId,
            ]);

            return response()->json(['message' => 'Facility access requires an associated facility.'], 403);
        }

        $submissions = Submission::query()
            ->with([
                'candidate:id,first_name,last_name,name,email,specialty',
                'jobOrder:id,title,specialty'
            ])
            ->where('tenant_id', $orgId)
            ->whereHas('jobOrder', function ($q) use ($user) {
                $q->where('facility_id', $user->facility_id);
            })
            ->orderByDesc('created_at')
            ->get();

        return response()->api($submissions);
    }

    /**
     * Get submission details.
     */
    public function submissionShow(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);

        $submission = Submission::query()
            ->with([
                'candidate',
                'jobOrder:id,title,specialty,facility_name'
            ])
            ->where('tenant_id', $orgId)
            ->whereHas('jobOrder', function ($q) use ($user) {
                $q->where('facility_id', $user->facility_id);
            })
            ->findOrFail($id);

        return response()->api($submission);
    }

    /**
     * Accept a candidate submission.
     */
    public function acceptSubmission(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);

        $submission = Submission::query()
            ->where('tenant_id', $orgId)
            ->whereHas('jobOrder', function ($q) use ($user) {
                $q->where('facility_id', $user->facility_id);
            })
            ->findOrFail($id);

        if ($submission->status !== 'pending') {
            return response()->json(['message' => 'Only pending submissions can be accepted.'], 422);
        }

        $submission->update(['status' => 'accepted']);

        // Trigger placement creation
        $placement = Placement::firstOrCreate([
            'tenant_id' => $orgId,
            'candidate_id' => $submission->candidate_id,
            'job_order_id' => $submission->job_order_id,
        ], [
            'submission_id' => $submission->id,
            'stage' => 'offered', // Start at offered stage when accepted by facility
        ]);

        if (!$placement->submission_id) {
            $placement->submission_id = $submission->id;
            $placement->save();
        }

        if ($placement->wasRecentlyCreated) {
            PlacementCreated::dispatch($placement, $orgId, $user);
        }

        SubmissionAccepted::dispatch($submission->loadMissing(['candidate', 'jobOrder']), $placement, $orgId, $user);

        return response()->api([
            'submission' => $submission,
            'placement_id' => $placement->id,
        ], 200, [], 'Submission accepted and placement created.');
    }

    /**
     * Reject a candidate submission.
     */
    public function rejectSubmission(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $submission = Submission::query()
            ->where('tenant_id', $orgId)
            ->whereHas('jobOrder', function ($q) use ($user) {
                $q->where('facility_id', $user->facility_id);
            })
            ->findOrFail($id);

        if ($submission->status !== 'pending') {
            return response()->json(['message' => 'Only pending submissions can be rejected.'], 422);
        }

        $submission->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['reason'],
        ]);

        return response()->api($submission, 200, [], 'Submission rejected.');
    }

    /**
     * Get pending timesheets for the authenticated facility user.
     */
    public function pendingTimesheets(Request $request): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);

        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        if (!$user?->facility_id) {
            Log::warning('Facility portal access denied: user missing facility_id', [
                'user_id' => $user?->id,
                'tenant_id' => $orgId,
            ]);

            return response()->json(['message' => 'Facility access requires an associated facility.'], 403);
        }

        $timesheets = Timesheet::query()
            ->with([
                'candidate:id,name,first_name,last_name,email,specialty',
                'assignment:id,tenant_id,facility_id,facility_name',
                'entries:id,timesheet_id,work_date,hours_worked,overtime_hours,notes',
            ])
            ->where('tenant_id', $orgId)
            ->where('status', 'submitted')
            ->whereHas('assignment', function ($q) use ($user) {
                $q->where('facility_id', $user->facility_id);
            })
            ->orderByDesc('submitted_at')
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
                    'specialty' => $t->candidate->specialty,
                ] : null,
                'facility' => (string) ($t->assignment?->facility_name ?? ''),
                'week_start' => $t->week_start_date?->format('Y-m-d'),
                'total_hours' => round($total, 2),
                'daily_hours' => $daily,
                'notes' => $notes,
                'status' => (string) $t->status,
                'submitted_at' => $t->submitted_at?->toIso8601String(),
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

    /**
     * Approve a timesheet (supervisor approval).
     */
    public function approveTimesheet(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);

        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        if (!$user?->facility_id) {
            Log::warning('Facility portal access denied: user missing facility_id', [
                'user_id' => $user?->id,
                'tenant_id' => $orgId,
                'timesheet_id' => $id,
            ]);

            return response()->json(['message' => 'Facility access requires an associated facility.'], 403);
        }

        $timesheet = Timesheet::query()
            ->with(['assignment:id,tenant_id,facility_id'])
            ->where('tenant_id', $orgId)
            ->findOrFail($id);

        if ((int) ($timesheet->assignment?->facility_id ?? 0) !== (int) $user->facility_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ((string) $timesheet->status !== 'submitted') {
            return response()->json(['message' => 'Only submitted timesheets can be approved by the facility.'], 422);
        }

        $timesheet->status = 'facility_approved';
        $timesheet->facility_approved_at = now();
        $timesheet->facility_approved_by_user_id = $user->id;
        $timesheet->rejected_at = null;
        $timesheet->rejected_by_user_id = null;
        $timesheet->rejection_reason = null;
        $timesheet->save();

        return response()->api([
            'id' => $timesheet->id,
            'status' => $timesheet->status,
            'facility_approved_at' => $timesheet->facility_approved_at?->toIso8601String(),
        ]);
    }

    /**
     * Reject a timesheet.
     */
    public function rejectTimesheet(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);

        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        if (!$user?->facility_id) {
            Log::warning('Facility portal access denied: user missing facility_id', [
                'user_id' => $user?->id,
                'tenant_id' => $orgId,
                'timesheet_id' => $id,
            ]);

            return response()->json(['message' => 'Facility access requires an associated facility.'], 403);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $timesheet = Timesheet::query()
            ->with(['assignment:id,tenant_id,facility_id'])
            ->where('tenant_id', $orgId)
            ->findOrFail($id);

        if ((int) ($timesheet->assignment?->facility_id ?? 0) !== (int) $user->facility_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ((string) $timesheet->status !== 'submitted') {
            return response()->json(['message' => 'Only submitted timesheets can be rejected by the facility.'], 422);
        }

        $timesheet->status = 'rejected';
        $timesheet->facility_approved_at = null;
        $timesheet->facility_approved_by_user_id = null;
        $timesheet->agency_approved_at = null;
        $timesheet->agency_approved_by_user_id = null;
        $timesheet->approved_at = null;
        $timesheet->rejected_at = now();
        $timesheet->rejected_by_user_id = $user->id;
        $timesheet->rejection_reason = $validated['reason'];
        $timesheet->save();

        return response()->api([
            'id' => $timesheet->id,
            'status' => $timesheet->status,
            'rejected_at' => $timesheet->rejected_at?->toIso8601String(),
        ]);
    }

    /**
     * Get invoices for the authenticated facility user.
     */
    public function invoices(Request $request): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);

        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        if (!$user?->facility_id) {
            Log::warning('Facility portal access denied: user missing facility_id', [
                'user_id' => $user?->id,
                'tenant_id' => $orgId,
            ]);

            return response()->json(['message' => 'Facility access requires an associated facility.'], 403);
        }

        $invoices = Invoice::query()
            ->where('tenant_id', $orgId)
            ->where('facility_id', $user->facility_id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Invoice $i) {
                return [
                    'id' => $i->id,
                    'invoice_number' => 'INV-' . str_pad($i->id, 6, '0', STR_PAD_LEFT),
                    'billing_period' => $i->week_start_date?->format('Y-m-d') . ' to ' . $i->week_end_date?->format('Y-m-d'),
                    'amount' => $i->total_amount,
                    'status' => $i->status,
                    'created_at' => $i->created_at?->toIso8601String(),
                ];
            });

        return response()->api($invoices);
    }

    /**
     * Get invoice details.
     */
    public function invoiceShow(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);

        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        if (!$user?->facility_id) {
            Log::warning('Facility portal access denied: user missing facility_id', [
                'user_id' => $user?->id,
                'tenant_id' => $orgId,
                'invoice_id' => $id,
            ]);

            return response()->json(['message' => 'Facility access requires an associated facility.'], 403);
        }

        $invoice = Invoice::query()
            ->with([
                'assignment:id,candidate_id,job_order_id,start_date,end_date',
                'assignment.candidate:id,name,first_name,last_name,email',
                'assignment.jobOrder:id,title,specialty',
            ])
            ->where('tenant_id', $orgId)
            ->where('facility_id', $user->facility_id)
            ->findOrFail($id);

        // Fetch related timesheets based on assignment and week_start_date
        $timesheets = Timesheet::query()
            ->with(['entries'])
            ->where('tenant_id', $orgId)
            ->where('assignment_id', $invoice->assignment_id)
            ->where('week_start_date', $invoice->week_start_date)
            ->whereHas('assignment', function ($q) use ($user) {
                $q->where('facility_id', $user->facility_id);
            })
            ->get();

        return response()->api([
            'id' => $invoice->id,
            'invoice_number' => 'INV-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT),
            'billing_period' => $invoice->week_start_date?->format('Y-m-d') . ' to ' . $invoice->week_end_date?->format('Y-m-d'),
            'amount' => $invoice->total_amount,
            'status' => $invoice->status,
            'created_at' => $invoice->created_at?->toIso8601String(),
            'assignment' => $invoice->assignment,
            'timesheets' => $timesheets,
        ]);
    }
}
