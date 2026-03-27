<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\CandidateCredential;
use App\Models\CandidateShiftIndex;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\Scopes\TenantScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AvailabilityIndexService
{
    public function rebuildCandidateIndex(int $candidateId): void
    {
        $candidate = Candidate::query()
            ->withoutGlobalScope(TenantScope::class)
            ->findOrFail($candidateId);

        $tenantId = (int) $candidate->tenant_id;

        DB::transaction(function () use ($tenantId, $candidateId) {
            CandidateShiftIndex::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('candidate_id', $candidateId)
                ->delete();

            $credentialStatus = $this->computeCredentialStatus($tenantId, $candidateId);

            $assignments = ShiftAssignment::query()
                ->withoutGlobalScopes()
                ->with(['shift:id,tenant_id,facility_id,starts_at,ends_at,status'])
                ->where('tenant_id', $tenantId)
                ->where('candidate_id', $candidateId)
                ->whereIn('status', ['approved', 'completed'])
                ->get();

            foreach ($assignments as $assignment) {
                $shift = $assignment->shift;
                if (!$shift) {
                    continue;
                }

                if ((string) $shift->status === 'cancelled') {
                    continue;
                }

                $startUtc = Carbon::parse($shift->starts_at)->utc();
                $endUtc = Carbon::parse($shift->ends_at)->utc();

                CandidateShiftIndex::create([
                    'tenant_id' => $tenantId,
                    'candidate_id' => $candidateId,
                    'facility_id' => $shift->facility_id,
                    'role' => null,
                    'date' => $startUtc->toDateString(),
                    'start_time' => $startUtc->format('H:i:s'),
                    'end_time' => $endUtc->format('H:i:s'),
                    'is_available' => false,
                    'credential_status' => $credentialStatus,
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function updateCandidateAvailability(int $candidateId): void
    {
        $this->rebuildCandidateIndex($candidateId);
    }

    public function markCandidateUnavailable(int $candidateId, $start, $end): void
    {
        $candidate = Candidate::query()->withoutGlobalScope(TenantScope::class)->findOrFail($candidateId);
        $tenantId = (int) $candidate->tenant_id;

        $startUtc = Carbon::parse($start)->utc();
        $endUtc = Carbon::parse($end)->utc();

        CandidateShiftIndex::query()->withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'candidate_id' => $candidateId,
            'facility_id' => null,
            'role' => null,
            'date' => $startUtc->toDateString(),
            'start_time' => $startUtc->format('H:i:s'),
            'end_time' => $endUtc->format('H:i:s'),
            'is_available' => false,
            'credential_status' => $this->computeCredentialStatus($tenantId, $candidateId),
            'updated_at' => now(),
        ]);
    }

    public function updateCredentialStatus(int $candidateId): void
    {
        $candidate = Candidate::query()->withoutGlobalScope(TenantScope::class)->findOrFail($candidateId);
        $tenantId = (int) $candidate->tenant_id;

        $status = $this->computeCredentialStatus($tenantId, $candidateId);

        CandidateShiftIndex::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('candidate_id', $candidateId)
            ->update([
                'credential_status' => $status,
                'updated_at' => now(),
            ]);
    }

    private function computeCredentialStatus(int $tenantId, int $candidateId): string
    {
        $hasExpired = CandidateCredential::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('candidate_id', $candidateId)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->exists();

        if ($hasExpired) {
            return 'expired';
        }

        $hasVerified = CandidateCredential::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('candidate_id', $candidateId)
            ->where('status', 'verified')
            ->exists();

        return $hasVerified ? 'verified' : 'unverified';
    }
}
