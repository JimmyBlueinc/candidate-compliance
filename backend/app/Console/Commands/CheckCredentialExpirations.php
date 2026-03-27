<?php

namespace App\Console\Commands;

use App\Events\CredentialExpired;
use App\Events\CredentialExpiringSoon;
use App\Models\CandidateCredential;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;

class CheckCredentialExpirations extends Command
{
    protected $signature = 'credentials:check-expirations {--tenantId=}';

    protected $description = 'Detect credentials expiring soon or expired, dispatch events, and update status.';

    public function handle(): int
    {
        $tenantId = $this->option('tenantId');

        $query = CandidateCredential::query()->withoutGlobalScopes()
            ->where('status', 'verified')
            ->whereNotNull('expires_at');

        if ($tenantId) {
            $query->where('tenant_id', (int) $tenantId);
        }

        $expiringSoonCount = 0;
        $expiredCount = 0;

        $query
            ->whereBetween('expires_at', [now(), now()->addDays(30)])
            ->chunkById(200, function ($credentials) use (&$expiringSoonCount) {
                foreach ($credentials as $credential) {
                    $daysRemaining = now()->diffInDays($credential->expires_at, false);
                    Event::dispatch(new CredentialExpiringSoon((int) $credential->tenant_id, $credential, $daysRemaining));
                    $expiringSoonCount++;
                }
            });

        $expiredQuery = CandidateCredential::query()->withoutGlobalScopes()
            ->whereIn('status', ['pending', 'verified'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now());

        if ($tenantId) {
            $expiredQuery->where('tenant_id', (int) $tenantId);
        }

        $expiredQuery
            ->chunkById(200, function ($credentials) use (&$expiredCount) {
                foreach ($credentials as $credential) {
                    if ($credential->status !== 'expired') {
                        $credential->status = 'expired';
                        $credential->save();
                    }
                    Event::dispatch(new CredentialExpired((int) $credential->tenant_id, $credential));
                    $expiredCount++;
                }
            });

        $this->info('Expiring soon dispatched: ' . $expiringSoonCount);
        $this->info('Expired processed: ' . $expiredCount);

        return Command::SUCCESS;
    }
}
