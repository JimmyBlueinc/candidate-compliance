<?php

namespace App\Events;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShiftCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Shift $shift,
        public readonly int $tenantId,
        public readonly ?User $actor = null,
    ) {}
}
