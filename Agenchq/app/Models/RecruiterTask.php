<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruiterTask extends Model
{
    protected $fillable = [
        'tenant_id',
        'candidate_id',
        'assigned_by_user_id',
        'assigned_to_user_id',
        'title',
        'description',
        'priority',
        'status',
        'recurrence',
        'recurrence_interval',
        'due_at',
        'remind_at',
        'completed_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'remind_at' => 'datetime',
        'completed_at' => 'datetime',
        'recurrence_interval' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }
}

