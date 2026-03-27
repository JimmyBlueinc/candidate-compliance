<?php

namespace App\Listeners;

use App\Events\CredentialExpired;
use App\Events\CredentialExpiringSoon;
use App\Events\CredentialRejected;
use App\Events\CredentialUploaded;
use App\Events\CredentialVerified;
use App\Services\CommunicationService;

class CredentialCommunicationListener
{
    public function __construct(
        private CommunicationService $communicationService
    ) {}

    public function handle(object $event): void
    {
        $tenantId = $event->tenantId ?? null;
        if (!$tenantId) {
            return;
        }

        $eventName = class_basename($event);

        $payload = match (true) {
            $event instanceof CredentialUploaded,
            $event instanceof CredentialVerified,
            $event instanceof CredentialRejected => [
                'credential_id' => $event->credential->id,
                'candidate_id' => $event->credential->candidate_id,
                'credential_type_id' => $event->credential->credential_type_id,
                'status' => $event->credential->status,
                'expires_at' => $event->credential->expires_at?->toIso8601String(),
                'actor_id' => $event->actor?->id,
                'notes' => $event instanceof CredentialRejected ? $event->notes : null,
            ],
            $event instanceof CredentialExpiringSoon => [
                'credential_id' => $event->credential->id,
                'candidate_id' => $event->credential->candidate_id,
                'credential_type_id' => $event->credential->credential_type_id,
                'expires_at' => $event->credential->expires_at?->toIso8601String(),
                'days_remaining' => $event->daysRemaining,
            ],
            $event instanceof CredentialExpired => [
                'credential_id' => $event->credential->id,
                'candidate_id' => $event->credential->candidate_id,
                'credential_type_id' => $event->credential->credential_type_id,
                'expires_at' => $event->credential->expires_at?->toIso8601String(),
                'status' => $event->credential->status,
            ],
            default => [],
        };

        $this->communicationService->dispatchWebhook((int) $tenantId, $eventName, $payload);
    }
}
