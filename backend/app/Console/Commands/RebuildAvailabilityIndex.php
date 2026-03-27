<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\Scopes\TenantScope;
use App\Services\AvailabilityIndexService;
use Illuminate\Console\Command;

class RebuildAvailabilityIndex extends Command
{
    protected $signature = 'availability:rebuild-index {--tenantId=} {--candidateId=}';

    protected $description = 'Rebuild candidate availability index.';

    public function __construct(
        private AvailabilityIndexService $indexService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantId = $this->option('tenantId');
        $candidateId = $this->option('candidateId');

        $query = Candidate::query()->withoutGlobalScope(TenantScope::class);

        if ($tenantId) {
            $query->where('tenant_id', (int) $tenantId);
        }

        if ($candidateId) {
            $query->where('id', (int) $candidateId);
        }

        $count = 0;

        $query->select(['id'])->chunkById(200, function ($candidates) use (&$count) {
            foreach ($candidates as $candidate) {
                $this->indexService->rebuildCandidateIndex((int) $candidate->id);
                $count++;
            }
        });

        $this->info('Candidates indexed: ' . $count);

        return Command::SUCCESS;
    }
}
