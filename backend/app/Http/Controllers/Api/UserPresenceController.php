<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Message;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

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

        // Get users who have been active in the last 5 minutes.
        // Guard against schema drift (older DBs may miss last_activity_at).
        $fiveMinutesAgo = now()->subMinutes(5);
        $hasLastActivity = Schema::hasColumn('users', 'last_activity_at');

        $query = User::query()
            ->where('organization_id', $orgId)
            ->where('id', '!=', $user->id) // Exclude current user
            ->whereIn('role', $staffRoles) // Only show staff members
            ->where(function ($q) use ($fiveMinutesAgo, $hasLastActivity) {
                if ($hasLastActivity) {
                    $q->where('last_activity_at', '>=', $fiveMinutesAgo)
                        ->orWhere('updated_at', '>=', $fiveMinutesAgo);
                } else {
                    $q->where('updated_at', '>=', $fiveMinutesAgo);
                }
            });

        if ($hasLastActivity) {
            $query->orderByDesc('last_activity_at');
        }

        $onlineUsers = $query
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get(['id', 'name', 'role', 'avatar_path', 'updated_at', 'last_activity_at'])
            ->map(function (User $u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'role' => $u->role,
                    'avatar' => $u->avatar_url,
                ];
            })
            ->values();

        return response()->json(['data' => $onlineUsers]);
    }

    /**
     * Update current user's presence/last activity timestamp.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $user = $request->user();

        // Update last activity timestamp only when column exists.
        if (Schema::hasColumn('users', 'last_activity_at')) {
            $user->last_activity_at = now();
        }
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
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }
}
