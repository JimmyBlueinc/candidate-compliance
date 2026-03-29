<?php

namespace App\Listeners;

use App\Events\MessageCreated;
use App\Events\SubmissionAccepted;
use App\Models\JobOrder;
use App\Models\Placement;
use App\Models\Submission;
use App\Models\User;
use App\Services\NotificationService;

class NotifyRecruiterListener
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle(object $event): void
    {
        if ($event instanceof SubmissionAccepted) {
            $submission = $event->submission->loadMissing(['candidate', 'jobOrder']);

            $this->notificationService->notifyAdmins(
                $event->tenantId,
                'submission_accepted',
                'placement',
                (int) $event->placement->id,
                [
                    'candidate_name' => $submission->candidate?->name,
                    'job_title' => $submission->jobOrder?->title,
                    'facility_name' => $submission->jobOrder?->facility_name,
                ]
            );

            return;
        }

        if ($event instanceof MessageCreated) {
            $actor = $event->actor;
            $message = $event->message;

            if ($message->recipient_id && (int) $message->recipient_id !== (int) $actor->id) {
                $this->notificationService->notify(
                    [(int) $message->recipient_id],
                    'message',
                    'message',
                    (int) $message->id,
                    [
                        'message' => substr($message->body, 0, 50),
                        'from' => $actor->name,
                        'sender_id' => (int) $actor->id,
                    ],
                    $event->tenantId
                );
            }

            if ($actor->facility_id) {
                // Message from facility -> notify tenant admins/recruiters
                $this->notificationService->notifyAdmins(
                    $event->tenantId,
                    'new_message',
                    $this->entityTypeFromMessage($message),
                    (int) $this->entityIdFromMessage($message),
                    [
                        'message' => substr($message->body, 0, 50),
                        'from' => $actor->name,
                        'sender_id' => (int) $actor->id,
                    ]
                );
                return;
            }

            // Message from agency -> notify facility users of target facility
            $facilityId = $this->facilityIdFromMessage($event->tenantId, $message);
            if (!$facilityId) {
                return;
            }

            $facilityUserIds = User::query()
                ->where('organization_id', $event->tenantId)
                ->where('facility_id', $facilityId)
                ->pluck('id')
                ->toArray();

            $this->notificationService->notify(
                $facilityUserIds,
                'new_message',
                $this->entityTypeFromMessage($message),
                (int) $this->entityIdFromMessage($message),
                [
                    'message' => substr($message->body, 0, 50),
                    'from' => $actor->name,
                    'sender_id' => (int) $actor->id,
                ],
                $event->tenantId
            );
        }
    }

    private function entityTypeFromMessage($message): string
    {
        if ($message->job_order_id) return 'job_order';
        if ($message->submission_id) return 'submission';
        return 'placement';
    }

    private function entityIdFromMessage($message): int
    {
        return (int) ($message->job_order_id ?: ($message->submission_id ?: $message->placement_id));
    }

    private function facilityIdFromMessage(int $tenantId, $message): ?int
    {
        if ($message->job_order_id) {
            return JobOrder::query()->where('tenant_id', $tenantId)->find($message->job_order_id)?->facility_id;
        }

        if ($message->submission_id) {
            $submission = Submission::query()->with('jobOrder')->where('tenant_id', $tenantId)->find($message->submission_id);
            return $submission?->jobOrder?->facility_id;
        }

        if ($message->placement_id) {
            $placement = Placement::query()->where('tenant_id', $tenantId)->find($message->placement_id);
            if (!$placement) {
                return null;
            }
            $job = JobOrder::query()->where('tenant_id', $tenantId)->find($placement->job_order_id);
            return $job?->facility_id;
        }

        return null;
    }
}
