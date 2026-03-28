<?php

namespace App\Services;

use App\Events\CandidateRecruiterAssigned;
use App\Events\CandidateStageChanged;
use App\Models\Candidate;
use App\Models\CandidatePipeline;
use App\Models\CandidatePipelineStageEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CandidatePipelineService
{
    public function setStage(int $tenantId, int $candidateId, string $stage, ?User $actor = null): CandidatePipeline
    {
        return DB::transaction(function () use ($tenantId, $candidateId, $stage, $actor) {
            $pipeline = CandidatePipeline::query()->firstOrCreate([
                'tenant_id' => $tenantId,
                'candidate_id' => $candidateId,
            ], [
                'stage' => 'new',
            ]);

            $previous = (string) $pipeline->stage;
            if ($previous === $stage) {
                return $pipeline;
            }

            $pipeline->stage = $stage;
            $pipeline->save();

            CandidatePipelineStageEvent::query()->create([
                'tenant_id' => $tenantId,
                'candidate_id' => $candidateId,
                'pipeline_id' => (int) $pipeline->id,
                'from_stage' => $previous,
                'to_stage' => $stage,
                'changed_by_user_id' => $actor?->id,
            ]);

            CandidateStageChanged::dispatch($pipeline, $tenantId, $previous, $stage, $actor);

            return $pipeline;
        });
    }

    public function assignRecruiter(int $tenantId, int $candidateId, int $recruiterId, ?User $actor = null): CandidatePipeline
    {
        return DB::transaction(function () use ($tenantId, $candidateId, $recruiterId, $actor) {
            $pipeline = CandidatePipeline::query()->firstOrCreate([
                'tenant_id' => $tenantId,
                'candidate_id' => $candidateId,
            ], [
                'stage' => 'new',
            ]);

            $previous = $pipeline->assigned_recruiter_id ? (int) $pipeline->assigned_recruiter_id : null;
            if ($previous === $recruiterId) {
                return $pipeline;
            }

            $pipeline->assigned_recruiter_id = $recruiterId;
            $pipeline->save();

            CandidateRecruiterAssigned::dispatch($pipeline, $tenantId, $previous, $recruiterId, $actor);

            return $pipeline;
        });
    }

    public function addPipelineNote(int $tenantId, int $candidateId, string $note, ?User $actor = null): CandidatePipeline
    {
        return DB::transaction(function () use ($tenantId, $candidateId, $note, $actor) {
            $pipeline = CandidatePipeline::query()->firstOrCreate([
                'tenant_id' => $tenantId,
                'candidate_id' => $candidateId,
            ], [
                'stage' => 'new',
            ]);

            $existing = trim((string) ($pipeline->notes ?? ''));
            $note = trim($note);

            $pipeline->notes = $existing === '' ? $note : ($existing . "\n" . $note);
            $pipeline->save();

            return $pipeline;
        });
    }
}
