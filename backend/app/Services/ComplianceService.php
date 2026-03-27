<?php

namespace App\Services;

use App\Models\CandidateCredential;
use App\Models\CredentialType;
use App\Models\FacilityCredentialRequirement;
use App\Models\Template;
use Illuminate\Support\Facades\Cache;

class ComplianceService
{
    public function evaluateWorkerCompliance(int $tenantId, int $candidateId, ?int $facilityId = null): array
    {
        $baseline = $this->evaluateGlobalBaseline($tenantId, $candidateId);

        if (!$facilityId) {
            return [
                'status' => $baseline['status'],
                'global' => $baseline,
            ];
        }

        $facility = $this->evaluateFacilityDelta($tenantId, $candidateId, $facilityId);

        $effective = $this->evaluateEffectiveForFacility(
            $tenantId,
            $candidateId,
            $baseline,
            $facility,
            $facilityId
        );

        return [
            'status' => $effective['status'],
            'global' => $baseline,
            'facility' => $facility,
            'effective' => $effective,
        ];
    }

    public function checkCandidateCompliance(int $tenantId, int $candidateId, int $facilityId): array
    {
        $cacheKey = 'facility_requirements_' . $tenantId . '_' . $facilityId;

        $requiredTypeIds = Cache::remember($cacheKey, now()->addHours(6), function () use ($tenantId, $facilityId) {
            return FacilityCredentialRequirement::query()
                ->where('tenant_id', $tenantId)
                ->where('facility_id', $facilityId)
                ->where('required', true)
                ->pluck('credential_type_id')
                ->unique()
                ->values();
        });

        $requiredTypes = CredentialType::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $requiredTypeIds)
            ->get()
            ->keyBy('id');

        $result = $this->evaluateCredentialTypes($tenantId, $candidateId, $requiredTypeIds->all(), $requiredTypes);

