<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractRateLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'role_title',
        'bill_rate',
        'pay_rate',
        'overtime_rate',
        'holiday_rate',
        'currency',
        'shift_type',
        'unit',
        'effective_start_date',
        'effective_end_date',
        'confidence_score',
        'source_span',
        'review_status',
        'approved_bill_rate',
        'approved_pay_rate',
        'approved_overtime_rate',
        'approved_holiday_rate',
    ];

    protected $casts = [
        'effective_start_date' => 'date',
        'effective_end_date' => 'date',
        'source_span' => 'array',
    ];

    // Review statuses
    const REVIEW_PENDING = 'pending';
    const REVIEW_APPROVED = 'approved';
    const REVIEW_REJECTED = 'rejected';
    const REVIEW_MODIFIED = 'modified';

    // Shift types
    const SHIFT_DAY = 'day';
    const SHIFT_NIGHT = 'night';
    const SHIFT_WEEKEND = 'weekend';
    const SHIFT_HOLIDAY = 'holiday';

    // Units
    const UNIT_HOUR = 'hour';
    const UNIT_DAY = 'day';
    const UNIT_SHIFT = 'shift';

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the effective bill rate (approved if exists, else extracted)
     */
    public function getEffectiveBillRate(): ?float
    {
        if ($this->review_status === self::REVIEW_APPROVED && $this->approved_bill_rate !== null) {
            return $this->approved_bill_rate;
        }
        return $this->bill_rate;
    }

    /**
     * Get the effective pay rate (approved if exists, else extracted)
     */
    public function getEffectivePayRate(): ?float
    {
        if ($this->review_status === self::REVIEW_APPROVED && $this->approved_pay_rate !== null) {
            return $this->approved_pay_rate;
        }
        return $this->pay_rate;
    }

    /**
     * Get the effective overtime rate
     */
    public function getEffectiveOvertimeRate(): ?float
    {
        if ($this->review_status === self::REVIEW_APPROVED && $this->approved_overtime_rate !== null) {
            return $this->approved_overtime_rate;
        }
        return $this->overtime_rate;
    }

    /**
     * Get the effective holiday rate
     */
    public function getEffectiveHolidayRate(): ?float
    {
        if ($this->review_status === self::REVIEW_APPROVED && $this->approved_holiday_rate !== null) {
            return $this->approved_holiday_rate;
        }
        return $this->holiday_rate;
    }

    /**
     * Check if rate line is approved
     */
    public function isApproved(): bool
    {
        return $this->review_status === self::REVIEW_APPROVED;
    }
}
