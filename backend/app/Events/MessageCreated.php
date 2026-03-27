<?php

namespace App\Events;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Message $message,
        public readonly int $tenantId,
        public readonly User $actor,
    ) {}
}
