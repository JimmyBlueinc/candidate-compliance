<?php

namespace App\Services\Automation\Actions;

use App\Services\InvoiceGenerationService;

class GenerateInvoiceAction
{
    public function __construct(
        private InvoiceGenerationService $invoiceService
    ) {}

    public function handle(object $event, array $config): void
    {
        $tenantId = $event->tenantId ?? null;
        if (!$tenantId) {
            return;
        }

        $this->invoiceService->generate((int) $tenantId);
    }
}
