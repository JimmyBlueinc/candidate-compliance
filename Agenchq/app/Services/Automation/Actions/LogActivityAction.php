<?php

namespace App\Services\Automation\Actions;

use App\Models\ActivityLog;

class LogActivityAction
{
    public function handle(object $event, array $config): void
    {
        $tenantId = $event->tenantId ?? null;
        if (!$tenantId) {
            return;
        }

        ActivityLog::create([
            'organization_id' => (int) $tenantId,
            'user_id' => $config['user_id'] ?? null,
            'action' => (string) ($config['action'] ?? 'automation'),
            'entity' => (string) ($config['entity'] ?? class_basename($event)),
            'entity_name' => (string) ($config['entity_name'] ?? class_basename($event)),
            'entity_id' => (int) ($config['entity_id'] ?? 0),
            'description' => (string) ($config['description'] ?? 'Automation executed'),
            'metadata' => (array) ($config['metadata'] ?? []),
        ]);
    }
}
