<?php

namespace App\Services;

use App\Events\ShiftCancelled;
use App\Events\ShiftCompleted;
use App\Events\ShiftCreated;
use App\Events\ShiftRequestWithdrawn;
use App\Events\ShiftRequested;
use App\Models\Assignment;
use App\Models\Facility;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\ShiftRequest;
use App\Models\ShiftTemplate;
use App\Models\Timesheet;
use App\Models\TimesheetEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftService
{
    public function createShiftFromTemplate(int $tenantId, int $shiftTemplateId, string $date, int $assignmentId, ?int $facilityId = null, ?User $actor = null): Shift
    {
        $template = ShiftTemplate::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($shiftTemplateId);

        $assignment = Assignment::query()
            ->where('tenant_id', $tenantId)
            ->find($assignmentId);

        if (!$assignment) {
            throw ValidationException::withMessages([
                'assignment_id' => ['Assignment not found.'],
            ]);
        }

        if ((string) $assignment->status !== 'active') {
            throw ValidationException::withMessages([
                'assignment_id' => ['Shifts can only be scheduled for active assignments.'],
            ]);
        }

        $assignmentFacilityId = (int) ($assignment->facility_id ?? 0);
        if ($assignmentFacilityId <= 0) {
            throw ValidationException::withMessages([
                'assignment_id' => ['Assignment is missing a facility.'],
            ]);
        }

        $resolvedFacilityId = (int) ($facilityId ?? $template->facility_id ?? 0);
        if ($resolvedFacilityId <= 0) {
            throw ValidationException::withMessages([
                'facility_id' => ['Facility is required to create a shift.'],
            ]);
        }

        if ((int) $resolvedFacilityId !== (int) $assignmentFacilityId) {
            throw ValidationException::withMessages([
                'facility_id' => ['Shift facility must match the assignment facility.'],
            ]);
        }

        $templateFacilityId = (int) ($template->facility_id ?? 0);
        if ($templateFacilityId > 0 && (int) $templateFacilityId !== (int) $assignmentFacilityId) {
            throw ValidationException::withMessages([
                'shift_template_id' => ['Shift template facility must match the assignment facility.'],
            ]);
        }

        $facility = Facility::query()
            ->where('organization_id', $tenantId)
            ->find($resolvedFacilityId);

        if (!$facility) {
            throw ValidationException::withMessages([
                'facility_id' => ['Facility not found for this tenant.'],
            ]);
        }

        $tz = $template->timezone ?: 'UTC';

        $startsAt = Carbon::parse($date . ' ' . $template->start_time, $tz)->utc();
        $endsAt = Carbon::parse($date . ' ' . $template->end_time, $tz)->utc();
        if ($endsAt->lte($startsAt)) {
            $endsAt = $endsAt->addDay();
        }

        return DB::transaction(function () use ($tenantId, $template, $startsAt, $endsAt, $assignmentId, $resolvedFacilityId, $actor) {
            $shift = Shift::create([
                'tenant_id' => $tenantId,
                'shift_template_id' => $template->id,
                'assignment_id' => $assignmentId,
                'facility_id' => $resolvedFacilityId,
                'title' => $template->name,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'break_minutes' => (int) ($template->break_minutes ?? 0),
                'timezone' => $template->timezone ?? 'UTC',
                'status' => 'open',
            ]);

            ShiftCreated::dispatch($shift, $tenantId, $actor);

            return $shift;
        });
    }

    public function requestShift(int $tenantId, int $shiftId, int $candidateId, ?string $notes = null, ?User $actor = null): ShiftRequest
    {
        $shift = Shift::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($shiftId);

        if (!in_array((string) $shift->status, ['open', 'assigned'], true)) {
            throw new \RuntimeException('Shift is not open for requests.');
        }

        $requiredStaff = max(1, (int) ($shift->required_staff ?? 1));
        $approvedCount = ShiftAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('shift_id', $shift->id)
            ->whereIn('status', ['approved', 'completed'])
            ->count();

        if ($approvedCount >= $requiredStaff) {
            throw new \RuntimeException('Shift has reached required staffing capacity.');
        }

        return DB::transaction(function () use ($tenantId, $shift, $candidateId, $notes, $actor) {
            $request = ShiftRequest::query()
                ->where('tenant_id', $tenantId)
                ->where('shift_id', $shift->id)
                ->where('candidate_id', $candidateId)
                ->lockForUpdate()
                ->first();

            if ($request) {
                if ((string) $request->status === 'withdrawn') {
                    $request->status = 'pending';
                    $request->notes = $notes;
                    $request->requested_at = now();
                    $request->responded_at = null;
                    $request->responded_by_user_id = null;
                    $request->rejection_reason = null;
                    $request->save();
                }

                if ((string) $request->status !== 'pending') {
                    throw new \RuntimeException('Shift request is not in a requestable state.');
                }
            } else {
                $request = ShiftRequest::create([
                    'tenant_id' => $tenantId,
                    'shift_id' => $shift->id,
                    'candidate_id' => $candidateId,
                    'status' => 'pending',
                    'notes' => $notes,
                    'requested_at' => now(),
                ]);
            }

            ShiftRequested::dispatch($request, $shift, $tenantId, $actor);

            return $request;
        });
    }

    public function withdrawRequest(int $tenantId, int $shiftId, int $candidateId, ?User $actor = null): ShiftRequest
    {
        return DB::transaction(function () use ($tenantId, $shiftId, $candidateId, $actor) {
            $shift = Shift::query()
                ->where('tenant_id', $tenantId)
                ->findOrFail($shiftId);

            $request = ShiftRequest::query()
                ->where('tenant_id', $tenantId)
                ->where('shift_id', $shift->id)
                ->where('candidate_id', $candidateId)
                ->lockForUpdate()
                ->firstOrFail();

            if ((string) $request->status !== 'pending') {
                throw new \RuntimeException('Only pending requests can be withdrawn.');
            }

            $request->status = 'withdrawn';
            $request->responded_at = now();
            $request->responded_by_user_id = $actor?->id;
            $request->save();

            ShiftRequestWithdrawn::dispatch($request, $shift, $tenantId, $actor);

            return $request;
        });
    }

    public function checkIn(int $tenantId, int $shiftId, int $candidateId, ?User $actor = null): ShiftAssignment
    {
        return DB::transaction(function () use ($tenantId, $shiftId, $candidateId, $actor) {
            $shift = Shift::query()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail($shiftId);

            $assignment = ShiftAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('shift_id', $shift->id)
                ->where('candidate_id', $candidateId)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$assignment->checked_in_at) {
                $assignment->checked_in_at = now();
                $assignment->actioned_by_user_id = $actor?->id;
                $assignment->save();
            }

            if (in_array((string) $shift->status, ['open', 'assigned'], true)) {
                $shift->status = 'in_progress';
                $shift->save();
            }

            return $assignment;
        });
    }

    public function checkOut(int $tenantId, int $shiftId, int $candidateId, ?User $actor = null): ShiftAssignment
    {
        return DB::transaction(function () use ($tenantId, $shiftId, $candidateId, $actor) {
            $shift = Shift::query()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail($shiftId);

            $assignment = ShiftAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('shift_id', $shift->id)
                ->where('candidate_id', $candidateId)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$assignment->checked_out_at) {
                $assignment->checked_out_at = now();
                $assignment->actioned_by_user_id = $actor?->id;
                $assignment->save();
            }

            return $assignment;
        });
    }

    public function cancelShift(int $tenantId, int $shiftId, ?User $actor = null): Shift
    {
        return DB::transaction(function () use ($tenantId, $shiftId, $actor) {
            $shift = Shift::query()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail($shiftId);

            if (in_array((string) $shift->status, ['cancelled', 'completed'], true)) {
                return $shift;
            }

            $shift->status = 'cancelled';
            $shift->save();

            $assignments = ShiftAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('shift_id', $shift->id)
                ->lockForUpdate()
                ->get();

            foreach ($assignments as $assignment) {
                if ((string) $assignment->status !== 'cancelled') {
                    $assignment->status = 'cancelled';
                    $assignment->cancelled_at = now();
                    $assignment->actioned_by_user_id = $actor?->id;
                    $assignment->save();
                }
            }

            ShiftCancelled::dispatch($shift, $tenantId, $actor);

            return $shift;
        });
    }

    public function completeShift(int $tenantId, int $shiftId, ?User $actor = null): array
    {
        return DB::transaction(function () use ($tenantId, $shiftId, $actor) {
            $shift = Shift::query()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail($shiftId);

            if ((string) $shift->status === 'completed') {
                $assignment = ShiftAssignment::query()->where('tenant_id', $tenantId)->where('shift_id', $shift->id)->first();
                return ['shift' => $shift, 'assignment' => $assignment, 'timesheet' => null];
            }

            $shift->status = 'completed';
            $shift->save();

            $assignments = ShiftAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('shift_id', $shift->id)
                ->lockForUpdate()
                ->get();

            $assignment = $assignments->first();

            $timesheet = null;

            if ($shift->assignment_id) {
                foreach ($assignments as $a) {
                    if ((string) $a->status !== 'completed') {
                        $a->status = 'completed';
                        $a->completed_at = now();
                        $a->actioned_by_user_id = $actor?->id;
                        $a->save();
                    }

                    $weekStart = Carbon::parse($shift->starts_at)->startOfWeek(Carbon::MONDAY)->toDateString();

                    $t = Timesheet::firstOrCreate([
                        'tenant_id' => $tenantId,
                        'assignment_id' => $shift->assignment_id,
                        'week_start_date' => $weekStart,
                    ], [
                        'candidate_id' => $a->candidate_id,
                        'status' => 'draft',
                    ]);

                    if (!$timesheet) {
                        $timesheet = $t;
                    }

                    $minutes = Carbon::parse($shift->starts_at)->diffInMinutes(Carbon::parse($shift->ends_at));
                    $minutes = max(0, $minutes - (int) ($shift->break_minutes ?? 0));
                    $hours = round($minutes / 60, 2);

                    TimesheetEntry::query()->firstOrCreate([
                        'timesheet_id' => $t->id,
                        'work_date' => Carbon::parse($shift->starts_at)->toDateString(),
                    ], [
                        'hours_worked' => $hours,
                        'overtime_hours' => null,
                        'notes' => 'Generated from shift #' . $shift->id,
                    ]);
                }
            }

            ShiftCompleted::dispatch($shift, $assignment, $timesheet, $tenantId, $actor);

            return ['shift' => $shift, 'assignment' => $assignment, 'timesheet' => $timesheet];
        });
    }
}
