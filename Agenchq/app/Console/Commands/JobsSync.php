<?php

namespace App\Console\Commands;

use App\Models\JobOrder;
use App\Models\JobSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class JobsSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs:sync {--source=} {--tenant=} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync job orders from external feeds (RSS/JSON), upserting into job_orders by (tenant_id, source, external_id).';

    public function handle(): int
    {
        $source = (string) ($this->option('source') ?? '');
        $tenant = (string) ($this->option('tenant') ?? '');
        $dryRun = (bool) $this->option('dry-run');

        $sources = JobSource::query()
            ->withoutGlobalScopes()
            ->where('enabled', true)
            ->when($source !== '', fn ($q) => $q->where('source_key', $source))
            ->when($tenant !== '', fn ($q) => $q->where('tenant_id', (int) $tenant))
            ->orderBy('tenant_id')
            ->orderBy('source_key')
            ->get();

        if ($sources->isEmpty()) {
            $this->warn('No enabled job sources found for the given filters.');
            return Command::SUCCESS;
        }

        $this->info('Jobs sync starting');
        $this->line('Sources: ' . $sources->count());
        $this->line('Dry run: ' . ($dryRun ? 'yes' : 'no'));
        $this->line('');

        $totalUpserts = 0;
        $totalErrors = 0;

        foreach ($sources as $src) {
            $this->line("[{$src->tenant_id}] {$src->source_key} ({$src->type}) {$src->url}");

            try {
                $items = $this->fetchItems($src->type, $src->url);
                $normalized = $this->normalizeItems($items, is_array($src->mapping) ? $src->mapping : null);

                $upserts = 0;
                $itemsCount = count($items);
                $seenExternalIds = [];

                DB::beginTransaction();

                foreach ($normalized as $row) {
                    $externalId = (string) ($row['external_id'] ?? '');
                    if ($externalId === '') {
                        continue;
                    }

                    $seenExternalIds[$externalId] = true;

                    $payload = [
                        'tenant_id' => (int) $src->tenant_id,
                        'source' => (string) $src->source_key,
                        'external_id' => $externalId,
                        'title' => (string) ($row['title'] ?? 'Untitled'),
                        'facility_name' => (string) ($row['facility_name'] ?? 'Unknown'),
                        'specialty' => $row['specialty'] ?? null,
                        'pay_rate' => $row['pay_rate'] ?? null,
                        'bill_rate' => $row['bill_rate'] ?? null,
                        'stipend_weekly' => $row['stipend_weekly'] ?? null,
                        'work_mode' => $row['work_mode'] ?? 'on_site',
                        'start_date' => $row['start_date'] ?? null,
                        'published' => true,
                        'status' => 'open',
                    ];

                    if ($dryRun) {
                        $upserts++;
                        continue;
                    }

                    $existing = JobOrder::query()
                        ->withoutGlobalScopes()
                        ->where('tenant_id', $payload['tenant_id'])
                        ->where('source', $payload['source'])
                        ->where('external_id', $payload['external_id'])
                        ->first();

                    if ($existing) {
                        $existing->fill($payload);
                        $existing->save();
                    } else {
                        JobOrder::query()->withoutGlobalScopes()->create($payload);
                    }

                    $upserts++;
                }

                if (!$dryRun && (bool) $src->archive_missing) {
                    $this->archiveMissingJobs((int) $src->tenant_id, (string) $src->source_key, array_keys($seenExternalIds));
                }

                if (!$dryRun) {
                    JobSource::query()->withoutGlobalScopes()->whereKey($src->id)->update([
                        'last_synced_at' => now(),
                        'last_error' => null,
                        'last_run_items' => $itemsCount,
                        'last_run_upserts' => $upserts,
                        'last_run_errors' => 0,
                        'updated_at' => now(),
                    ]);
                }

                DB::commit();

                $this->line("  Upserts: {$upserts}");
                $totalUpserts += $upserts;
            } catch (\Throwable $e) {
                DB::rollBack();
                $totalErrors++;
                $this->error('  Error: ' . $e->getMessage());

                if (!$dryRun) {
                    JobSource::query()->withoutGlobalScopes()->whereKey($src->id)->update([
                        'last_error' => substr($e->getMessage(), 0, 2000),
                        'last_run_items' => null,
                        'last_run_upserts' => 0,
                        'last_run_errors' => 1,
                        'updated_at' => now(),
                    ]);
                }
            }

            $this->line('');
        }

        $this->info("Done. Upserts={$totalUpserts} Errors={$totalErrors}");
        return Command::SUCCESS;
    }

    private function archiveMissingJobs(int $tenantId, string $sourceKey, array $externalIds): void
    {
        $base = JobOrder::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('source', $sourceKey);

        $externalIds = array_values(array_unique(array_filter(array_map('strval', $externalIds), fn ($v) => $v !== '')));

        if (count($externalIds) === 0) {
            $base->where('status', 'open')->update(['status' => 'closed']);
            return;
        }

        if (count($externalIds) <= 800) {
            $base
                ->whereNotIn('external_id', $externalIds)
                ->where('status', 'open')
                ->update(['status' => 'closed']);
            return;
        }

        $base->where('status', 'open')->update(['status' => 'closed']);

        foreach (array_chunk($externalIds, 800) as $chunk) {
            JobOrder::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('source', $sourceKey)
                ->whereIn('external_id', $chunk)
                ->update(['status' => 'open']);
        }
    }

    private function fetchItems(string $type, string $url): array
    {
        $client = Http::timeout(20);
        if (app()->environment('local')) {
            $client = $client->withoutVerifying();
        }

        if ($type === 'json') {
            $res = $client->acceptJson()->get($url);
            if (!$res->ok()) {
                throw new \RuntimeException('Failed to fetch JSON feed: HTTP ' . $res->status());
            }
            $json = $res->json();
            if (is_array($json) && array_key_exists('data', $json) && is_array($json['data'])) {
                return $json['data'];
            }
            return is_array($json) ? $json : [];
        }

        $res = $client->get($url);
        if (!$res->ok()) {
            throw new \RuntimeException('Failed to fetch RSS feed: HTTP ' . $res->status());
        }

        $xml = @simplexml_load_string($res->body());
        if (!$xml) {
            throw new \RuntimeException('Invalid RSS XML.');
        }

        $items = [];
        if (isset($xml->channel) && isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) {
                $items[] = [
                    'title' => (string) ($item->title ?? ''),
                    'link' => (string) ($item->link ?? ''),
                    'guid' => (string) ($item->guid ?? ''),
                    'description' => (string) ($item->description ?? ''),
                ];
            }
        }
        return $items;
    }

    private function normalizeItems(array $items, ?array $mapping = null): array
    {
        $rows = [];
        foreach ($items as $it) {
            if (!is_array($it)) {
                continue;
            }

            if ($mapping && count($mapping) > 0) {
                foreach ($mapping as $to => $from) {
                    if (!is_string($to) || $to === '') {
                        continue;
                    }
                    if (!is_string($from) || $from === '') {
                        continue;
                    }
                    if (!array_key_exists($to, $it) && array_key_exists($from, $it)) {
                        $it[$to] = $it[$from];
                    }
                }
            }

            $externalId = (string) ($it['external_id'] ?? ($it['id'] ?? ($it['guid'] ?? ($it['link'] ?? ''))));
            $title = (string) ($it['title'] ?? '');
            $facility = (string) ($it['facility_name'] ?? ($it['facility'] ?? ''));
            $specialty = $it['specialty'] ?? null;

            if ($title === '' && isset($it['name'])) {
                $title = (string) $it['name'];
            }

            if ($facility === '') {
                $facility = 'Unknown';
            }

            $rows[] = [
                'external_id' => $externalId,
                'title' => $title !== '' ? $title : 'Untitled',
                'facility_name' => $facility,
                'specialty' => $specialty,
                'pay_rate' => $it['pay_rate'] ?? null,
                'bill_rate' => $it['bill_rate'] ?? null,
                'stipend_weekly' => $it['stipend_weekly'] ?? null,
                'work_mode' => $it['work_mode'] ?? null,
                'start_date' => $it['start_date'] ?? null,
            ];
        }
        return $rows;
    }
}
