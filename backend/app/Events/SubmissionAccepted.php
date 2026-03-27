<?php

namespace App\Events;

use App\Models\Placement;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubmissionAccepted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Submission $submission,
        public readonly Placement $placement,
        public readonly int $tenantId,
        public readonly ?User $actor = null,
    ) {}
}
