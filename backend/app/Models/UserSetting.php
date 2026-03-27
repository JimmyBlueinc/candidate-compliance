<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    protected $fillable = [
        'user_id',
        'language',
        'timezone',
        'theme',
        'sidebar_collapsed',
        'notifications_enabled',
        'email_notifications_enabled',
        'expiry_reminders_enabled',
        'reminder_days_before',
        'notification_preferences',
    ];

    protected $casts = [
        'notifications_enabled' => 'boolean',
        'email_notifications_enabled' => 'boolean',
        'expiry_reminders_enabled' => 'boolean',
        'sidebar_collapsed' => 'boolean',
        'reminder_days_before' => 'integer',
        'notification_preferences' => 'array',
    ];

    /**
     * Get the user that owns the settings.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get default settings.
     */
    public static function getDefaults(): array
    {
        return [
            'language' => 'en',
            'timezone' => 'UTC',
            'theme' => 'light',
            'sidebar_collapsed' => false,
            'notifications_enabled' => true,
            'email_notifications_enabled' => true,
            'expiry_reminders_enabled' => true,
            'reminder_days_before' => 30,
            'notification_preferences' => [],
        ];
    }
}
