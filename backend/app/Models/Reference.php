<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'candidate_name',
        'reference_name',
        'reference_type',
        'contact_email',
        'contact_phone',
        'organization',
        'relationship',
        'verification_status',
        'verified_date',
        'verified_by',
        'notes',
        're_verification_date',
    ];

    protected function casts(): array
    {
        return [
            'verified_date' => 'date',
            're_verification_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}

