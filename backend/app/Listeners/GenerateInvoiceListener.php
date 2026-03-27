<?php

namespace App\Listeners;

use App\Events\TimesheetApproved;
use App\Services\InvoiceService;

class GenerateInvoiceListener
{
    public function __construct(
        private InvoiceService $invoiceService
    ) {}

    public function handle(TimesheetApproved $event): void
    {
        // Tenant isolation: generate invoice only for this tenant
        $this->invoiceService->generateInvoiceFromTimesheets(
            tenantId: (int) $event->tenantId,
            timesheetIds: [(int) $event->timesheet->id],
            actor: $event->actor
        );
    }
}
