<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDriveFileShare extends Model
{
    protected $fillable = [
        'tenant_id',
        'file_id',
        'owner_user_id',
        'recipient_user_id',
        'message_id',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(UserDriveFile::class, 'file_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }
}
