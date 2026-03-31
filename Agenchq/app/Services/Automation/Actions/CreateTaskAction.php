<?php

namespace App\Services\Automation\Actions;

use App\Models\ActivityLog;

class CreateTaskAction
{
    public function handle(object $event, array $config): void
    {
        $tenantId = $event->tenantId ?? null;
        if (!$tenantId) {
            return;
        }

        ActivityLog::create([
            'organization_id' => (int) $tenantId,
            'user_id' => $config['assigned_to_user_id'] ?? null,
            'action' => 'task_created',
            'entity' => (string) ($config['entity'] ?? class_basename($event)),
            'entity_name' => (string) ($config['title'] ?? 'Task'),
            'entity_id' => (int) ($config['entity_id'] ?? 0),
            'description' => (string) ($config['description'] ?? 'Task created by automation'),
            'metadata' => (array) ($config['metadata'] ?? []),
        ]);
    }
}
