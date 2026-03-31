<?php

namespace App\Listeners;

use App\Events\CredentialExpired;
use App\Events\CredentialVerified;
use App\Events\ShiftAssignmentApproved;
use App\Events\ShiftCancelled;
use App\Events\ShiftCompleted;
use App\Services\AvailabilityIndexService;

class AvailabilityIndexListener
{
    public function __construct(
        private AvailabilityIndexService $indexService
    ) {}

    public function handle(object $event): void
    {
        match (true) {
            $event instanceof CredentialVerified => $this->indexService->updateCredentialStatus((int) $event->credential->candidate_id),
            $event instanceof CredentialExpired => $this->indexService->updateCredentialStatus((int) $event->credential->candidate_id),
            $event instanceof ShiftAssignmentApproved => $this->indexService->updateCandidateAvailability((int) $event->assignment->candidate_id),
            $event instanceof ShiftCompleted => $this->indexService->updateCandidateAvailability((int) ($event->assignment?->candidate_id ?? 0)),
            $event instanceof ShiftCancelled => $this->handleShiftCancelled($event),
            default => null,
        };
    }

    private function handleShiftCancelled(ShiftCancelled $event): void
    {
        $shift = $event->shift;
        $shift->loadMissing(['shiftAssignments:candidate_id,shift_id,tenant_id']);

        foreach ($shift->shiftAssignments as $assignment) {
            $this->indexService->updateCandidateAvailability((int) $assignment->candidate_id);
        }
    }
}
