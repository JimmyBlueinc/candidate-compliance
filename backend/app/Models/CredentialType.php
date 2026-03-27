<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CredentialType extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    protected $fillable = [
        'tenant_id',
        'name',
        'category',
        'requires_expiration',
        'requires_document',
    ];

    protected $casts = [
        'requires_expiration' => 'boolean',
        'requires_document' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'tenant_id');
    }

    public function candidateCredentials(): HasMany
    {
        return $this->hasMany(CandidateCredential::class, 'credential_type_id');
    }

    public function facilityRequirements(): HasMany
    {
        return $this->hasMany(FacilityCredentialRequirement::class, 'credential_type_id');
    }
}
