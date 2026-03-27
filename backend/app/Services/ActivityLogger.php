<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Log a system activity.
     */
    public function log(int $tenantId, string $entityType, ?int $entityId, string $event, array $data = [], string $source = 'system'): ActivityLog
    {
        $userId = Auth::id();
        if (!$userId && array_key_exists('actor_id', $data)) {
            $userId = $data['actor_id'];
        }

        if (!$userId) {
            return ActivityLog::make([
                'tenant_id' => $tenantId,
                'organization_id' => $tenantId,
                'user_id' => null,
                'old_action' => 'event',
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'event' => $event,
                'source' => $source,
                'data' => $data,
                'description' => "Event {$event} occurred on {$entityType} #{$entityId}",
                'created_at' => now(),
            ]);
        }

        return ActivityLog::create([
            'tenant_id' => $tenantId,
            'organization_id' => $tenantId, // Legacy field support
            'user_id' => $userId,
            'old_action' => 'event',
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'event' => $event,
            'source' => $source,
            'data' => $data,
            'description' => "Event {$event} occurred on {$entityType} #{$entityId}",
            'created_at' => now(),
        ]);
    }
}
