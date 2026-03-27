<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Candidate;
use App\Models\Credential;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Placement;
use App\Models\Submission;
use App\Models\Timesheet;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CandidatePortalController extends Controller
{
    /**
     * Get candidate associated with the authenticated user.
     */
    private function getCandidate(Request $request, int $orgId): ?Candidate
    {
        return Candidate::query()
            ->where('tenant_id', $orgId)
            ->where('user_id', $request->user()->id)
            ->first();
    }

    public function me(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        $user = $request->user();

        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        
        $candidate = Candidate::where('tenant_id', $orgId)
            ->where('user_id', $user->id)
            ->first();

        $credentialsCount = 0;
        if ($candidate) {
            $credentialsCount = Credential::where('tenant_id', $orgId)
                ->where('candidate_id', $candidate->id)
                ->count();
        }

        return response()->api([
            'candidate' => $candidate,
            'credentials_count' => $credentialsCount,
        ]);
    }

    /**
     * Dashboard summary.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $orgId = Org::id($request);

        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $candidate = $this->getCandidate($request, $orgId);

        if (!$candidate) {
            return response()->json(['message' => 'Candidate profile not found.'], 404);
        }

        $activeAssignments = Assignment::query()
            ->where('tenant_id', $orgId)
            ->where('candidate_id', $candidate->id)
            ->where('status', 'active')
            ->count();

        $pendingTimesheets = Timesheet::query()
            ->where('tenant_id', $orgId)
            ->where('candidate_id', $candidate->id)
            ->whereIn('status', ['draft', 'rejected'])
            ->count();

        $recentPayments = Payment::query()
            ->where('tenant_id', $orgId)
            ->whereHas('invoice', function ($q) use ($candidate) {
                $q->where('candidate_id', $candidate->id);
            })
            ->orderByDesc('payment_date')
            ->limit(5)
            ->get();

        $unreadNotifications = Notification::query()
            ->where('tenant_id', $orgId)
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->api([
            'active_assignments' => $activeAssignments,
            'pending_timesheets' => $pendingTimesheets,
            'recent_payments' => $recentPayments,
            'unread_notifications' => $unreadNotifications,
        ]);
    }

    /**
     * Candidate applications (submissions).
     */
    public function applications(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        $candidate = $this->getCandidate($request, $orgId);

        if (!$candidate) {
            return response()->json(['message' => 'Candidate profile not found.'], 404);
        }

        $submissions = Submission::query()
            ->with(['jobOrder:id,title,facility_name,specialty'])
            ->where('tenant_id', $orgId)
            ->where('candidate_id', $candidate->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->api($submissions);
    }

    /**
     * Candidate placements.
     */
    public function placements(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        $candidate = $this->getCandidate($request, $orgId);

        if (!$candidate) {
            return response()->json(['message' => 'Candidate profile not found.'], 404);
        }

        $placements = Placement::query()
            ->with(['jobOrder:id,title,facility_name,specialty'])
            ->where('tenant_id', $orgId)
            ->where('candidate_id', $candidate->id)
            ->orderByDesc('updated_at')
            ->get();

        return response()->api($placements);
    }

    /**
     * Candidate timesheets.
     */
    public function timesheets(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        $candidate = $this->getCandidate($request, $orgId);

        if (!$candidate) {
            return response()->json(['message' => 'Candidate profile not found.'], 404);
        }

        $timesheets = Timesheet::query()
            ->with([
                'assignment:id,facility_name',
                'entries:id,timesheet_id,work_date,hours_worked,overtime_hours,notes',
            ])
            ->where('tenant_id', $orgId)
            ->where('candidate_id', $candidate->id)
            ->orderByDesc('week_start_date')
            ->limit(52)
            ->get();

        $data = $timesheets->map(function (Timesheet $t) {
            $daily = array_fill(0, 7, 0.0);
            $total = 0.0;
            $notes = null;

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
                'facility' => (string) ($t->assignment?->facility_name ?? ''),
                'week_start' => $t->week_start_date?->format('Y-m-d'),
                'status' => (string) $t->status,
                'total_hours' => round($total, 2),
                'daily_hours' => $daily,
                'notes' => $notes,
                'rejection_reason' => $t->rejection_reason,
            ];
        });

        return response()->api($data);
    }

    /**
     * Submit timesheet entries.
     */
    public function storeTimesheet(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        $candidate = $this->getCandidate($request, $orgId);

        if (!$candidate) {
            return response()->json(['message' => 'Candidate profile not found.'], 404);
        }

        $validated = $request->validate([
            'assignment_id' => ['required', 'integer', 'exists:assignments,id'],
            'week_start_date' => ['required', 'date'],
            'entries' => ['sometimes', 'array', 'min:1'],
            'entries.*.work_date' => ['required_with:entries', 'date'],
            'entries.*.hours_worked' => ['required_with:entries', 'numeric', 'min:0'],
            'entries.*.overtime_hours' => ['nullable', 'numeric', 'min:0'],
            'entries.*.notes' => ['nullable', 'string', 'max:500'],
            'daily_hours' => ['sometimes', 'array', 'size:7'],
            'daily_hours.*' => ['nullable', 'numeric', 'min:0'],
            'hours' => ['sometimes', 'array', 'size:7'],
            'hours.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Verify assignment ownership and status
        $assignment = Assignment::query()
            ->where('tenant_id', $orgId)
            ->where('id', $validated['assignment_id'])
            ->where('candidate_id', $candidate->id)
            ->where('status', 'active')
            ->firstOrFail();

        $timesheet = Timesheet::firstOrCreate([
            'tenant_id' => $orgId,
            'assignment_id' => $assignment->id,
            'candidate_id' => $candidate->id,
            'week_start_date' => $validated['week_start_date'],
        ], [
            'status' => 'draft',
        ]);

        if (!in_array($timesheet->status, ['draft', 'rejected'])) {
            return response()->json(['message' => 'Timesheet cannot be modified in its current status.'], 422);
        }

        $entries = $validated['entries'] ?? null;
        if (!$entries) {
            $hours = $validated['daily_hours'] ?? $validated['hours'] ?? null;
            if (!is_array($hours) || count($hours) !== 7) {
                return response()->json(['message' => 'Timesheet entries are required.'], 422);
            }

            $weekStart = \Carbon\Carbon::parse($validated['week_start_date'])->startOfDay();
            $entries = [];
            for ($i = 0; $i < 7; $i++) {
                $h = (float) ($hours[$i] ?? 0);
                if ($h <= 0) {
                    continue;
                }

                $entries[] = [
                    'work_date' => $weekStart->copy()->addDays($i)->format('Y-m-d'),
                    'hours_worked' => $h,
                    'overtime_hours' => 0,
                    'notes' => null,
                ];
            }

            if (count($entries) === 0) {
                return response()->json(['message' => 'At least one day must have hours greater than 0.'], 422);
            }
        }

        // Delete old entries and create new ones
        $timesheet->entries()->delete();
        foreach ($entries as $entry) {
            $timesheet->entries()->create($entry);
        }

        return response()->api($timesheet->load('entries'), 200, [], 'Timesheet saved successfully.');
    }

    /**
     * Payment history.
     */
    public function payments(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        $candidate = $this->getCandidate($request, $orgId);

        if (!$candidate) {
            return response()->json(['message' => 'Candidate profile not found.'], 404);
        }

        $payments = Payment::query()
            ->with(['invoice:id,week_start_date,week_end_date,total_hours'])
            ->where('tenant_id', $orgId)
            ->whereHas('invoice', function ($q) use ($candidate) {
                $q->where('candidate_id', $candidate->id);
            })
            ->orderByDesc('payment_date')
            ->get();

        return response()->api($payments);
    }
}
