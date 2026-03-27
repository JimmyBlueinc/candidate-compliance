<?php

namespace App\Services;

use App\Events\InvoiceGenerated;
use App\Models\Invoice;
use App\Models\Timesheet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceGenerationService
{
    public function generate(?int $tenantId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $timesheets = Timesheet::query()
            ->with([
                'entries:id,timesheet_id,hours_worked,overtime_hours',
                'assignment:id,tenant_id,facility_id,facility_name,bill_rate',
            ])
            ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId))
            ->when($startDate !== null && $startDate !== '', fn ($q) => $q->whereDate('week_start_date', '>=', $startDate))
            ->when($endDate !== null && $endDate !== '', fn ($q) => $q->whereDate('week_start_date', '<=', $endDate))
            ->where('status', 'agency_approved')
            ->orderBy('assignment_id')
            ->orderBy('week_start_date')
            ->get();

        $created = 0;
        $skipped = 0;
        $errors = 0;

        DB::beginTransaction();
        try {
            foreach ($timesheets as $t) {
                $assignment = $t->assignment;
                if (!$assignment) {
                    $errors++;
                    continue;
                }

                $weekStartDate = $t->week_start_date;
                if (!$weekStartDate) {
                    $errors++;
                    continue;
                }

                $weekStart = Carbon::parse($weekStartDate)->startOfDay();
                $weekStartKey = $weekStart->toDateTimeString();

                $weekEnd = $weekStart->copy()->addDays(6)->toDateTimeString();

                $hours = 0.0;
                foreach ($t->entries as $e) {
                    $hours += (float) ($e->hours_worked ?? 0);
                    $hours += (float) ($e->overtime_hours ?? 0);
                }

                $billRate = (float) ($assignment->bill_rate ?? 0);
                $amount = $hours * $billRate;

                $invoice = Invoice::firstOrCreate([
                    'tenant_id' => (int) $t->tenant_id,
                    'assignment_id' => (int) $assignment->id,
                    'week_start_date' => $weekStartKey,
                ], [
                    'facility_id' => $assignment->facility_id,
                    'facility_name' => $assignment->facility_name,
                    'week_end_date' => $weekEnd,
                    'total_hours' => round($hours, 2),
                    'bill_rate' => round($billRate, 2),
                    'total_amount' => round($amount, 2),
                    'status' => 'draft',
                ]);

                if ($invoice->wasRecentlyCreated) {
                    $created++;

                    InvoiceGenerated::dispatch($invoice, (int) $t->tenant_id);
                } else {
                    $skipped++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'created' => $created,
            'skipped_existing' => $skipped,
            'errors' => $errors,
        ];
    }
}
