<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $organizationName,
        public string $loginUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to AgencHQ',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user_welcome',
            with: [
                'name' => $this->name,
                'organizationName' => $this->organizationName,
                'loginUrl' => $this->loginUrl,
            ],
        );
    }
}
