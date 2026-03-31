<?php

namespace App\Services;

use App\Models\CommunicationLog;

class CommunicationService
{
    public function __construct(
        private EmailService $emailService,
        private WebhookDispatcher $webhookDispatcher,
        private NotificationService $notificationService
    ) {}

    public function sendEmail(int $tenantId, string $templateName, string $recipient, array $data): void
    {
        $emailJob = $this->emailService->queueTemplateEmail($tenantId, $templateName, $recipient, $data);

        CommunicationLog::create([
            'tenant_id' => $tenantId,
            'type' => 'email',
            'event' => $templateName,
            'entity_type' => 'email_job',
            'entity_id' => $emailJob->id,
            'recipient' => $recipient,
            'status' => 'queued',
            'metadata' => [
                'template' => $templateName,
            ],
        ]);
    }

    public function dispatchWebhook(int $tenantId, string $event, array $payload): void
    {
        $deliveries = $this->webhookDispatcher->dispatch($tenantId, $event, $payload);

        foreach ($deliveries as $delivery) {
            CommunicationLog::create([
                'tenant_id' => $tenantId,
                'type' => 'webhook',
                'event' => $event,
                'entity_type' => 'webhook_delivery',
                'entity_id' => $delivery->id,
                'recipient' => null,
                'status' => 'queued',
                'metadata' => [
                    'event' => $event,
                ],
            ]);
        }
    }

    public function sendNotification(int $tenantId, int $userId, string $type, array $data): void
    {
        $this->notificationService->notify([$userId], $type, $data['entity_type'] ?? 'notification', (int) ($data['entity_id'] ?? 0), $data, $tenantId);

        CommunicationLog::create([
            'tenant_id' => $tenantId,
            'type' => 'notification',
            'event' => $type,
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'recipient' => (string) $userId,
            'status' => 'sent',
            'metadata' => [
                'type' => $type,
            ],
        ]);
    }
}
