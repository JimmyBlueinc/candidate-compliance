<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingSettings extends Model
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
                $builder->where('billing_settings.organization_id', $tenantId);
            }
        });
    }

    protected $fillable = [
        'facility_id',
        'organization_id',
        'payment_terms_days',
        'invoice_frequency',
        'currency',
        'default_bill_rate',
        'default_pay_rate',
        'default_markup_percent',
        'overtime_multiplier',
        'holiday_multiplier',
        'timesheet_required',
        'expense_allowed',
        'minimum_bill_hours',
        'source',
        'contract_id',
        'applied_at',
    ];

    protected $casts = [
        'timesheet_required' => 'boolean',
        'expense_allowed' => 'boolean',
        'applied_at' => 'datetime',
    ];

    // Source types
    const SOURCE_MANUAL = 'manual';
    const SOURCE_CONTRACT = 'contract';

    // Invoice frequencies
    const FREQUENCY_WEEKLY = 'weekly';
    const FREQUENCY_BIWEEKLY = 'biweekly';
    const FREQUENCY_MONTHLY = 'monthly';

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Check if settings are from a contract
     */
    public function isFromContract(): bool
    {
        return $this->source === self::SOURCE_CONTRACT && $this->contract_id !== null;
    }

    /**
     * Get the effective overtime rate for a given base rate
     */
    public function calculateOvertimeRate(float $baseRate): float
    {
        return $baseRate * ($this->overtime_multiplier ?? 1.5);
    }

    /**
     * Get the effective holiday rate for a given base rate
     */
    public function calculateHolidayRate(float $baseRate): float
    {
        return $baseRate * ($this->holiday_multiplier ?? 2.0);
    }

    /**
     * Calculate markup from bill/pay rates
     */
    public function calculateMarkup(): ?float
    {
        if ($this->default_bill_rate && $this->default_pay_rate && $this->default_pay_rate > 0) {
            return (($this->default_bill_rate - $this->default_pay_rate) / $this->default_pay_rate) * 100;
        }
        return $this->default_markup_percent;
    }

    /**
     * Calculate total bill amount for given hours
     * Handles regular, overtime, and holiday hours
     */
    public function calculateBillAmount(float $regularHours, float $overtimeHours = 0, float $holidayHours = 0): array
    {
        $billRate = $this->default_bill_rate ?? 0;
        
        $regularAmount = $regularHours * $billRate;
        $overtimeAmount = $overtimeHours * $this->calculateOvertimeRate($billRate);
        $holidayAmount = $holidayHours * $this->calculateHolidayRate($billRate);
        
        $totalHours = $regularHours + $overtimeHours + $holidayHours;
        $totalAmount = $regularAmount + $overtimeAmount + $holidayAmount;
        
        // Apply minimum bill hours if set
        $minimumHours = $this->minimum_bill_hours ?? 0;
        $shortfall = max(0, $minimumHours - $totalHours);
        $minimumAdjustment = $shortfall * $billRate;
        
        return [
            'regular_hours' => $regularHours,
            'overtime_hours' => $overtimeHours,
            'holiday_hours' => $holidayHours,
            'total_hours' => $totalHours,
            'regular_amount' => round($regularAmount, 2),
            'overtime_amount' => round($overtimeAmount, 2),
            'holiday_amount' => round($holidayAmount, 2),
            'minimum_hours' => $minimumHours,
            'minimum_shortfall_hours' => $shortfall,
            'minimum_adjustment' => round($minimumAdjustment, 2),
            'subtotal' => round($totalAmount, 2),
            'total_with_minimum' => round($totalAmount + $minimumAdjustment, 2),
            'bill_rate' => $billRate,
            'overtime_rate' => round($this->calculateOvertimeRate($billRate), 2),
            'holiday_rate' => round($this->calculateHolidayRate($billRate), 2),
        ];
    }

    /**
     * Calculate pay amount for given hours
     */
    public function calculatePayAmount(float $regularHours, float $overtimeHours = 0, float $holidayHours = 0): array
    {
        $payRate = $this->default_pay_rate ?? 0;
        
        $regularAmount = $regularHours * $payRate;
        $overtimeAmount = $overtimeHours * $this->calculateOvertimeRate($payRate);
        $holidayAmount = $holidayHours * $this->calculateHolidayRate($payRate);
        
        $totalHours = $regularHours + $overtimeHours + $holidayHours;
        $totalAmount = $regularAmount + $overtimeAmount + $holidayAmount;
        
        return [
            'regular_hours' => $regularHours,
            'overtime_hours' => $overtimeHours,
            'holiday_hours' => $holidayHours,
            'total_hours' => $totalHours,
            'regular_amount' => round($regularAmount, 2),
            'overtime_amount' => round($overtimeAmount, 2),
            'holiday_amount' => round($holidayAmount, 2),
            'total_amount' => round($totalAmount, 2),
            'pay_rate' => $payRate,
            'overtime_rate' => round($this->calculateOvertimeRate($payRate), 2),
            'holiday_rate' => round($this->calculateHolidayRate($payRate), 2),
        ];
    }

    /**
     * Calculate gross margin for given hours
     */
    public function calculateMargin(float $regularHours, float $overtimeHours = 0, float $holidayHours = 0): array
    {
        $bill = $this->calculateBillAmount($regularHours, $overtimeHours, $holidayHours);
        $pay = $this->calculatePayAmount($regularHours, $overtimeHours, $holidayHours);
        
        $marginAmount = $bill['total_with_minimum'] - $pay['total_amount'];
        $marginPercent = $bill['total_with_minimum'] > 0 
            ? ($marginAmount / $bill['total_with_minimum']) * 100 
            : 0;
        
        return [
            'bill_total' => $bill['total_with_minimum'],
            'pay_total' => $pay['total_amount'],
            'margin_amount' => round($marginAmount, 2),
            'margin_percent' => round($marginPercent, 2),
            'bill_breakdown' => $bill,
            'pay_breakdown' => $pay,
        ];
    }

    /**
     * Get invoice due date based on payment terms
     */
    public function calculateInvoiceDueDate(\DateTime $invoiceDate): \DateTime
    {
        $days = $this->payment_terms_days ?? 30;
        $dueDate = clone $invoiceDate;
        $dueDate->modify("+{$days} days");
        return $dueDate;
    }

    /**
     * Get next invoice date based on frequency
     */
    public function getNextInvoiceDate(\DateTime $fromDate = null): \DateTime
    {
        $from = $fromDate ?? new \DateTime();
        $next = clone $from;
        
        switch ($this->invoice_frequency) {
            case self::FREQUENCY_WEEKLY:
                $next->modify('+1 week');
                break;
            case self::FREQUENCY_BIWEEKLY:
                $next->modify('+2 weeks');
                break;
            case self::FREQUENCY_MONTHLY:
            default:
                $next->modify('+1 month');
                break;
        }
        
        return $next;
    }

    /**
     * Get or create billing settings for a facility
     */
    public static function getOrCreateForFacility(int $facilityId, int $organizationId): self
    {
        $settings = static::where('facility_id', $facilityId)->first();
        
        if (!$settings) {
            $settings = static::create([
                'facility_id' => $facilityId,
                'organization_id' => $organizationId,
                'source' => self::SOURCE_MANUAL,
            ]);
        }
        
        return $settings;
    }
}
