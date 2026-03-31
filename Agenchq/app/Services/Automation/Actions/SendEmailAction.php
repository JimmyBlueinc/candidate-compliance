<?php

namespace App\Services\Automation\Actions;

use Illuminate\Support\Facades\Mail;

class SendEmailAction
{
    public function handle(object $event, array $config): void
    {
        $to = $config['to'] ?? null;
        $subject = (string) ($config['subject'] ?? 'Notification');
        $body = (string) ($config['body'] ?? '');

        if (!$to) {
            return;
        }

        Mail::raw($body, function ($message) use ($to, $subject) {
            $message->to($to)->subject($subject);
        });
    }
}
