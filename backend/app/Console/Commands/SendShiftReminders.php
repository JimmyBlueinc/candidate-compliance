<?php

namespace App\Console\Commands;

use App\Models\Shift;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendShiftReminders extends Command
{
    protected $signature = 'shifts:send-reminders {--tenantId=}';

    protected $description = 'Send reminders for upcoming shifts.';

    public function __construct(
        private NotificationService $notificationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantId = $this->option('tenantId');

        $total = 0;
        $total += $this->sendWindowReminders($tenantId ? (int) $tenantId : null, now(), now()->addHours(24), 'shift_reminder_24h');
        $total += $this->sendWindowReminders($tenantId ? (int) $tenantId : null, now(), now()->addHours(2), 'shift_reminder_2h');

        $this->info('Shift reminders sent: ' . $total);

        return Command::SUCCESS;
    }

    private function sendWindowReminders(?int $tenantId, $from, $to, string $type): int
    {
        $query = Shift::query()->withoutGlobalScopes()
            ->whereIn('status', ['open', 'assigned', 'in_progress'])
            ->whereBetween('starts_at', [$from, $to]);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $count = 0;
        $query->chunkById(200, function ($shifts) use (&$count, $type) {
            foreach ($shifts as $shift) {
                $tenantId = (int) $shift->tenant_id;

                $this->notificationService->notifyAdmins(
                    tenantId: $tenantId,
                    type: $type,
                    entityType: 'shift',
                    entityId: (int) $shift->id,
                    data: [
                        'shift_id' => (int) $shift->id,
                        'starts_at' => $shift->starts_at?->toIso8601String(),
                        'ends_at' => $shift->ends_at?->toIso8601String(),
                        'facility_id' => $shift->facility_id,
                        'status' => $shift->status,
                    ]
                );

                $count++;
            }
        });

        return $count;
    }
}
