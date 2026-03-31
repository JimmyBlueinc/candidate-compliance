<?php

namespace App\Events;

use App\Models\CandidateCredential;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CredentialExpiringSoon
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $tenantId,
        public CandidateCredential $credential,
        public int $daysRemaining
    ) {}
}
