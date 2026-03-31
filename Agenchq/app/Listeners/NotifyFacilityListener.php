<?php

namespace App\Listeners;

use App\Events\InvoiceGenerated;
use App\Events\TimesheetSubmitted;
use App\Models\User;
use App\Services\NotificationService;

class NotifyFacilityListener
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle(object $event): void
    {
        if ($event instanceof TimesheetSubmitted) {
            $timesheet = $event->timesheet->loadMissing(['assignment', 'candidate']);

            $facilityId = $timesheet->assignment?->facility_id;
            if (!$facilityId) {
                return;
            }

            $facilityUserIds = User::query()
                ->where('organization_id', $event->tenantId)
                ->where('facility_id', $facilityId)
                ->pluck('id')
                ->toArray();

            $this->notificationService->notify(
                $facilityUserIds,
                'timesheet_submitted',
                'timesheet',
                (int) $timesheet->id,
                [
                    'candidate_name' => $timesheet->candidate?->name,
                    'week_start_date' => $timesheet->week_start_date?->format('Y-m-d'),
                ],
                $event->tenantId
            );

            return;
        }

        if ($event instanceof InvoiceGenerated) {
            $invoice = $event->invoice;

            if (!$invoice->facility_id) {
                return;
            }

            $facilityUserIds = User::query()
                ->where('organization_id', $event->tenantId)
                ->where('facility_id', $invoice->facility_id)
                ->pluck('id')
                ->toArray();

            $this->notificationService->notify(
                $facilityUserIds,
                'invoice_generated',
                'invoice',
                (int) $invoice->id,
                [
                    'invoice_number' => 'INV-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT),
                    'amount' => $invoice->total_amount,
                ],
                $event->tenantId
            );
        }
    }
}
