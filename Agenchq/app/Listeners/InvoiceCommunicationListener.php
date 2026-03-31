<?php

namespace App\Listeners;

use App\Events\InvoiceGenerated;
use App\Services\CommunicationService;

class InvoiceCommunicationListener
{
    public function __construct(
        private CommunicationService $communicationService
    ) {}

    public function handle(InvoiceGenerated $event): void
    {
        $payload = [
            'invoice_id' => $event->invoice->id,
            'facility_id' => $event->invoice->facility_id,
            'assignment_id' => $event->invoice->assignment_id,
            'status' => $event->invoice->status,
            'total_amount' => $event->invoice->total_amount,
            'issued_at' => $event->invoice->issued_at,
            'due_at' => $event->invoice->due_at,
        ];

        $this->communicationService->dispatchWebhook((int) $event->tenantId, class_basename($event), $payload);
    }
}
