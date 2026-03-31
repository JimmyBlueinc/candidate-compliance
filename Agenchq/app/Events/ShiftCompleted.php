<?php

namespace App\Events;

use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShiftCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Shift $shift,
        public readonly ?ShiftAssignment $assignment,
        public readonly ?Timesheet $timesheet,
        public readonly int $tenantId,
        public readonly ?User $actor = null,
    ) {}
}
