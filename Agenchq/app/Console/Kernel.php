<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('credentials:send-reminders')
            ->dailyAt('08:00');

        $schedule->command('shifts:send-reminders')
            ->hourly();

        $schedule->command('availability:rebuild-index')
            ->dailyAt('02:00');

        $schedule->command('credentials:send-summary')
            ->dailyAt('09:00');

        $schedule->command('credentials:check-expirations')
            ->dailyAt('07:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
