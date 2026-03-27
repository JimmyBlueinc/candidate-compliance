<?php

namespace App\Events;

use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TimesheetApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Timesheet $timesheet,
        public readonly int $tenantId,
        public readonly ?User $actor = null,
    ) {}
}
