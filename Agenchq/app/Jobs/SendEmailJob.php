<?php

namespace App\Jobs;

use App\Models\EmailJob;
use App\Models\EmailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $emailJobId
    ) {}

    public function handle(): void
    {
        $job = EmailJob::query()->find($this->emailJobId);
        if (!$job) {
            return;
        }

        $job->status = 'processing';
        $job->attempts = (int) ($job->attempts ?? 0) + 1;
        $job->save();

        $subject = $job->subject ?? ($job->data['subject'] ?? 'Notification');
        $body = $job->body ?? ($job->data['body'] ?? '');

        try {
            Mail::raw((string) $body, function ($message) use ($job, $subject) {
                $message->to($job->recipient)->subject((string) $subject);
            });

            EmailLog::create([
                'tenant_id' => $job->tenant_id,
                'recipient' => $job->recipient,
                'subject' => (string) $subject,
                'provider' => 'laravel_mail',
                'status' => 'sent',
                'response' => null,
                'sent_at' => now(),
            ]);

            $job->status = 'sent';
            $job->sent_at = now();
            $job->save();
        } catch (\Throwable $e) {
            EmailLog::create([
                'tenant_id' => $job->tenant_id,
                'recipient' => $job->recipient,
                'subject' => (string) $subject,
                'provider' => 'laravel_mail',
                'status' => 'failed',
                'response' => $e->getMessage(),
                'sent_at' => now(),
            ]);

            $job->status = 'failed';
            $job->save();

            throw $e;
        }
    }
}
