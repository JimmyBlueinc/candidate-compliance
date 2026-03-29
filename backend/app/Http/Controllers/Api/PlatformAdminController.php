<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageCreated;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Organization;
use App\Models\Placement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlatformAdminController extends Controller
{
    public function platformHealth(Request $request): JsonResponse
    {
        $tenants = Organization::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $counts = Placement::query()
            ->withoutGlobalScopes()
            ->selectRaw('tenant_id, count(*) as active_count')
            ->where('stage', 'active')
            ->groupBy('tenant_id')
            ->pluck('active_count', 'tenant_id');

        $rows = $tenants->map(function ($t) use ($counts) {
            return [
                'tenant_id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
                'active_placements' => (int) ($counts[$t->id] ?? 0),
            ];
        });

        return response()->json([
            'data' => $rows,
        ]);
    }

    public function workforce(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['nullable', 'string'],
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'string', 'in:name,last_active,login_time,session_duration'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
        ]);

        $tokenUsageByUser = DB::table('personal_access_tokens')
            ->selectRaw('tokenable_id as user_id, MAX(created_at) as last_login_at, MAX(last_used_at) as token_last_used_at')
            ->where('tokenable_type', User::class)
            ->groupBy('tokenable_id');

        $activityByUser = DB::table('activity_logs')
            ->selectRaw('user_id, COUNT(*) as activity_count')
            ->groupBy('user_id');

        $query = User::query()
            ->withoutGlobalScopes()
            ->leftJoinSub($tokenUsageByUser, 'token_usage', 'token_usage.user_id', '=', 'users.id')
            ->leftJoinSub($activityByUser, 'activity_usage', 'activity_usage.user_id', '=', 'users.id')
            ->leftJoin('organizations', 'organizations.id', '=', 'users.organization_id')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.role',
                'users.organization_id',
                'users.last_activity_at',
                'token_usage.last_login_at',
                'token_usage.token_last_used_at',
                'organizations.name as organization_name',
                DB::raw('COALESCE(activity_usage.activity_count, 0) as activity_count'),
            ]);

        if (!empty($validated['role'])) {
            $query->where('users.role', $validated['role']);
        }

        if (!empty($validated['search'])) {
            $needle = trim((string) $validated['search']);
            $query->where(function ($q) use ($needle) {
                $q->where('users.name', 'like', "%{$needle}%")
                    ->orWhere('users.email', 'like', "%{$needle}%")
                    ->orWhere('organizations.name', 'like', "%{$needle}%");
            });
        }

        $rows = $query->limit(500)->get()->map(function ($row) {
            $loginAt = $row->last_login_at ? Carbon::parse($row->last_login_at) : null;
            $lastActive = $row->last_activity_at
                ? Carbon::parse($row->last_activity_at)
                : ($row->token_last_used_at ? Carbon::parse($row->token_last_used_at) : null);
            $sessionDurationMinutes = ($loginAt && $lastActive && $lastActive->greaterThan($loginAt))
                ? $loginAt->diffInMinutes($lastActive)
                : null;
            $activityCount = (int) $row->activity_count;
            $activityLevel = $activityCount >= 100 ? 'high' : ($activityCount >= 30 ? 'medium' : 'low');

            return [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'email' => (string) $row->email,
                'role' => (string) $row->role,
                'organization_id' => $row->organization_id ? (int) $row->organization_id : null,
                'organization_name' => $row->organization_name,
                'login_time' => $loginAt?->toIso8601String(),
                'last_active_time' => $lastActive?->toIso8601String(),
                'session_duration_minutes' => $sessionDurationMinutes,
                'activity_count' => $activityCount,
                'activity_level' => $activityLevel,
            ];
        });

        $sort = (string) ($validated['sort'] ?? 'last_active');
        $direction = (string) ($validated['direction'] ?? 'desc');
        $sorter = [
            'name' => fn ($row) => strtolower((string) ($row['name'] ?? '')),
            'last_active' => fn ($row) => strtotime((string) ($row['last_active_time'] ?? '1970-01-01')),
            'login_time' => fn ($row) => strtotime((string) ($row['login_time'] ?? '1970-01-01')),
            'session_duration' => fn ($row) => (int) ($row['session_duration_minutes'] ?? 0),
        ][$sort] ?? fn ($row) => strtotime((string) ($row['last_active_time'] ?? '1970-01-01'));

        $sorted = $rows->sortBy($sorter, SORT_REGULAR, $direction === 'desc')->values();

        return response()->json([
            'data' => $sorted,
        ]);
    }

    public function quickMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $sender = $request->user();
        $recipient = User::query()->withoutGlobalScopes()->findOrFail((int) $validated['recipient_id']);
        if ((int) $recipient->id === (int) $sender->id) {
            return response()->json(['message' => 'You cannot message yourself.'], 422);
        }

        $tenantId = $recipient->organization_id;
        if (!$tenantId) {
            return response()->json(['message' => 'Recipient has no organization context.'], 422);
        }

        $messageData = [
            'tenant_id' => (int) $tenantId,
            'user_id' => (int) $sender->id,
            'recipient_id' => (int) $recipient->id,
            'body' => (string) $validated['body'],
            'created_at' => now(),
        ];

        if (Schema::hasColumn('messages', 'read_at')) {
            $messageData['read_at'] = null;
        }

        $message = Message::query()->withoutGlobalScopes()->create($messageData);

        if (class_exists(MessageCreated::class)) {
            MessageCreated::dispatch($message, (int) $tenantId, $sender);
        }

        return response()->json([
            'data' => $message->load(['user:id,name,role', 'recipient:id,name,role']),
            'message' => 'Message sent successfully.',
        ]);
    }
}
