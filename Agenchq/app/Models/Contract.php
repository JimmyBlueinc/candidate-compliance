<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Contract extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope('organization', function ($builder) {
            $user = auth()->user();
            if ($user && ($user->role ?? null) === 'platform_admin') {
                return;
            }

            $tenantId = TenantContext::id() ?? TenantContext::defaultId();
            if ($tenantId) {
                $builder->where('contracts.organization_id', $tenantId);
            }
        });
    }

    protected $fillable = [
        'facility_id',
        'organization_id',
        'document_type',
        'file_path',
        'file_name',
        'version',
        'status',
        'effective_start_date',
        'effective_end_date',
        'created_by',
        'extracted_text',
    ];

    protected $casts = [
        'effective_start_date' => 'date',
        'effective_end_date' => 'date',
    ];

    // Document types
    const TYPE_MSA = 'msa';
    const TYPE_SOW = 'sow';
    const TYPE_AMENDMENT = 'amendment';
    const TYPE_RATE_CARD = 'rate_card';

    // Statuses
    const STATUS_UPLOADED = 'uploaded';
    const STATUS_PROCESSING = 'processing';
    const STATUS_PROCESSED = 'processed';
    const STATUS_REVIEWED = 'reviewed';
    const STATUS_APPROVED = 'approved';

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function terms(): HasOne
    {
        return $this->hasOne(ContractTerm::class);
    }

    public function rateLines(): HasMany
    {
        return $this->hasMany(ContractRateLine::class);
    }

    public function billingSettings(): HasMany
    {
        return $this->hasMany(BillingSettings::class);
    }

    /**
     * Check if contract is ready for review
     */
    public function isReadyForReview(): bool
    {
        return in_array($this->status, [self::STATUS_PROCESSED, self::STATUS_REVIEWED]);
    }

    /**
     * Check if contract is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Get the file URL from S3
     */
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }
        return \Storage::disk('private_assets')->url($this->file_path);
    }
}
