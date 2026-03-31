<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimesheetEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'timesheet_id',
        'work_date',
        'hours_worked',
        'overtime_hours',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'hours_worked' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
        ];
    }

    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(Timesheet::class);
    }
}
