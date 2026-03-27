<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Timesheet extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    protected $fillable = [
        'tenant_id',
        'assignment_id',
        'candidate_id',
        'week_start_date',
        'status',
        'submitted_at',
        'facility_approved_at',
        'facility_approved_by_user_id',
        'agency_approved_at',
        'agency_approved_by_user_id',
        'approved_at',
        'rejected_at',
        'rejected_by_user_id',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'week_start_date' => 'date',
            'submitted_at' => 'datetime',
            'facility_approved_at' => 'datetime',
            'agency_approved_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'tenant_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(TimesheetEntry::class);
    }
}
