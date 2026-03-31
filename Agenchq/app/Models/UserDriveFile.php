<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserDriveFile extends Model
{
    protected $fillable = [
        'tenant_id',
        'owner_user_id',
        'name',
        'path',
        'storage_disk',
        'mime_type',
        'size_bytes',
        'extension',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(UserDriveFileShare::class, 'file_id');
    }
}
