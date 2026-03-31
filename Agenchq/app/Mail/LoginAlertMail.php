<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $organizationName,
        public string $ip,
        public string $userAgent,
        public string $loggedInAt,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New login to your AgencHQ account',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.login_alert',
            with: [
                'name' => $this->name,
                'organizationName' => $this->organizationName,
                'ip' => $this->ip,
                'userAgent' => $this->userAgent,
                'loggedInAt' => $this->loggedInAt,
            ],
        );
    }
}
