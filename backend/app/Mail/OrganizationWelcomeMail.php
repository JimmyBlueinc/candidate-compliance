<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrganizationWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $organizationName,
        public string $loginUrl,
        public string $email,
        public string $tempPassword,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to AgencHQ — Your organization is ready',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.organization_welcome',
            with: [
                'organizationName' => $this->organizationName,
                'loginUrl' => $this->loginUrl,
                'email' => $this->email,
                'tempPassword' => $this->tempPassword,
            ],
        );
    }
}
