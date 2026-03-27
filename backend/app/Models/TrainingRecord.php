<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'candidate_name',
        'training_name',
        'training_type',
        'provider',
        'completion_date',
        'expiry_date',
        'credits',
        'document_path',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'completion_date' => 'date',
            'expiry_date' => 'date',
            'credits' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

