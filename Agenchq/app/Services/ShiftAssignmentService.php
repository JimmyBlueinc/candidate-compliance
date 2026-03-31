<?php

namespace App\Services;

use App\Events\ShiftAssignmentApproved;
use App\Events\ShiftAssignmentRejected;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\ShiftRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftAssignmentService
{
    public function __construct(
        private ComplianceService $complianceService,
        private AvailabilityService $availabilityService
    ) {}

    public function approveRequest(int $tenantId, int $shiftRequestId, ?User $actor = null): ShiftAssignment
    {
        return DB::transaction(function () use ($tenantId, $shiftRequestId, $actor) {
            $request = ShiftRequest::query()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail($shiftRequestId);

            if ((string) $request->status !== 'pending') {
                throw new \RuntimeException('Only pending requests can be approved.');
            }

            $shift = Shift::query()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail((int) $request->shift_id);

            if (!in_array((string) $shift->status, ['open', 'assigned'], true)) {
                throw new \RuntimeException('Shift is not available for assignment.');
            }

            $approvedCount = ShiftAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('shift_id', $shift->id)
                ->whereIn('status', ['approved', 'completed'])
                ->lockForUpdate()
                ->count();

            $requiredStaff = max(1, (int) ($shift->required_staff ?? 1));
            if ($approvedCount >= $requiredStaff) {
                throw new \RuntimeException('Shift has reached required staffing capacity.');
            }

            $this->availabilityService->assertNotHardBlocked($tenantId, (int) $request->candidate_id, $shift->starts_at, $shift->ends_at);
            $this->assertNoOverlap($tenantId, (int) $request->candidate_id, $shift->starts_at, $shift->ends_at);

            $facilityId = (int) ($shift->facility_id ?? $shift->assignment?->facility_id ?? 0);
            if ($facilityId > 0) {
                $eval = $this->complianceService->evaluateWorkerCompliance($tenantId, (int) $request->candidate_id, $facilityId);
                $effective = $eval['effective'] ?? $eval['facility'] ?? [];
                $status = (string) ($eval['status'] ?? ($effective['status'] ?? 'ready'));

                if ($status !== 'ready') {
                    throw ValidationException::withMessages([
                        'message' => ['Worker is not compliance-ready for this facility.'],
                        'status' => [$status],
                        'missing' => $effective['missing'] ?? [],
                        'expired' => $effective['expired'] ?? [],
                        'pending' => $effective['pending'] ?? [],
                        'rejected' => $effective['rejected'] ?? [],
                    ]);
                }
            }

            $request->status = 'approved';
            $request->responded_at = now();
            $request->responded_by_user_id = $actor?->id;
            $request->save();

            $assignment = ShiftAssignment::create([
                'tenant_id' => $tenantId,
                'shift_id' => $shift->id,
                'candidate_id' => $request->candidate_id,
                'status' => 'approved',
                'approved_at' => now(),
                'actioned_by_user_id' => $actor?->id,
            ]);

            $updatedApprovedCount = $approvedCount + 1;
            if ($updatedApprovedCount >= $requiredStaff) {
                ShiftRequest::query()
                    ->where('tenant_id', $tenantId)
                    ->where('shift_id', $shift->id)
                    ->where('id', '!=', $request->id)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'rejected',
                        'responded_at' => now(),
                        'responded_by_user_id' => $actor?->id,
                        'rejection_reason' => 'Shift has reached staffing capacity.',
                    ]);
            }

            if ((string) $shift->status === 'open') {
                $shift->status = 'assigned';
                $shift->save();
            }

            ShiftAssignmentApproved::dispatch($assignment, $request, $shift, $tenantId, $actor);

            return $assignment;
        });
    }

    public function rejectRequest(int $tenantId, int $shiftRequestId, string $reason, ?User $actor = null): ShiftRequest
    {
        return DB::transaction(function () use ($tenantId, $shiftRequestId, $reason, $actor) {
            $request = ShiftRequest::query()
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail($shiftRequestId);

            if ((string) $request->status !== 'pending') {
                throw new \RuntimeException('Only pending requests can be rejected.');
            }

            $shift = Shift::query()
                ->where('tenant_id', $tenantId)
                ->findOrFail((int) $request->shift_id);

            $request->status = 'rejected';
            $request->responded_at = now();
            $request->responded_by_user_id = $actor?->id;
            $request->rejection_reason = $reason;
            $request->save();

            ShiftAssignmentRejected::dispatch($request, $shift, $tenantId, $actor);

            return $request;
        });
    }

    private function assertNoOverlap(int $tenantId, int $candidateId, $startsAt, $endsAt): void
    {
        $overlap = ShiftAssignment::query()
            ->join('shifts', 'shift_assignments.shift_id', '=', 'shifts.id')
            ->where('shift_assignments.tenant_id', $tenantId)
            ->where('shift_assignments.candidate_id', $candidateId)
            ->whereIn('shift_assignments.status', ['approved', 'completed'])
            ->where('shifts.status', '!=', 'cancelled')
            ->where(function ($q) use ($startsAt, $endsAt) {
                $q->where('shifts.starts_at', '<', $endsAt)
                    ->where('shifts.ends_at', '>', $startsAt);
            })
            ->exists();

        if ($overlap) {
            throw new \RuntimeException('Candidate has an overlapping shift assignment.');
        }
    }

    
}
