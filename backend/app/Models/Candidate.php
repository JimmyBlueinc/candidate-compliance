<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'first_name',
        'last_name',
        'name',
        'email',
        'phone',
        'address_line1',
        'address_line2',
        'specialty',
        'license_type',
        'years_experience',
        'city',
        'state',
        'postal_code',
        'country',
        'work_authorization',
        'background_check',
        'drug_screen',
        'vaccination',
        'onboarding_completed_at',
        'onboarding_stage',
        'role',
        'availability',
        'source',
        'notes',
        'tags',
        'last_applied_at',
        'resume_path',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'last_applied_at' => 'datetime',
            'work_authorization' => 'boolean',
            'background_check' => 'boolean',
            'drug_screen' => 'boolean',
            'vaccination' => 'boolean',
            'onboarding_completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'tenant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function availabilityWindows(): HasMany
    {
        return $this->hasMany(CandidateAvailability::class);
    }
}
