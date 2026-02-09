<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackgroundCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'user_id',
        'candidate_name',
        'check_type',
        'issue_date',
        'expiry_date',
        'verification_status',
        'verified_by',
        'verification_date',
        'document_path',
        'notes',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'verification_date' => 'date',
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

    protected $appends = ['document_url'];

    public function getDocumentUrlAttribute(): ?string
    {
        if (!$this->document_path) {
            return null;
        }
        
        return \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->url($this->document_path);
    }
}

