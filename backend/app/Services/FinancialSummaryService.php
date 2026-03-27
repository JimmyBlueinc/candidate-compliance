<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialSummaryService
{
    /**
     * Compute high-level financial metrics.
     *
     * @param int|null $tenantId
     * @return array
     */
    public function getSummary(?int $tenantId = null): array
    {
        // 1. Total Invoiced
        $totalInvoiced = (float) Invoice::query()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        // 2. Total Collected
        $totalCollected = (float) Payment::query()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->sum('amount');

        // 3. Outstanding AR
        $outstandingAR = $totalInvoiced - $totalCollected;

        // 4. Avg Days to Pay
        // Logic: average(payment.payment_date - invoice.created_at)
        // Since we don't have an 'issued_at' field yet, we use 'created_at' as the baseline.
        $avgDaysToPay = 0;

        $paymentRows = DB::table('payments')
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->when($tenantId, fn($q) => $q->where('payments.tenant_id', $tenantId))
            ->select(['payments.payment_date', 'invoices.created_at'])
            ->get();

        $days = [];
        foreach ($paymentRows as $row) {
            if (!$row->payment_date || !$row->created_at) {
                continue;
            }

            $paidAt = Carbon::parse($row->payment_date)->startOfDay();
            $invoicedAt = Carbon::parse($row->created_at)->startOfDay();
            $days[] = $invoicedAt->diffInDays($paidAt, false);
        }

        if (count($days) > 0) {
            $avgDaysToPay = array_sum($days) / count($days);
        }

        return [
            'total_invoiced' => round($totalInvoiced, 2),
            'total_collected' => round($totalCollected, 2),
            'outstanding_ar' => round(max(0, $outstandingAR), 2),
            'avg_days_to_pay' => round($avgDaysToPay, 1),
            'currency' => 'USD',
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
