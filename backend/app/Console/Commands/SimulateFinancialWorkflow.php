<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Organization;
use App\Models\Candidate;
use App\Models\Timesheet;
use App\Models\TimesheetEntry;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Models\JobOrder;
use App\Models\Facility;
use App\Services\InvoiceGenerationService;
use App\Services\AccountsReceivableService;
use App\Services\FinancialSummaryService;
use App\Services\TimesheetRevenueService;
use App\Services\OperationalPlacementService;
use Carbon\Carbon;
use App\Models\Scopes\TenantScope;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class SimulateFinancialWorkflow extends Command
{
    protected $signature = 'simulate:financial-workflow';
    protected $description = 'Simulate full financial workflow';

    public function handle()
    {
        $org = Organization::first() ?: Organization::create(['name' => 'Test Org']);

        TenantContext::setId($org->id);
        
        $this->info('1. Creating Recruiters...');

        $recruiter1 = User::query()->firstOrCreate([
            'organization_id' => $org->id,
            'email' => 'recruiter1@example.com',
        ], [
            'name' => 'Recruiter One',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'access_status' => 'active',
            'must_change_password' => 0,
        ]);

        $recruiter2 = User::query()->firstOrCreate([
            'organization_id' => $org->id,
            'email' => 'recruiter2@example.com',
        ], [
            'name' => 'Recruiter Two',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'access_status' => 'active',
            'must_change_password' => 0,
        ]);

        $this->info('2. Creating Operational Placements (Assignments) (3)...');

        $definitions = [
            [
                'facility_name' => 'General Hospital',
                'recruiter_id' => $recruiter1->id,
                'bill_rate' => 100.00,
                'pay_rate' => 50.00,
                'candidate_email' => 'candidate1@example.com',
            ],
            [
                'facility_name' => 'General Hospital',
                'recruiter_id' => $recruiter2->id,
                'bill_rate' => 110.00,
                'pay_rate' => 55.00,
                'candidate_email' => 'candidate2@example.com',
            ],
            [
                'facility_name' => 'City Clinic',
                'recruiter_id' => $recruiter1->id,
                'bill_rate' => 95.00,
                'pay_rate' => 48.00,
                'candidate_email' => 'candidate3@example.com',
            ],
        ];

        $facilityIdsByName = [];
        foreach ($definitions as $def) {
            $name = (string) $def['facility_name'];
            if (isset($facilityIdsByName[$name])) {
                continue;
            }

            $facility = Facility::query()->firstOrCreate([
                'organization_id' => $org->id,
                'name' => $name,
            ], [
                'address' => null,
                'city' => null,
                'state' => null,
                'country' => null,
                'contact_email' => null,
                'contact_phone' => null,
            ]);

            $facilityIdsByName[$name] = $facility->id;
        }

        $jobOrders = [];
        foreach ($definitions as $i => $def) {
            $jobOrders[$i] = JobOrder::query()->firstOrCreate([
                'tenant_id' => $org->id,
                'facility_id' => $facilityIdsByName[(string) $def['facility_name']] ?? null,
                'title' => 'Simulated Job Order ' . ($i + 1),
                'facility_name' => $def['facility_name'],
            ], [
                'specialty' => 'Simulation',
                'bill_rate' => $def['bill_rate'],
                'pay_rate' => $def['pay_rate'],
                'status' => 'open',
            ]);
        }

        $assignments = [];
        foreach ($definitions as $i => $def) {
            $candidate = Candidate::withoutGlobalScope(TenantScope::class)
                ->where('email', $def['candidate_email'])
                ->first();

            if (!$candidate) {
                $candidate = Candidate::create([
                    'tenant_id' => $org->id,
                    'first_name' => 'Test',
                    'last_name' => 'Candidate ' . ($i + 1),
                    'name' => 'Test Candidate ' . ($i + 1),
                    'email' => $def['candidate_email'],
                ]);
            }

            // Ensure placement exists (unique: tenant_id, candidate_id, job_order_id)
            $placement = \App\Models\Placement::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                ->where('tenant_id', $org->id)
                ->where('candidate_id', $candidate->id)
                ->where('job_order_id', $jobOrders[$i]->id)
                ->first();

            if (!$placement) {
                try {
                    $placement = \App\Models\Placement::create([
                        'tenant_id' => $org->id,
                        'candidate_id' => $candidate->id,
                        'job_order_id' => $jobOrders[$i]->id,
                        'recruiter_id' => $def['recruiter_id'],
                        'stage' => 'placed',
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    $placement = \App\Models\Placement::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                        ->where('tenant_id', $org->id)
                        ->where('candidate_id', $candidate->id)
                        ->where('job_order_id', $jobOrders[$i]->id)
                        ->firstOrFail();
                }
            }

            $assignment = app(OperationalPlacementService::class)
                ->createFromPipelinePlacement($org->id, (int) $placement->id);

            $assignment->start_date = now()->startOfWeek()->format('Y-m-d');
            $assignment->status = 'active';
            $assignment->save();

            $assignments[] = $assignment;
        }

        $this->info('3. Generating multiple weeks of approved timesheets...');

        $weeks = [
            now()->startOfWeek()->subWeeks(2),
            now()->startOfWeek()->subWeeks(1),
            now()->startOfWeek(),
        ];

        $simStartDate = Carbon::parse($weeks[0])->format('Y-m-d');
        $simEndDate = Carbon::parse($weeks[count($weeks) - 1])->format('Y-m-d');

        $this->info('3a. Resetting existing financial records within simulation window (idempotency)...');

        $timesheetIdsInWindow = DB::table('timesheets')
            ->where('tenant_id', $org->id)
            ->whereRaw('date(week_start_date) >= ?', [$simStartDate])
            ->whereRaw('date(week_start_date) <= ?', [$simEndDate])
            ->pluck('id')
            ->all();

        if (count($timesheetIdsInWindow) > 0) {
            TimesheetEntry::query()->whereIn('timesheet_id', $timesheetIdsInWindow)->delete();
            DB::table('timesheets')->whereIn('id', $timesheetIdsInWindow)->delete();
        }

        $invoiceIdsInWindow = DB::table('invoices')
            ->where('tenant_id', $org->id)
            ->whereRaw('date(week_start_date) >= ?', [$simStartDate])
            ->whereRaw('date(week_start_date) <= ?', [$simEndDate])
            ->pluck('id')
            ->all();

        if (count($invoiceIdsInWindow) > 0) {
            Payment::query()->whereIn('invoice_id', $invoiceIdsInWindow)->delete();
            DB::table('invoices')->whereIn('id', $invoiceIdsInWindow)->delete();
        }

        $deterministicHoursByWeekIndex = [32.0, 24.0, 32.0];

        foreach ($assignments as $aIdx => $assignment) {
            foreach ($weeks as $wIdx => $weekStart) {
                $weekStartDate = Carbon::parse($weekStart)->format('Y-m-d');

                $timesheetId = DB::table('timesheets')
                    ->where('assignment_id', $assignment->id)
                    ->whereRaw('date(week_start_date) = ?', [$weekStartDate])
                    ->value('id');

                if (!$timesheetId) {
                    $timesheetId = DB::table('timesheets')->insertGetId([
                        'tenant_id' => $org->id,
                        'assignment_id' => $assignment->id,
                        'candidate_id' => $assignment->candidate_id,
                        'week_start_date' => $weekStartDate,
                        'status' => 'submitted',
                        'submitted_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('timesheets')->where('id', $timesheetId)->update([
                        'status' => 'submitted',
                        'submitted_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $timesheet = Timesheet::withoutGlobalScope(TenantScope::class)->find($timesheetId);
                if (!$timesheet) {
                    $this->error('Timesheet not found after upsert.');
                    return 1;
                }

                TimesheetEntry::where('timesheet_id', $timesheet->id)->delete();

                $targetHours = $deterministicHoursByWeekIndex[$wIdx] ?? 32.0;

                $entryDates = [
                    Carbon::parse($weekStart)->format('Y-m-d'),
                    Carbon::parse($weekStart)->copy()->addDays(1)->format('Y-m-d'),
                    Carbon::parse($weekStart)->copy()->addDays(2)->format('Y-m-d'),
                    Carbon::parse($weekStart)->copy()->addDays(3)->format('Y-m-d'),
                ];

                $hoursPerEntry = $targetHours / count($entryDates);
                foreach ($entryDates as $d) {
                    TimesheetEntry::create([
                        'timesheet_id' => $timesheet->id,
                        'work_date' => $d,
                        'hours_worked' => $hoursPerEntry,
                        'overtime_hours' => 0,
                    ]);
                }

                DB::table('timesheets')->where('id', $timesheet->id)->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->info('4. Regenerating invoices for all assignments/weeks...');

        foreach ($assignments as $assignment) {
            foreach ($weeks as $weekStart) {
                $weekStartDate = Carbon::parse($weekStart)->format('Y-m-d');
                Invoice::query()
                    ->where('assignment_id', $assignment->id)
                    ->whereDate('week_start_date', $weekStartDate)
                    ->delete();
            }
        }

        $genService = app(InvoiceGenerationService::class);
        $result = $genService->generate(
            $org->id,
            $simStartDate,
            $simEndDate
        );
        $this->info("Generation Result: " . json_encode($result));

        $this->info('5. Resetting payments and creating deterministic partial payments...');

        $allInvoices = Invoice::query()
            ->where('tenant_id', $org->id)
            ->whereIn('assignment_id', array_map(fn($a) => $a->id, $assignments))
            ->orderBy('assignment_id')
            ->orderBy('week_start_date')
            ->get();

        foreach ($allInvoices as $invoice) {
            Payment::where('invoice_id', $invoice->id)->delete();

            $paymentDate = now()->format('Y-m-d');
            // Pay 1000 on each invoice, but clamp so we never exceed invoice total.
            $target = min(1000.0, (float) $invoice->total_amount);
            $parts = [500.0, 300.0, 200.0];
            $refs = ['CHK101', 'ACH202', 'CRD303'];
            $methods = ['Check', 'ACH', 'Card'];

            $remaining = $target;
            for ($i = 0; $i < count($parts); $i++) {
                if ($remaining <= 0) {
                    break;
                }
                $amt = min($parts[$i], $remaining);
                $remaining -= $amt;
                Payment::create([
                    'tenant_id' => $org->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $amt,
                    'payment_date' => $paymentDate,
                    'payment_method' => $methods[$i],
                    'reference_number' => $refs[$i],
                ]);
            }
        }

        $this->info('6. Fetching Final Records & Analytics...');

        $timesheetsData = Timesheet::withoutGlobalScope(TenantScope::class)
            ->with('entries')
            ->where('tenant_id', $org->id)
            ->whereIn('assignment_id', array_map(fn($a) => $a->id, $assignments))
            ->whereIn(DB::raw('date(week_start_date)'), array_map(fn($w) => Carbon::parse($w)->format('Y-m-d'), $weeks))
            ->orderBy('assignment_id')
            ->orderBy('week_start_date')
            ->get();

        $invoicesData = Invoice::query()
            ->with('payments')
            ->where('tenant_id', $org->id)
            ->whereIn('assignment_id', array_map(fn($a) => $a->id, $assignments))
            ->orderBy('assignment_id')
            ->orderBy('week_start_date')
            ->get();

        $paymentsData = Payment::query()
            ->where('tenant_id', $org->id)
            ->whereIn('invoice_id', $invoicesData->pluck('id')->all())
            ->orderBy('invoice_id')
            ->orderBy('id')
            ->get();

        $arSummary = app(AccountsReceivableService::class)->getAgingSummary($org->id);
        $finSummary = app(FinancialSummaryService::class)->getSummary($org->id);

        $revenueAnalytics = app(TimesheetRevenueService::class)->calculate(
            $org->id,
            $simStartDate,
            $simEndDate
        );

        $stripNonDeterministic = function ($value) use (&$stripNonDeterministic) {
            if (is_array($value)) {
                unset(
                    $value['id'],
                    $value['timesheet_id'],
                    $value['invoice_id'],
                    $value['created_at'],
                    $value['updated_at'],
                    $value['submitted_at'],
                    $value['approved_at']
                );

                foreach ($value as $k => $v) {
                    $value[$k] = $stripNonDeterministic($v);
                }
                return $value;
            }

            if ($value instanceof \Illuminate\Support\Collection) {
                return $stripNonDeterministic($value->toArray());
            }

            return $value;
        };

        $timesheetsOut = $stripNonDeterministic($timesheetsData->toArray());
        $invoicesOut = $stripNonDeterministic($invoicesData->toArray());
        $paymentsOut = $stripNonDeterministic($paymentsData->toArray());

        $verificationPayload = [
            'timesheets' => $timesheetsOut,
            'invoices' => $invoicesOut,
            'payments' => $paymentsOut,
            'ar_summary' => $arSummary,
            'financial_summary' => $finSummary,
            'revenue_analytics' => $revenueAnalytics,
        ];
        unset($verificationPayload['financial_summary']['timestamp']);
        $checksum = sha1(json_encode($verificationPayload));

        $this->line('\n--- TIMESHEETS ---');
        $this->line(json_encode($timesheetsOut, JSON_PRETTY_PRINT));
        $this->line('\n--- INVOICES ---');
        $this->line(json_encode($invoicesOut, JSON_PRETTY_PRINT));
        $this->line('\n--- PAYMENTS ---');
        $this->line(json_encode($paymentsOut, JSON_PRETTY_PRINT));
        $this->line('\n--- AR SUMMARY ---');
        $this->line(json_encode($arSummary, JSON_PRETTY_PRINT));
        $this->line('\n--- FINANCIAL SUMMARY ---');
        unset($finSummary['timestamp']);
        $this->line(json_encode($finSummary, JSON_PRETTY_PRINT));
        $this->line('\n--- REVENUE ANALYTICS ---');
        $this->line(json_encode($revenueAnalytics, JSON_PRETTY_PRINT));

        $this->line('\n--- VERIFICATION ---');
        $this->line(json_encode([
            'weeks' => [
                'start' => $simStartDate,
                'end' => $simEndDate,
            ],
            'counts' => [
                'assignments' => count($assignments),
                'timesheets' => count($timesheetsOut),
                'invoices' => count($invoicesOut),
                'payments' => count($paymentsOut),
            ],
            'checksum_sha1' => $checksum,
        ], JSON_PRETTY_PRINT));
    }
}
