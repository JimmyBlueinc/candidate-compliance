<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Get user settings.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $settings = UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            UserSetting::getDefaults()
        );

        return response()->json([
            'settings' => $settings,
        ]);
    }

    /**
     * Update user settings.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'language' => 'sometimes|string|in:en,es,fr',
            'timezone' => 'sometimes|string|max:50',
            'theme' => 'sometimes|string|in:light,dark,auto',
            'notifications_enabled' => 'sometimes|boolean',
            'email_notifications_enabled' => 'sometimes|boolean',
            'expiry_reminders_enabled' => 'sometimes|boolean',
            'reminder_days_before' => 'sometimes|integer|min:1|max:365',
            'notification_preferences' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $settings = UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            UserSetting::getDefaults()
        );

        $settings->update($validator->validated());

        return response()->json([
            'message' => 'Settings updated successfully',
            'settings' => $settings->fresh(),
        ]);
    }

    /**
     * Reset settings to defaults.
     */
    public function reset(Request $request): JsonResponse
    {
        $user = $request->user();

        $settings = UserSetting::firstOrCreate(
            ['user_id' => $user->id],
            UserSetting::getDefaults()
        );

        $settings->update(UserSetting::getDefaults());

        return response()->json([
            'message' => 'Settings reset to defaults',
            'settings' => $settings->fresh(),
        ]);
    }
}
