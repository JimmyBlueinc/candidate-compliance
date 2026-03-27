<?php

namespace App\Events;

use App\Models\CandidateCredential;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CredentialVerified
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $tenantId,
        public CandidateCredential $credential,
        public ?User $actor = null
    ) {}
}
