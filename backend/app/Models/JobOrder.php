<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobOrder extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    protected $fillable = [
        'tenant_id',
        'facility_id',
        'title',
        'role',
        'required_staff',
        'facility_name',
        'specialty',
        'bill_rate',
        'pay_rate',
        'start_date',
        'end_date',
        'description',
        'created_by_user_id',
        'work_mode',
        'stipend_weekly',
        'published',
        'status',
        'external_id',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'bill_rate' => 'decimal:2',
            'pay_rate' => 'decimal:2',
            'stipend_weekly' => 'decimal:2',
            'published' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
            'required_staff' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'tenant_id');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
