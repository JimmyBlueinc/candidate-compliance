<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'candidate_name',
        'record_type',
        'date',
        'rating',
        'reviewer_id',
        'notes',
        'document_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
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

