<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CandidateCredential extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    protected $fillable = [
        'tenant_id',
        'candidate_id',
        'credential_type_id',
        'document_path',
        'issued_at',
        'expires_at',
        'status',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'tenant_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function credentialType(): BelongsTo
    {
        return $this->belongsTo(CredentialType::class, 'credential_type_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(CredentialVerification::class, 'credential_id');
    }

    public function latestRejectedVerification(): HasOne
    {
        return $this->hasOne(CredentialVerification::class, 'credential_id')
            ->where('status', 'rejected')
            ->latest('created_at');
    }

    public function latestReviewFeedback(): HasOne
    {
        return $this->hasOne(CredentialVerification::class, 'credential_id')
            ->whereIn('status', ['rejected', 'needs_correction'])
            ->latest('created_at');
    }
}
