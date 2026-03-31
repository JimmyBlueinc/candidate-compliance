<?php

namespace App\Mail;

use App\Models\CandidateCredential;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CandidateCredentialExpiryReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CandidateCredential $credential,
        public int $daysUntilExpiry
    ) {
    }

    public function envelope(): Envelope
    {
        $type = (string) ($this->credential->credentialType?->name ?? 'Credential');
        return new Envelope(
            subject: "Credential Expiry Alert: {$type} expires in {$this->daysUntilExpiry} day(s)"
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.candidate-credential-expiry-reminder',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
