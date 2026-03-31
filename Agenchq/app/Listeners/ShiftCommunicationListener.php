<?php

namespace App\Listeners;

use App\Events\ShiftAssignmentApproved;
use App\Events\ShiftAssignmentRejected;
use App\Events\ShiftCancelled;
use App\Events\ShiftCompleted;
use App\Events\ShiftCreated;
use App\Events\ShiftRequestWithdrawn;
use App\Events\ShiftRequested;
use App\Services\CommunicationService;

class ShiftCommunicationListener
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
            $event instanceof ShiftCreated => [
                'shift_id' => $event->shift->id,
                'facility_id' => $event->shift->facility_id,
                'assignment_id' => $event->shift->assignment_id,
                'starts_at' => $event->shift->starts_at?->toIso8601String(),
                'ends_at' => $event->shift->ends_at?->toIso8601String(),
                'status' => $event->shift->status,
            ],
            $event instanceof ShiftRequested => [
                'shift_id' => $event->shift->id,
                'shift_request_id' => $event->request->id,
                'candidate_id' => $event->request->candidate_id,
                'notes' => $event->request->notes,
                'status' => $event->request->status,
            ],
            $event instanceof ShiftRequestWithdrawn => [
                'shift_id' => $event->shift->id,
                'shift_request_id' => $event->request->id,
                'candidate_id' => $event->request->candidate_id,
                'status' => $event->request->status,
            ],
            $event instanceof ShiftAssignmentApproved => [
                'shift_id' => $event->shift->id,
                'shift_request_id' => $event->request->id,
                'shift_assignment_id' => $event->assignment->id,
                'candidate_id' => $event->assignment->candidate_id,
                'status' => $event->assignment->status,
            ],
            $event instanceof ShiftAssignmentRejected => [
                'shift_id' => $event->shift->id,
                'shift_request_id' => $event->request->id,
                'candidate_id' => $event->request->candidate_id,
                'status' => $event->request->status,
                'rejection_reason' => $event->request->rejection_reason,
            ],
            $event instanceof ShiftCompleted => [
                'shift_id' => $event->shift->id,
                'shift_assignment_id' => $event->assignment?->id,
                'candidate_id' => $event->assignment?->candidate_id,
                'timesheet_id' => $event->timesheet?->id,
            ],
            $event instanceof ShiftCancelled => [
                'shift_id' => $event->shift->id,
                'status' => $event->shift->status,
            ],
            default => [],
        };

        $this->communicationService->dispatchWebhook((int) $tenantId, $eventName, $payload);
    }
}
