<?php

namespace App\Services\Automation\Actions;

use App\Services\NotificationService;

class SendNotificationAction
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle(object $event, array $config): void
    {
        $tenantId = $event->tenantId ?? null;
        if (!$tenantId) {
            return;
        }

        $type = (string) ($config['type'] ?? 'automation');
        $entityType = (string) ($config['entity_type'] ?? 'automation');
        $entityId = (int) ($config['entity_id'] ?? 0);
        $data = (array) ($config['data'] ?? []);

        if (!empty($config['notify_admins'])) {
            $this->notificationService->notifyAdmins((int) $tenantId, $type, $entityType, $entityId, $data);
            return;
        }

        $userIds = (array) ($config['user_ids'] ?? []);
        $userIds = array_values(array_filter(array_map('intval', $userIds), fn ($v) => $v > 0));

        if (count($userIds) === 0) {
            return;
        }

        $this->notificationService->notify($userIds, $type, $entityType, $entityId, $data, (int) $tenantId);
    }
}
