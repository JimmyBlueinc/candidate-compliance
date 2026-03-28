<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Message;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPresenceController extends Controller
{
    /**
     * Get online users in the organization (active in last 5 minutes).
     */
    public function online(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $user = $request->user();
        
        // Only non-candidates can see online users
        $staffRoles = ['org_super_admin', 'admin', 'recruiter', 'compliance', 'scheduler', 'finance', 'logistics'];
        if (!in_array((string) ($user->role ?? ''), $staffRoles, true)) {
            return response()->json(['data' => []]);
        }

        // Get users who have been active in the last 5 minutes
        // Using last_activity_at column if it exists, otherwise fallback to updated_at
        $fiveMinutesAgo = now()->subMinutes(5);

        $onlineUsers = User::query()
            ->where('organization_id', $orgId)
            ->where('id', '!=', $user->id) // Exclude current user
            ->whereIn('role', $staffRoles) // Only show staff members
            ->where(function ($q) use ($fiveMinutesAgo) {
                $q->where('last_activity_at', '>=', $fiveMinutesAgo)
                  ->orWhere('updated_at', '>=', $fiveMinutesAgo);
            })
            ->select(['id', 'name', 'role', 'avatar'])
            ->orderByDesc('last_activity_at')
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        return response()->json(['data' => $onlineUsers]);
    }

    /**
     * Update current user's presence/last activity timestamp.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Update last activity timestamp
        $user->last_activity_at = now();
        $user->saveQuietly(); // Save without triggering events

        return response()->json(['status' => 'ok']);
    }

    /**
     * Get unread message count for current user.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['count' => 0]);
        }

        $user = $request->user();

        // Use message-level read_at tracking for unread count.
        $count = Message::query()
            ->where('tenant_id', $orgId)
            ->where('recipient_id', $user->id)
            ->where('created_at', '>=', now()->subDay())
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }
}
