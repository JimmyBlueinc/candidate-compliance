<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TalentNetworkWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $organizationName,
        public string $profileUrl,
        public ?string $name = null,
        public ?string $tempPassword = null,
        public ?string $loginUrl = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to the Talent Network',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.talent_network_welcome',
            with: [
                'organizationName' => $this->organizationName,
                'profileUrl' => $this->profileUrl,
                'name' => $this->name,
                'tempPassword' => $this->tempPassword,
                'loginUrl' => $this->loginUrl,
            ],
        );
    }
}
