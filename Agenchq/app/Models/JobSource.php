<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobSource extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    protected $fillable = [
        'tenant_id',
        'source_key',
        'name',
        'type',
        'url',
        'enabled',
        'archive_missing',
        'mapping',
        'last_synced_at',
        'last_error',
        'last_run_items',
        'last_run_upserts',
        'last_run_errors',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'archive_missing' => 'boolean',
            'mapping' => 'array',
            'last_synced_at' => 'datetime',
            'last_run_items' => 'integer',
            'last_run_upserts' => 'integer',
            'last_run_errors' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'tenant_id');
    }
}
