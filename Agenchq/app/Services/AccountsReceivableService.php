<?php

namespace App\Services;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AccountsReceivableService
{
    /**
     * Calculate AR aging grouped by facility.
     *
     * @param int|null $tenantId
     * @return array
     */
    public function getAgingSummary(?int $tenantId = null): array
    {
        $invoices = Invoice::query()
            ->with(['payments'])
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->where('status', '!=', 'paid')
            ->get();

        $now = Carbon::now();
        $summary = [
            'total_ar' => 0,
            'buckets' => [
                '0-30' => 0,
                '31-60' => 0,
                '61-90' => 0,
                '90+' => 0,
            ],
            'by_facility' => []
        ];

        foreach ($invoices as $invoice) {
            $totalAmount = (float) $invoice->total_amount;
            $paymentsSum = (float) $invoice->payments->sum('amount');
            $balanceDue = $totalAmount - $paymentsSum;

            if ($balanceDue <= 0) {
                continue;
            }

            $daysOld = $now->diffInDays($invoice->created_at);
            $bucket = $this->getBucket($daysOld);
            $facility = $invoice->facility_name ?: 'Unknown Facility';

            // Update global totals
            $summary['total_ar'] += $balanceDue;
            $summary['buckets'][$bucket] += $balanceDue;

            // Update facility totals
            if (!isset($summary['by_facility'][$facility])) {
                $summary['by_facility'][$facility] = [
                    'total_ar' => 0,
                    'buckets' => [
                        '0-30' => 0,
                        '31-60' => 0,
                        '61-90' => 0,
                        '90+' => 0,
                    ]
                ];
            }

            $summary['by_facility'][$facility]['total_ar'] += $balanceDue;
            $summary['by_facility'][$facility]['buckets'][$bucket] += $balanceDue;
        }

        // Round all numbers
        $this->roundValues($summary);

        return $summary;
    }

    /**
     * Determine the aging bucket based on days.
     */
    private function getBucket(int $days): string
    {
        if ($days <= 30) return '0-30';
        if ($days <= 60) return '31-60';
        if ($days <= 90) return '61-90';
        return '90+';
    }

    /**
     * Round all financial values in the summary.
     */
    private function roundValues(array &$summary): void
    {
        $summary['total_ar'] = round($summary['total_ar'], 2);
        foreach ($summary['buckets'] as $key => $value) {
            $summary['buckets'][$key] = round($value, 2);
        }

        foreach ($summary['by_facility'] as $facility => &$data) {
            $data['total_ar'] = round($data['total_ar'], 2);
            foreach ($data['buckets'] as $key => $value) {
                $data['buckets'][$key] = round($value, 2);
            }
        }
    }
}
