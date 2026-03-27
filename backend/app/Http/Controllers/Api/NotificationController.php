<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get unread notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);

        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $notifications = Notification::query()
            ->where('tenant_id', $orgId)
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->api($notifications);
    }

    public function markAsRead(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);

        $notification = Notification::query()
            ->where('tenant_id', $orgId)
            ->where('user_id', $user->id)
            ->findOrFail($id);

        $notification->update(['read_at' => now()]);

        return response()->api(['success' => true]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);

        Notification::query()
            ->where('tenant_id', $orgId)
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->api(['success' => true]);
    }
}
