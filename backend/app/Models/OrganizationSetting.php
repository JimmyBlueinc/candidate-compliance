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
        'public_home_content',
    ];

    protected $casts = [
        'sidebar_collapsed' => 'boolean',
        'notifications_enabled' => 'boolean',
        'email_notifications_enabled' => 'boolean',
        'expiry_reminders_enabled' => 'boolean',
        'reminder_days_before' => 'integer',
        'module_preferences' => 'array',
        'public_home_content' => 'array',
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
            'module_preferences' => [
                'matching_weights' => [
                    'exact_specialty' => 40,
                    'specialty_overlap' => 24,
                    'keyword_alignment_per_hit' => 4,
                    'keyword_alignment_cap' => 22,
                    'name_relevance' => 20,
                    'email_relevance' => 10,
                    'specialty_relevance' => 12,
                    'experience_cap' => 14,
                    'recency_30d' => 8,
                    'recency_90d' => 4,
                ],
            ],
            'public_home_content' => [
                'hero_heading' => 'Build your next chapter with our team.',
                'hero_subheading' => 'Discover meaningful healthcare staffing opportunities and apply in minutes.',
                'hero_primary_cta_label' => 'Browse Open Jobs',
                'hero_secondary_cta_label' => 'Join Talent Pool',
                'why_join_heading' => 'A team built for growth, support, and meaningful impact.',
                'talent_pool_heading' => 'Get matched with the right opportunities faster.',
                'talent_pool_subheading' => 'Share your profile once and get notified when the right role opens.',
                'final_cta_heading' => 'Ready to apply or join our talent network?',
                'final_cta_subheading' => 'Start with open roles now or submit your profile for future opportunities.',
            ],
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
