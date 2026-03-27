<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'payment_terms_days',
        'invoice_frequency',
        'currency',
        'bill_rate_type',
        'bill_rate_amount',
        'pay_rate_amount',
        'markup_percent',
        'overtime_multiplier',
        'holiday_multiplier',
        'timesheet_required',
        'expense_allowed',
        'minimum_bill_hours',
        'confidence_json',
        'source_spans_json',
        'review_status',
        'reviewed_by',
        'reviewed_at',
        // Approved values
        'approved_payment_terms_days',
        'approved_invoice_frequency',
        'approved_bill_rate_amount',
        'approved_pay_rate_amount',
        'approved_markup_percent',
        'approved_overtime_multiplier',
        'approved_holiday_multiplier',
        'approved_timesheet_required',
        'approved_expense_allowed',
        'approved_minimum_bill_hours',
    ];

    protected $casts = [
        'timesheet_required' => 'boolean',
        'expense_allowed' => 'boolean',
        'approved_timesheet_required' => 'boolean',
        'approved_expense_allowed' => 'boolean',
        'confidence_json' => 'array',
        'source_spans_json' => 'array',
        'reviewed_at' => 'datetime',
    ];

    // Review statuses
    const REVIEW_PENDING = 'pending';
    const REVIEW_APPROVED = 'approved';
    const REVIEW_REJECTED = 'rejected';
    const REVIEW_MODIFIED = 'modified';

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the effective value for a field (approved if exists, else extracted)
     */
    public function getEffectiveValue(string $field): mixed
    {
        $approvedField = 'approved_' . $field;
        if ($this->review_status === self::REVIEW_APPROVED && isset($this->$approvedField)) {
            return $this->$approvedField;
        }
        return $this->$field;
    }

    /**
     * Get confidence score for a specific field
     */
    public function getConfidence(string $field): ?float
    {
        return $this->confidence_json[$field] ?? null;
    }

    /**
     * Get source span for a specific field
     */
    public function getSourceSpan(string $field): ?array
    {
        return $this->source_spans_json[$field] ?? null;
    }

    /**
     * Check if terms have been reviewed
     */
    public function isReviewed(): bool
    {
        return $this->review_status !== self::REVIEW_PENDING;
    }

    /**
     * Check if terms are approved
     */
    public function isApproved(): bool
    {
        return $this->review_status === self::REVIEW_APPROVED;
    }

    /**
     * Get all extractable fields
     */
    public static function getExtractableFields(): array
    {
        return [
            'payment_terms_days',
            'invoice_frequency',
            'currency',
            'bill_rate_type',
            'bill_rate_amount',
            'pay_rate_amount',
            'markup_percent',
            'overtime_multiplier',
            'holiday_multiplier',
            'timesheet_required',
            'expense_allowed',
            'minimum_bill_hours',
        ];
    }
}
