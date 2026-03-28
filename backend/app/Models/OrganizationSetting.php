<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationSetting extends Model
{
    protected $fillable = [
        'organization_id',
        'language',
        'timezone',
        'sidebar_collapsed',
        'notifications_enabled',
        'email_notifications_enabled',
        'expiry_reminders_enabled',
        'reminder_days_before',
        'module_preferences',
    ];

    protected $casts = [
        'sidebar_collapsed' => 'boolean',
        'notifications_enabled' => 'boolean',
        'email_notifications_enabled' => 'boolean',
        'expiry_reminders_enabled' => 'boolean',
        'reminder_days_before' => 'integer',
        'module_preferences' => 'array',
    ];

    public static function defaults(): array
    {
        return [
            'language' => 'en',
            'timezone' => 'UTC',
            'sidebar_collapsed' => false,
            'notifications_enabled' => true,
            'email_notifications_enabled' => true,
            'expiry_reminders_enabled' => true,
            'reminder_days_before' => 30,
            'module_preferences' => [],
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
