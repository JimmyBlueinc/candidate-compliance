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

        $settings->update($validator->validated());

        return response()->api(['settings' => $settings->fresh()], 200, [], 'Organization settings updated.');
    }
}
