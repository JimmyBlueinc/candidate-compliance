<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationAction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'rule_id',
        'action',
        'config',
        'order',
    ];

    protected $casts = [
        'config' => 'array',
        'order' => 'integer',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'rule_id');
    }
}
