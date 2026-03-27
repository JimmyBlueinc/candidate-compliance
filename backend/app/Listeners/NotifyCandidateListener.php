<?php

namespace App\Listeners;

use App\Events\MessageCreated;
use App\Models\User;
use App\Services\NotificationService;

class NotifyCandidateListener
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle(MessageCreated $event): void
    {
        // Placeholder: notify candidate users if/when message contexts map to a candidate.
        // Keeping tenant isolation as a guard.
        if ($event->tenantId <= 0) {
            return;
        }

        // No-op for now.
        return;
    }
}
