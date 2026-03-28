<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationConnection extends Model
{
    protected $fillable = [
        'organization_id',
        'key',
        'enabled',
        'status',
        'settings',
        'credentials',
        'connected_at',
        'last_synced_at',
        'last_error',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'settings' => 'array',
        'credentials' => 'array',
        'connected_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}

