<?php

namespace App\Listeners;

use App\Events\CandidateRecruiterAssigned;
use App\Events\CandidateStageChanged;
use App\Events\CredentialExpired;
use App\Events\CredentialExpiringSoon;
use App\Events\CredentialRejected;
use App\Events\CredentialUploaded;
use App\Events\CredentialVerified;
use App\Events\JobOrderClosed;
use App\Events\JobOrderCreated;
use App\Events\JobOrderFilled;
use App\Events\InvoiceGenerated;
use App\Events\MessageCreated;
use App\Events\PlacementCreated;
use App\Events\ShiftAssignmentApproved;
use App\Events\ShiftAssignmentRejected;
use App\Events\ShiftCancelled;
use App\Events\ShiftCompleted;
use App\Events\ShiftCreated;
use App\Events\ShiftRequestWithdrawn;
use App\Events\ShiftRequested;
use App\Events\SubmissionAccepted;
use App\Events\TimesheetApproved;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Log;

class ActivityLogListener
{
    public function __construct(
        private ActivityLogger $logger
    ) {}

    public function handle(object $event): void
    {
        // Tenant isolation: only log if the event has a tenantId property.
        $tenantId = $event->tenantId ?? null;
        if (!$tenantId) {
            return;
        }

        $entityType = match (true) {
            $event instanceof CandidateStageChanged,
            $event instanceof CandidateRecruiterAssigned => 'candidate_pipeline',
            $event instanceof JobOrderCreated,
            $event instanceof JobOrderFilled,
            $event instanceof JobOrderClosed => 'job_order',
            $event instanceof CredentialUploaded,
            $event instanceof CredentialVerified,
            $event instanceof CredentialRejected,
            $event instanceof CredentialExpiringSoon,
            $event instanceof CredentialExpired => 'candidate_credential',
            $event instanceof PlacementCreated => 'placement',
            $event instanceof SubmissionAccepted => 'submission',
            $event instanceof TimesheetApproved => 'timesheet',
            $event instanceof ShiftCreated,
            $event instanceof ShiftRequested,
            $event instanceof ShiftRequestWithdrawn,
            $event instanceof ShiftAssignmentApproved,
            $event instanceof ShiftAssignmentRejected,
            $event instanceof ShiftCompleted,
            $event instanceof ShiftCancelled => 'shift',
            $event instanceof InvoiceGenerated => 'invoice',
            $event instanceof MessageCreated => 'message',
            default => class_basename($event),
        };

        $entityId = match (true) {
            $event instanceof CandidateStageChanged => $event->pipeline->id,
            $event instanceof CandidateRecruiterAssigned => $event->pipeline->id,
            $event instanceof JobOrderCreated => $event->jobOrder->id,
            $event instanceof JobOrderFilled => $event->jobOrder->id,
            $event instanceof JobOrderClosed => $event->jobOrder->id,
            $event instanceof CredentialUploaded,
            $event instanceof CredentialVerified,
            $event instanceof CredentialRejected,
            $event instanceof CredentialExpiringSoon,
            $event instanceof CredentialExpired => $event->credential->id,
            $event instanceof PlacementCreated => $event->placement->id,
            $event instanceof SubmissionAccepted => $event->submission->id,
            $event instanceof TimesheetApproved => $event->timesheet->id,
            $event instanceof ShiftCreated => $event->shift->id,
            $event instanceof ShiftRequested => $event->shift->id,
            $event instanceof ShiftRequestWithdrawn => $event->shift->id,
            $event instanceof ShiftAssignmentApproved => $event->shift->id,
            $event instanceof ShiftAssignmentRejected => $event->shift->id,
            $event instanceof ShiftCompleted => $event->shift->id,
            $event instanceof ShiftCancelled => $event->shift->id,
            $event instanceof InvoiceGenerated => $event->invoice->id,
            $event instanceof MessageCreated => $event->message->id,
            default => null,
        };

        $candidateId = match (true) {
            $event instanceof CandidateStageChanged => $event->pipeline->candidate_id,
            $event instanceof CandidateRecruiterAssigned => $event->pipeline->candidate_id,
            $event instanceof CredentialUploaded,
            $event instanceof CredentialVerified,
            $event instanceof CredentialRejected,
            $event instanceof CredentialExpiringSoon,
            $event instanceof CredentialExpired => $event->credential->candidate_id,
            $event instanceof ShiftRequested => $event->request->candidate_id,
            $event instanceof ShiftRequestWithdrawn => $event->request->candidate_id,
            $event instanceof ShiftAssignmentApproved => $event->assignment->candidate_id,
            $event instanceof ShiftAssignmentRejected => $event->request->candidate_id,
            $event instanceof ShiftCompleted => $event->assignment?->candidate_id,
            default => null,
        };

        $credentialTypeId = match (true) {
            $event instanceof CredentialUploaded,
            $event instanceof CredentialVerified,
            $event instanceof CredentialRejected,
            $event instanceof CredentialExpiringSoon,
            $event instanceof CredentialExpired => $event->credential->credential_type_id,
            default => null,
        };

        $this->logger->log(
            tenantId: (int) $tenantId,
            entityType: $entityType,
            entityId: (int) $entityId,
            event: class_basename($event),
            data: [
                'actor_id' => $event->actor?->id ?? null,
                'actor_name' => $event->actor?->name ?? 'System',
                'candidate_id' => $candidateId,
                'credential_type_id' => $credentialTypeId,
            ],
            source: 'system'
        );
    }
}
