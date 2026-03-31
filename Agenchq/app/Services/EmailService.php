<?php

namespace App\Services;

use App\Jobs\SendEmailJob;
use App\Models\EmailJob;
use App\Models\EmailTemplate;

class EmailService
{
    public function queueTemplateEmail(int $tenantId, string $templateName, string $recipient, array $data): EmailJob
    {
        $template = EmailTemplate::query()
            ->where('tenant_id', $tenantId)
            ->where('name', $templateName)
            ->where('enabled', true)
            ->first();

        $subject = $template?->subject ?? ($data['subject'] ?? 'Notification');
        $body = $template?->body ?? ($data['body'] ?? '');

        $subject = $this->renderString((string) $subject, $data);
        $body = $this->renderString((string) $body, $data);

        $job = EmailJob::create([
            'tenant_id' => $tenantId,
            'template_id' => $template?->id,
            'recipient' => $recipient,
            'subject' => (string) $subject,
            'body' => (string) $body,
            'data' => $data,
            'status' => 'pending',
            'attempts' => 0,
        ]);

        SendEmailJob::dispatch($job->id);

        return $job;
    }

    private function renderString(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            if (!is_scalar($value) && $value !== null) {
                continue;
            }
            $template = str_replace('{{' . $key . '}}', (string) $value, $template);
        }

        return $template;
    }
}
