<?php

namespace App\Services;

use App\Models\Timesheet;

class TimesheetRevenueService
{
    public function calculate(?int $tenantId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $timesheets = Timesheet::query()
            ->with([
                'entries:id,timesheet_id,hours_worked,overtime_hours',
                'assignment:id,tenant_id,recruiter_id,facility_name,bill_rate,pay_rate',
                'assignment.recruiter:id,name',
            ])
            ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId))
            ->when($startDate !== null && $startDate !== '', fn ($q) => $q->whereDate('week_start_date', '>=', $startDate))
            ->when($endDate !== null && $endDate !== '', fn ($q) => $q->whereDate('week_start_date', '<=', $endDate))
            ->where('status', 'agency_approved')
            ->get();

        $totals = $this->emptyMetrics();
        $byFacility = [];
        $byRecruiter = [];
        $byAssignment = [];

        foreach ($timesheets as $t) {
            $assignment = $t->assignment;
            if (!$assignment) {
                continue;
            }

            $facilityName = (string) ($assignment->facility_name ?? 'Unknown');
            $recruiterId = $assignment->recruiter_id ? (int) $assignment->recruiter_id : null;
            $recruiterName = $assignment->recruiter?->name;
            $assignmentId = (int) $assignment->id;

            $hours = 0.0;
            foreach ($t->entries as $e) {
                $hours += (float) ($e->hours_worked ?? 0);
                $hours += (float) ($e->overtime_hours ?? 0);
            }

            $billRate = (float) ($assignment->bill_rate ?? 0);
            $payRate = (float) ($assignment->pay_rate ?? 0);

            $gross = $hours * $billRate;
            $labor = $hours * $payRate;

            $this->addMetrics($totals, $hours, $gross, $labor);

            if (!isset($byFacility[$facilityName])) {
                $byFacility[$facilityName] = array_merge([
                    'facility_name' => $facilityName,
                ], $this->emptyMetrics());
            }
            $this->addMetrics($byFacility[$facilityName], $hours, $gross, $labor);

            $recruiterKey = $recruiterId !== null ? (string) $recruiterId : 'unassigned';
            if (!isset($byRecruiter[$recruiterKey])) {
                $byRecruiter[$recruiterKey] = array_merge([
                    'recruiter' => $recruiterId !== null ? [
                        'id' => $recruiterId,
                        'name' => $recruiterName,
                    ] : null,
                ], $this->emptyMetrics());
            }
            $this->addMetrics($byRecruiter[$recruiterKey], $hours, $gross, $labor);

            $assignmentKey = (string) $assignmentId;
            if (!isset($byAssignment[$assignmentKey])) {
                $byAssignment[$assignmentKey] = array_merge([
                    'assignment' => [
                        'id' => $assignmentId,
                        'facility_name' => $facilityName,
                        'recruiter' => $recruiterId !== null ? [
                            'id' => $recruiterId,
                            'name' => $recruiterName,
                        ] : null,
                        'bill_rate' => $assignment->bill_rate,
                        'pay_rate' => $assignment->pay_rate,
                    ],
                ], $this->emptyMetrics());
            }
            $this->addMetrics($byAssignment[$assignmentKey], $hours, $gross, $labor);
        }

        $this->finalizeMetrics($totals);
        foreach ($byFacility as &$row) {
            $this->finalizeMetrics($row);
        }
        foreach ($byRecruiter as &$row) {
            $this->finalizeMetrics($row);
        }
        foreach ($byAssignment as &$row) {
            $this->finalizeMetrics($row);
        }

        return [
            'totals' => $totals,
            'by_facility' => array_values($byFacility),
            'by_recruiter' => array_values($byRecruiter),
            'by_assignment' => array_values($byAssignment),
        ];
    }

    private function emptyMetrics(): array
    {
        return [
            'total_hours' => 0.0,
            'gross_revenue' => 0.0,
            'labor_cost' => 0.0,
            'net_margin' => 0.0,
            'margin_percent' => 0.0,
        ];
    }

    private function addMetrics(array &$target, float $hours, float $gross, float $labor): void
    {
        $target['total_hours'] += $hours;
        $target['gross_revenue'] += $gross;
        $target['labor_cost'] += $labor;
    }

    private function finalizeMetrics(array &$target): void
    {
        $target['net_margin'] = $target['gross_revenue'] - $target['labor_cost'];
        $target['margin_percent'] = $target['gross_revenue'] > 0
            ? ($target['net_margin'] / $target['gross_revenue']) * 100
            : 0.0;

        $target['total_hours'] = round((float) $target['total_hours'], 2);
        $target['gross_revenue'] = round((float) $target['gross_revenue'], 2);
        $target['labor_cost'] = round((float) $target['labor_cost'], 2);
        $target['net_margin'] = round((float) $target['net_margin'], 2);
        $target['margin_percent'] = round((float) $target['margin_percent'], 2);
    }
}
