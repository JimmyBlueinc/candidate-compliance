<?php

namespace App\Events;

use App\Models\CandidatePipeline;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CandidateStageChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CandidatePipeline $pipeline,
        public readonly int $tenantId,
        public readonly string $previousStage,
        public readonly string $stage,
        public readonly ?User $actor = null,
    ) {}
}
