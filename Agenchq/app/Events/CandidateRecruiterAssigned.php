<?php

namespace App\Events;

use App\Models\CandidatePipeline;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CandidateRecruiterAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CandidatePipeline $pipeline,
        public readonly int $tenantId,
        public readonly ?int $previousRecruiterId,
        public readonly ?int $recruiterId,
        public readonly ?User $actor = null,
    ) {}
}
