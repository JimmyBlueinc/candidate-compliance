<?php

namespace App\Events;

use App\Models\Placement;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlacementCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Placement $placement,
        public readonly int $tenantId,
        public readonly ?User $actor = null,
    ) {}
}
