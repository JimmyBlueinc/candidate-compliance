<?php

namespace App\Listeners;

use App\Events\CandidateRecruiterAssigned;
use App\Events\CandidateStageChanged;
use App\Services\CommunicationService;

class CandidatePipelineCommunicationListener
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
            $event instanceof CandidateStageChanged => [
                'candidate_id' => $event->pipeline->candidate_id,
                'pipeline_id' => $event->pipeline->id,
                'previous_stage' => $event->previousStage,
                'stage' => $event->stage,
                'assigned_recruiter_id' => $event->pipeline->assigned_recruiter_id,
            ],
            $event instanceof CandidateRecruiterAssigned => [
                'candidate_id' => $event->pipeline->candidate_id,
                'pipeline_id' => $event->pipeline->id,
                'previous_recruiter_id' => $event->previousRecruiterId,
                'recruiter_id' => $event->recruiterId,
                'stage' => $event->pipeline->stage,
            ],
            default => [],
        };

        $this->communicationService->dispatchWebhook((int) $tenantId, $eventName, $payload);
    }
}
