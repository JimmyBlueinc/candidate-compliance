<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\JobOrder;
use App\Models\Placement;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OperationalPlacementService
{
    public function __construct(
        private ComplianceService $complianceService
    ) {}

    /**
     * Create (or return existing) operational placement Assignment for a pipeline Placement.
     *
     * Assignment is the canonical operational placement entity.
     */
    public function createFromPipelinePlacement(int $tenantId, int $placementId): Assignment
    {
        return DB::transaction(function () use ($tenantId, $placementId) {
            $placement = Placement::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->find($placementId);

            if (!$placement) {
                throw (new ModelNotFoundException())->setModel(Placement::class, [$placementId]);
            }

            $jobOrder = JobOrder::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->find($placement->job_order_id);

            $facilityId = (int) ($jobOrder?->facility_id ?? 0);
            if ($facilityId <= 0) {
                throw ValidationException::withMessages([
                    'facility_id' => ['Job order must be associated with a facility before an operational placement can be created.'],
                ]);
            }

            $eval = $this->complianceService->evaluateWorkerCompliance($tenantId, (int) $placement->candidate_id, $facilityId);
            $effective = $eval['effective'] ?? $eval['facility'] ?? [];
            $status = (string) ($eval['status'] ?? ($effective['status'] ?? 'ready'));

            if ($status !== 'ready') {
                throw ValidationException::withMessages([
                    'status' => [$status],
                    'missing' => $effective['missing'] ?? [],
                    'expired' => $effective['expired'] ?? [],
                    'pending' => $effective['pending'] ?? [],
                    'rejected' => $effective['rejected'] ?? [],
                ]);
            }

            $existing = Assignment::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->where('placement_id', $placement->id)
                ->first();

            if ($existing) {
                return $existing;
            }

            // Prevent duplicate active assignments for the same candidate/job order combination.
            // This is intentionally conservative and only guards active/pending.
            $duplicate = Assignment::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->where('candidate_id', $placement->candidate_id)
                ->where('job_order_id', $placement->job_order_id)
                ->whereIn('status', ['pending', 'active'])
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'assignment' => ['Candidate already has an active assignment for this job order.'],
                ]);
            }

            return Assignment::withoutGlobalScope(TenantScope::class)->create([
                'tenant_id' => $tenantId,
                'placement_id' => $placement->id,
                'candidate_id' => $placement->candidate_id,
                'job_order_id' => $placement->job_order_id,
                'recruiter_id' => $placement->recruiter_id,
                'facility_id' => $jobOrder?->facility_id,
                'facility_name' => $jobOrder?->facility_name,
                'pay_rate' => $jobOrder?->pay_rate,
                'bill_rate' => $jobOrder?->bill_rate,
                'status' => 'pending',
            ]);
        });
    }
}
