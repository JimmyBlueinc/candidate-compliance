<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class CandidatePipelineStageEvent extends Model
{
    protected $fillable = [
        'tenant_id',
        'candidate_id',
        'pipeline_id',
        'from_stage',
        'to_stage',
        'changed_by_user_id',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }
}

