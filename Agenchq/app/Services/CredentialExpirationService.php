<?php

namespace App\Services;

use App\Events\CredentialExpiringSoon;
use App\Models\CandidateCredential;
use Illuminate\Support\Facades\Event;

class CredentialExpirationService
{
    public function detectExpiringSoon(int $tenantId): int
    {
        $count = 0;

        CandidateCredential::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'verified')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays(30)])
            ->chunkById(200, function ($credentials) use ($tenantId, &$count) {
                foreach ($credentials as $credential) {
                    $daysRemaining = now()->diffInDays($credential->expires_at, false);
                    Event::dispatch(new CredentialExpiringSoon($tenantId, $credential, $daysRemaining));
                    $count++;
                }
            });

        CandidateCredential::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'verified'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        return $count;
    }
}
