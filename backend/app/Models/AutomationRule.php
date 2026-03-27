<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationRule extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'event',
        'enabled',
        'priority',
        'stop_processing',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'priority' => 'integer',
        'stop_processing' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'tenant_id');
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(AutomationCondition::class, 'rule_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(AutomationAction::class, 'rule_id')->orderBy('order');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AutomationLog::class, 'rule_id');
    }
}
