<?php

namespace App\Events;

use App\Models\JobOrder;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JobOrderClosed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly JobOrder $jobOrder,
        public readonly int $tenantId,
        public readonly string $previousStatus,
        public readonly string $status,
        public readonly ?User $actor = null,
    ) {}
}
