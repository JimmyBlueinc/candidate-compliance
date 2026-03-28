<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrganizationSetting;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrganizationSettingsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $settings = OrganizationSetting::firstOrCreate(
            ['organization_id' => $orgId],
            OrganizationSetting::defaults()
        );

        $settings->public_home_content = $this->mergePublicHomeContent($settings->public_home_content);

        return response()->api(['settings' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $validator = Validator::make($request->all(), [
            'language' => 'sometimes|string|in:en,es,fr',
            'timezone' => 'sometimes|string|max:64',
            'sidebar_collapsed' => 'sometimes|boolean',
            'notifications_enabled' => 'sometimes|boolean',
            'email_notifications_enabled' => 'sometimes|boolean',
            'expiry_reminders_enabled' => 'sometimes|boolean',
            'reminder_days_before' => 'sometimes|integer|min:1|max:365',
            'module_preferences' => 'sometimes|array',
            'public_home_content' => 'sometimes|array',
            'public_home_content.hero_heading' => 'sometimes|string|max:160',
            'public_home_content.hero_subheading' => 'sometimes|string|max:600',
            'public_home_content.hero_primary_cta_label' => 'sometimes|string|max:80',
            'public_home_content.hero_secondary_cta_label' => 'sometimes|string|max:80',
            'public_home_content.why_join_heading' => 'sometimes|string|max:160',
            'public_home_content.talent_pool_heading' => 'sometimes|string|max:160',
            'public_home_content.talent_pool_subheading' => 'sometimes|string|max:600',
            'public_home_content.final_cta_heading' => 'sometimes|string|max:160',
            'public_home_content.final_cta_subheading' => 'sometimes|string|max:600',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $settings = OrganizationSetting::firstOrCreate(
            ['organization_id' => $orgId],
            OrganizationSetting::defaults()
        );

        $validated = $validator->validated();
        if (array_key_exists('public_home_content', $validated)) {
            $validated['public_home_content'] = $this->mergePublicHomeContent($validated['public_home_content']);
        }

        $settings->update($validated);
        $fresh = $settings->fresh();
        $fresh->public_home_content = $this->mergePublicHomeContent($fresh->public_home_content);

        return response()->api(['settings' => $fresh], 200, [], 'Organization settings updated.');
    }

    private function mergePublicHomeContent(?array $incoming): array
    {
        $defaults = OrganizationSetting::defaults()['public_home_content'] ?? [];
        return array_merge($defaults, $incoming ?? []);
    }
}