        return [
            'compliant' => ($result['status'] ?? null) === 'ready',
            'missing_credentials' => $result['missing'] ?? [],
            'expired_credentials' => $result['expired'] ?? [],
            'expiring_soon' => $result['expiring_soon'] ?? [],
        ];
    }

    private function evaluateGlobalBaseline(int $tenantId, int $candidateId): array
    {
        $requiredTemplateTypes = Template::query()
            ->where('is_active', true)
            ->where('organization_id', $tenantId)
            ->orderBy('name')
            ->pluck('credential_type')
            ->filter(fn ($t) => is_string($t) && trim($t) !== '')
            ->map(fn ($t) => strtolower(trim((string) $t)))
            ->unique()
            ->values();

        $allTypes = CredentialType::query()
            ->where('tenant_id', $tenantId)
            ->get();

        $byNormalizedName = $allTypes
            ->keyBy(fn (CredentialType $t) => strtolower(trim((string) $t->name)));

        $matchedTypeIds = [];
        $unmapped = [];

        foreach ($requiredTemplateTypes as $name) {
            $type = $byNormalizedName->get($name);
            if ($type) {
                $matchedTypeIds[] = (int) $type->id;
                continue;
            }

            $unmapped[] = [
                'credential_type_id' => null,
                'name' => $name,
                'category' => null,
                'source' => 'template',
            ];
        }

        $requiredTypes = $allTypes->whereIn('id', $matchedTypeIds)->keyBy('id');

        $evaluated = $this->evaluateCredentialTypes($tenantId, $candidateId, $matchedTypeIds, $requiredTypes);

        $missing = array_values(array_merge($evaluated['missing'] ?? [], $unmapped));
        $status = $this->statusFromLists($missing, $evaluated['expired'] ?? [], $evaluated['pending'] ?? [], $evaluated['rejected'] ?? []);

        return [
            'status' => $status,
            'required' => $this->labelsFromTypes($matchedTypeIds, $requiredTypes),
            'missing' => $missing,
            'expired' => $evaluated['expired'] ?? [],
            'pending' => $evaluated['pending'] ?? [],
            'rejected' => $evaluated['rejected'] ?? [],
            'expiring_soon' => $evaluated['expiring_soon'] ?? [],
        ];
    }

    private function evaluateFacilityDelta(int $tenantId, int $candidateId, int $facilityId): array
    {
        $cacheKey = 'facility_requirements_' . $tenantId . '_' . $facilityId;

        $requiredTypeIds = Cache::remember($cacheKey, now()->addHours(6), function () use ($tenantId, $facilityId) {
            return FacilityCredentialRequirement::query()
                ->where('tenant_id', $tenantId)
                ->where('facility_id', $facilityId)
                ->where('required', true)
                ->pluck('credential_type_id')
                ->unique()
                ->values();
        });

        $requiredTypes = CredentialType::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $requiredTypeIds)
            ->get()
            ->keyBy('id');

        $evaluated = $this->evaluateCredentialTypes($tenantId, $candidateId, $requiredTypeIds->all(), $requiredTypes);

        return [
            'status' => $evaluated['status'] ?? 'ready',
            'facility_id' => $facilityId,
            'required' => $this->labelsFromTypes($requiredTypeIds->all(), $requiredTypes),
            'missing' => $evaluated['missing'] ?? [],
            'expired' => $evaluated['expired'] ?? [],
            'pending' => $evaluated['pending'] ?? [],
            'rejected' => $evaluated['rejected'] ?? [],
            'expiring_soon' => $evaluated['expiring_soon'] ?? [],
        ];
    }

    private function evaluateEffectiveForFacility(
        int $tenantId,
        int $candidateId,
        array $baseline,
        array $facility,
        int $facilityId
    ): array {
        $globalRequired = $baseline['required'] ?? [];
        $facilityRequired = $facility['required'] ?? [];

        $requiredByKey = [];
        foreach (array_merge($globalRequired, $facilityRequired) as $item) {
            $key = ($item['credential_type_id'] ?? null) ? ('id:' . $item['credential_type_id']) : ('name:' . strtolower((string) ($item['name'] ?? '')));
            if (!$key || isset($requiredByKey[$key])) {
                continue;
            }
            $requiredByKey[$key] = $item;
        }

        $missingByKey = [];
        foreach (array_merge($baseline['missing'] ?? [], $facility['missing'] ?? []) as $item) {
            $key = ($item['credential_type_id'] ?? null) ? ('id:' . $item['credential_type_id']) : ('name:' . strtolower((string) ($item['name'] ?? '')));
            if (!$key || isset($missingByKey[$key])) {
                continue;
            }
            $missingByKey[$key] = $item;
        }

        $expiredByKey = [];
        foreach (array_merge($baseline['expired'] ?? [], $facility['expired'] ?? []) as $item) {
            $key = ($item['credential_type_id'] ?? null) ? ('id:' . $item['credential_type_id']) : ('name:' . strtolower((string) ($item['name'] ?? '')));
            if (!$key || isset($expiredByKey[$key])) {
                continue;
            }
            $expiredByKey[$key] = $item;
        }

        $rejectedByKey = [];
        foreach (array_merge($baseline['rejected'] ?? [], $facility['rejected'] ?? []) as $item) {
            $key = ($item['credential_type_id'] ?? null) ? ('id:' . $item['credential_type_id']) : ('name:' . strtolower((string) ($item['name'] ?? '')));
            if (!$key || isset($rejectedByKey[$key])) {
                continue;
            }
            $rejectedByKey[$key] = $item;
        }

        $pendingByKey = [];
        foreach (array_merge($baseline['pending'] ?? [], $facility['pending'] ?? []) as $item) {
            $key = ($item['credential_type_id'] ?? null) ? ('id:' . $item['credential_type_id']) : ('name:' . strtolower((string) ($item['name'] ?? '')));
            if (!$key || isset($pendingByKey[$key])) {
                continue;
            }
            $pendingByKey[$key] = $item;
        }

        $missing = array_values($missingByKey);
        $expired = array_values($expiredByKey);
        $pending = array_values($pendingByKey);
        $rejected = array_values($rejectedByKey);
        $status = $this->statusFromLists($missing, $expired, $pending, $rejected);

        return [
            'status' => $status,
            'facility_id' => $facilityId,
            'required' => array_values($requiredByKey),
            'missing' => $missing,
            'expired' => $expired,
            'pending' => $pending,
            'rejected' => $rejected,
            'expiring_soon' => array_values(array_merge($baseline['expiring_soon'] ?? [], $facility['expiring_soon'] ?? [])),
        ];
    }

    private function evaluateCredentialTypes(int $tenantId, int $candidateId, array $requiredTypeIds, $requiredTypes): array
    {
        $requiredTypeIds = array_values(array_unique(array_map('intval', $requiredTypeIds)));

        if (count($requiredTypeIds) === 0) {
            return [
                'status' => 'ready',
                'missing' => [],
                'expired' => [],
                'pending' => [],
                'rejected' => [],
                'expiring_soon' => [],
            ];
        }

        $credentials = CandidateCredential::query()
            ->where('tenant_id', $tenantId)
            ->where('candidate_id', $candidateId)
            ->whereIn('credential_type_id', $requiredTypeIds)
            ->orderByDesc('id')
            ->get()
            ->groupBy('credential_type_id');

        $missing = [];
        $expired = [];
        $pending = [];
        $rejected = [];
        $expiringSoon = [];

        foreach ($requiredTypeIds as $typeId) {
            $type = $requiredTypes->get($typeId);
            $typeLabel = [
                'credential_type_id' => (int) $typeId,
                'name' => $type?->name,
                'category' => $type?->category,
            ];

            $typeCreds = $credentials->get($typeId, collect());

            $verified = $typeCreds
                ->where('status', 'verified')
                ->sortByDesc('verified_at')
                ->first();

            if ($verified) {
                if ($verified->expires_at && $verified->expires_at->isPast()) {
                    $expired[] = $typeLabel;
                    continue;
                }

                if ($verified->expires_at && $verified->expires_at->lte(now()->addDays(30))) {
                    $days = now()->diffInDays($verified->expires_at, false);
                    $expiringSoon[] = $typeLabel + ['days_remaining' => $days];
                }

                continue;
            }

            $hasRejected = $typeCreds->where('status', 'rejected')->count() > 0;
            if ($hasRejected) {
                $rejected[] = $typeLabel;
                continue;
            }

            $hasPending = $typeCreds->where('status', 'pending')->count() > 0;
            if ($hasPending) {
                $pending[] = $typeLabel;
                continue;
            }

            $hasExpired = $typeCreds->where('status', 'expired')->count() > 0;
            if ($hasExpired) {
                $expired[] = $typeLabel;
                continue;
            }

            $missing[] = $typeLabel;
        }

        return [
            'status' => $this->statusFromLists($missing, $expired, $pending, $rejected),
            'missing' => $missing,
            'expired' => $expired,
            'pending' => $pending,
            'rejected' => $rejected,
            'expiring_soon' => $expiringSoon,
        ];
    }

    private function statusFromLists(array $missing, array $expired, array $pending, array $rejected): string
    {
        if (count($missing) > 0 || count($expired) > 0 || count($rejected) > 0) {
            return 'blocked';
        }

        if (count($pending) > 0) {
            return 'pending';
        }

        return 'ready';
    }

    private function labelsFromTypes(array $typeIds, $requiredTypes): array
    {
        $labels = [];

        foreach ($typeIds as $typeId) {
            $type = $requiredTypes->get((int) $typeId);
            $labels[] = [
                'credential_type_id' => (int) $typeId,
                'name' => $type?->name,
                'category' => $type?->category,
            ];
        }

        return $labels;
    }
}
