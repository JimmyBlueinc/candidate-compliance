<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Facility extends Model
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
                $builder->where('facilities.organization_id', $tenantId);
            }
        });
    }

    protected $fillable = [
        'organization_id',
        'name',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'timezone',
        'facility_type',
        'facility_type_other',
        'contact_person_name',
        'contact_email',
        'contact_phone',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function jobOrders(): HasMany
    {
        return $this->hasMany(JobOrder::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function billingSettings(): HasOne
    {
        return $this->hasOne(BillingSettings::class);
    }
}
