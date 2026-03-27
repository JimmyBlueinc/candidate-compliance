<?php

namespace App\Listeners;

use App\Events\JobOrderClosed;
use App\Events\JobOrderCreated;
use App\Events\JobOrderFilled;
use App\Services\CommunicationService;

class JobOrderCommunicationListener
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
            $event instanceof JobOrderCreated => [
                'job_order_id' => $event->jobOrder->id,
                'facility_id' => $event->jobOrder->facility_id,
                'status' => $event->jobOrder->status,
                'required_staff' => $event->jobOrder->required_staff,
                'start_date' => $event->jobOrder->start_date,
                'end_date' => $event->jobOrder->end_date,
            ],
            $event instanceof JobOrderFilled => [
                'job_order_id' => $event->jobOrder->id,
                'facility_id' => $event->jobOrder->facility_id,
                'previous_status' => $event->previousStatus,
                'status' => $event->status,
                'required_staff' => $event->jobOrder->required_staff,
            ],
            $event instanceof JobOrderClosed => [
                'job_order_id' => $event->jobOrder->id,
                'facility_id' => $event->jobOrder->facility_id,
                'previous_status' => $event->previousStatus,
                'status' => $event->status,
            ],
            default => [],
        };

        $this->communicationService->dispatchWebhook((int) $tenantId, $eventName, $payload);
    }
}
