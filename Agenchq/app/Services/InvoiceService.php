<?php

namespace App\Services;

use App\Events\InvoiceGenerated;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Timesheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function generateInvoiceFromTimesheets(int $tenantId, array $timesheetIds, ?User $actor = null): Invoice
    {
        return DB::transaction(function () use ($tenantId, $timesheetIds, $actor) {
            $timesheets = Timesheet::query()
                ->with([
                    'entries:id,timesheet_id,hours_worked,overtime_hours',
                    'assignment:id,tenant_id,facility_id,facility_name,bill_rate,placement_id',
                ])
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $timesheetIds)
                ->where('status', 'agency_approved')
                ->get();

            if ($timesheets->count() === 0) {
                abort(422, 'No agency-approved timesheets provided.');
            }

            $first = $timesheets->first();
            $assignment = $first->assignment;
            if (!$assignment) {
                abort(422, 'Timesheet assignment missing.');
            }

            $weekStart = $first->week_start_date ? Carbon::parse($first->week_start_date)->startOfDay() : null;
            if (!$weekStart) {
                abort(422, 'Timesheet week_start_date missing.');
            }

            $invoice = Invoice::firstOrCreate([
                'tenant_id' => $tenantId,
                'assignment_id' => (int) $assignment->id,
                'week_start_date' => $weekStart->toDateTimeString(),
            ], [
                'facility_id' => $assignment->facility_id,
                'facility_name' => $assignment->facility_name,
                'week_end_date' => $weekStart->copy()->addDays(6)->toDateTimeString(),
                'bill_rate' => (float) ($assignment->bill_rate ?? 0),
                'status' => 'draft',
                'created_by_user_id' => $actor?->id,
            ]);

            foreach ($timesheets as $t) {
                $hours = 0.0;
                foreach ($t->entries as $e) {
                    $hours += (float) ($e->hours_worked ?? 0);
                    $hours += (float) ($e->overtime_hours ?? 0);
                }

                $billRate = (float) ($assignment->bill_rate ?? 0);
                $amount = $hours * $billRate;

                $this->addLineItem($tenantId, (int) $invoice->id, (int) $t->id, (int) ($assignment->placement_id ?? 0) ?: null, $hours, $billRate, $actor);
            }

            $invoice->refresh();
            if ($invoice->wasRecentlyCreated) {
                InvoiceGenerated::dispatch($invoice, $tenantId);
            }

            return $invoice;
        });
    }

    public function addLineItem(
        int $tenantId,
        int $invoiceId,
        int $timesheetId,
        ?int $placementId,
        float $hours,
        float $billRate,
        ?User $actor = null
    ): InvoiceLineItem {
        return DB::transaction(function () use ($tenantId, $invoiceId, $timesheetId, $placementId, $hours, $billRate) {
            $invoice = Invoice::query()
                ->where('tenant_id', $tenantId)
                ->findOrFail($invoiceId);

            $amount = $hours * $billRate;

            $line = InvoiceLineItem::updateOrCreate([
                'invoice_id' => $invoice->id,
                'timesheet_id' => $timesheetId,
            ], [
                'placement_id' => $placementId,
                'hours' => round($hours, 2),
                'bill_rate' => round($billRate, 2),
                'amount' => round($amount, 2),
            ]);

            $totals = InvoiceLineItem::query()
                ->where('invoice_id', $invoice->id)
                ->selectRaw('COALESCE(SUM(hours),0) as total_hours, COALESCE(SUM(amount),0) as total_amount')
                ->first();

            $invoice->total_hours = round((float) ($totals->total_hours ?? 0), 2);
            $invoice->total_amount = round((float) ($totals->total_amount ?? 0), 2);
            $invoice->save();

            return $line;
        });
    }

    public function issueInvoice(int $tenantId, int $invoiceId, ?User $actor = null, ?\DateTimeInterface $dueAt = null): Invoice
    {
        return DB::transaction(function () use ($tenantId, $invoiceId, $actor, $dueAt) {
            $invoice = Invoice::query()
                ->where('tenant_id', $tenantId)
                ->findOrFail($invoiceId);

            if ((string) $invoice->status !== 'draft') {
                return $invoice;
            }

            $invoice->status = 'issued';
            $invoice->issued_at = now();
            $invoice->due_at = $dueAt ? Carbon::instance($dueAt) : null;
            if (!$invoice->created_by_user_id) {
                $invoice->created_by_user_id = $actor?->id;
            }
            $invoice->save();

            return $invoice;
        });
    }

    public function markInvoicePaid(int $tenantId, int $invoiceId, ?User $actor = null): Invoice
    {
        return DB::transaction(function () use ($tenantId, $invoiceId) {
            $invoice = Invoice::query()
                ->where('tenant_id', $tenantId)
                ->findOrFail($invoiceId);

            if ((string) $invoice->status === 'paid') {
                return $invoice;
            }

            $invoice->status = 'paid';
            $invoice->save();

            return $invoice;
        });
    }
}
