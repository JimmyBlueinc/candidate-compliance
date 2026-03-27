<?php

namespace App\Events;

use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\ShiftRequest;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShiftAssignmentApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ShiftAssignment $assignment,
        public readonly ShiftRequest $request,
        public readonly Shift $shift,
        public readonly int $tenantId,
        public readonly ?User $actor = null,
    ) {}
}
