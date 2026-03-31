<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Notify specific users.
     */
    public function notify(array $userIds, string $type, string $entityType, int $entityId, array $data, int $tenantId): void
    {
        foreach ($userIds as $userId) {
            try {
                Notification::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'type' => $type,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'data' => $data,
                    'created_at' => now(),
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to create notification for user {$userId}: " . $e->getMessage());
            }
        }
    }

    /**
     * Notify all admins and recruiters of a tenant.
     */
    public function notifyAdmins(int $tenantId, string $type, string $entityType, int $entityId, array $data): void
    {
        $adminIds = User::where('organization_id', $tenantId)
            ->whereIn('role', ['admin', 'org_super_admin', 'recruiter', 'compliance', 'scheduler', 'finance', 'logistics'])
            ->pluck('id')
            ->toArray();

        $this->notify($adminIds, $type, $entityType, $entityId, $data, $tenantId);
    }
}
