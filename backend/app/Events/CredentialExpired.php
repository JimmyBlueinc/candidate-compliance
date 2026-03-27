<?php

namespace App\Events;

use App\Models\CandidateCredential;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CredentialExpired
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $tenantId,
        public CandidateCredential $credential
    ) {}
}
