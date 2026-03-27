<?php

namespace App\Console\Commands;

use App\Models\Facility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FacilitiesBackfill extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'facilities:backfill';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill facilities from job_orders.facility_name and populate facility_id on job_orders, assignments, and invoices.';

    public function handle(): int
    {
        $facilityRows = DB::table('job_orders')
            ->selectRaw('DISTINCT facility_name, tenant_id as organization_id')
            ->whereNotNull('facility_name')
            ->whereRaw("TRIM(facility_name) <> ''")
            ->orderBy('organization_id')
            ->orderBy('facility_name')
            ->get();

        if ($facilityRows->isEmpty()) {
            $this->info('No facility_name values found in job_orders. Nothing to backfill.');
            $this->line('facilities created: 0');
            $this->line('job_orders updated: 0');
            $this->line('assignments updated: 0');
            $this->line('invoices updated: 0');
            return Command::SUCCESS;
        }

        $facilitiesCreated = 0;
        $jobOrdersUpdated = 0;
        $assignmentsUpdated = 0;
        $invoicesUpdated = 0;

        DB::beginTransaction();
        try {
            foreach ($facilityRows as $row) {
                $organizationId = (int) $row->organization_id;
                $name = trim((string) $row->facility_name);

                if ($organizationId <= 0 || $name === '') {
                    continue;
                }

                $facility = Facility::query()
                    ->withoutGlobalScopes()
                    ->firstOrCreate([
                        'organization_id' => $organizationId,
                        'name' => $name,
                    ]);

                if ($facility->wasRecentlyCreated) {
                    $facilitiesCreated++;
                }

                $jobOrdersUpdated += DB::table('job_orders')
                    ->where('tenant_id', $organizationId)
                    ->where('facility_name', $name)
                    ->whereNull('facility_id')
                    ->update(['facility_id' => $facility->id]);

                $assignmentsUpdated += DB::table('assignments')
                    ->where('tenant_id', $organizationId)
                    ->where('facility_name', $name)
                    ->whereNull('facility_id')
                    ->update(['facility_id' => $facility->id]);

                $invoicesUpdated += DB::table('invoices')
                    ->where('tenant_id', $organizationId)
                    ->where('facility_name', $name)
                    ->whereNull('facility_id')
                    ->update(['facility_id' => $facility->id]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Backfill failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info('Facilities backfill complete.');
        $this->line('facilities created: ' . $facilitiesCreated);
        $this->line('job_orders updated: ' . $jobOrdersUpdated);
        $this->line('assignments updated: ' . $assignmentsUpdated);
        $this->line('invoices updated: ' . $invoicesUpdated);

        return Command::SUCCESS;
    }
}
