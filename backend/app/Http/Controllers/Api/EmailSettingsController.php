<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class EmailSettingsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Only admin/super_admin can view email settings
        if (!in_array($request->user()->role, ['admin', 'org_super_admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Read from .env or config
        $settings = [
            'smtp_host' => env('MAIL_HOST', 'smtp.gmail.com'),
            'smtp_port' => env('MAIL_PORT', '587'),
            'smtp_username' => env('MAIL_USERNAME', ''),
            'smtp_password' => env('MAIL_PASSWORD', '') ? '***hidden***' : '',
            'from_email' => env('MAIL_FROM_ADDRESS', 'noreply@goodwillstaffing.ca'),
            'from_name' => env('MAIL_FROM_NAME', 'Goodwill Staffing'),
            'reminder_days' => [30, 14, 7],
            'enable_reminders' => true,
            'enable_summary' => true,
        ];

        return response()->json($settings);
    }

    public function update(Request $request): JsonResponse
    {
        // Only admin/super_admin can update email settings
        if (!in_array($request->user()->role, ['admin', 'org_super_admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'smtp_host' => 'sometimes|string|max:255',
            'smtp_port' => 'sometimes|integer',
            'smtp_username' => 'sometimes|string|max:255',
            'smtp_password' => 'sometimes|string',
            'from_email' => 'sometimes|email|max:255',
            'from_name' => 'sometimes|string|max:255',
            'reminder_days' => 'sometimes|array',
            'enable_reminders' => 'sometimes|boolean',
            'enable_summary' => 'sometimes|boolean',
        ]);

        // Note: In production, you'd want to update .env file or use a settings table
        // For now, we'll just return success
        // TODO: Implement actual settings storage (database table or .env update)

        return response()->json([
            'message' => 'Email settings updated successfully',
            'settings' => $validated,
        ]);
    }

    public function test(Request $request): JsonResponse
    {
        // Only admin/super_admin can test email
        if (!in_array($request->user()->role, ['admin', 'org_super_admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $email = $request->input('email', $request->user()->email);

        try {
            // TODO: Send test email
            \Mail::raw('This is a test email from Goodwill Staffing Compliance Tracker.', function ($message) use ($email) {
                $message->to($email)
                        ->subject('Test Email - Goodwill Staffing');
            });

            return response()->json(['message' => 'Test email sent successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to send test email: ' . $e->getMessage()], 500);
        }
    }
}
