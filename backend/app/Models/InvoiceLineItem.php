<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLineItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'timesheet_id',
        'placement_id',
        'hours',
        'bill_rate',
        'amount',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'hours' => 'decimal:2',
            'bill_rate' => 'decimal:2',
            'amount' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(Timesheet::class);
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
    }
}
