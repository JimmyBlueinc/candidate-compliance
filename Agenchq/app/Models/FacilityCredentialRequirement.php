<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class FacilityCredentialRequirement extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());

        static::saved(function (FacilityCredentialRequirement $req) {
            Cache::forget('facility_requirements_' . $req->tenant_id . '_' . $req->facility_id);
        });

        static::deleted(function (FacilityCredentialRequirement $req) {
            Cache::forget('facility_requirements_' . $req->tenant_id . '_' . $req->facility_id);
        });
    }

    protected $fillable = [
        'tenant_id',
        'facility_id',
        'credential_type_id',
        'required',
    ];

    protected $casts = [
        'required' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'tenant_id');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function credentialType(): BelongsTo
    {
        return $this->belongsTo(CredentialType::class, 'credential_type_id');
    }
}
