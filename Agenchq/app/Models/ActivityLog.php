<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'organization_id',
        'user_id',
        'old_action',
        'entity_type',
        'entity_name',
        'entity_id',
        'event',
        'source',
        'data',
        'description',
        'metadata',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'tenant_id');
    }

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Backward-compatible alias:
     * many callers still write/read "metadata", but the table column is "data".
     */
    public function setMetadataAttribute($value): void
    {
        $this->attributes['data'] = is_array($value) || is_object($value)
            ? json_encode($value)
            : $value;
    }

    public function getMetadataAttribute()
    {
        return $this->getAttribute('data');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
