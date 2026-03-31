<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Support\Org;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $filter = $request->input('filter', 'all');
        $action = $request->input('action');
        $entity = $request->input('entity');
        $search = $request->input('search', '');
        $orgId = Org::id($request);
        $effectiveOrgId = $user?->role === 'platform_admin' ? $orgId : $user?->organization_id;

        $query = ActivityLog::with('user:id,name,email');

        if ($effectiveOrgId) {
            $query->where('organization_id', $effectiveOrgId);
        }

        // Filter by action
        if ($action) {
            $query->where(function ($q) use ($action) {
                $q->where('old_action', $action)->orWhere('event', $action);
            });
        } elseif ($filter !== 'all') {
            $query->where(function ($q) use ($filter) {
                $q->where('old_action', $filter)->orWhere('event', $filter);
            });
        }

        if ($entity) {
            $query->where('entity_type', $entity);
        }

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('entity_name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('entity_type', 'like', '%' . $search . '%');
            });
        }

        // Role-based filtering
        if ($user->role === 'candidate') {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('user_id')) {
            if (!in_array($user?->role, ['admin', 'org_super_admin'], true)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $targetUser = User::query()->findOrFail((int) $request->input('user_id'));
            if ($effectiveOrgId && (int) $targetUser->organization_id !== (int) $effectiveOrgId) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $query->where('user_id', $targetUser->id);
        }

        $perPage = (int) $request->integer('per_page', 20);
        if ($perPage < 1) {
            $perPage = 20;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        // Incremental "live" mode for real-time activity streams.
        $sinceId = (int) $request->integer('since_id', 0);
        if ($sinceId > 0) {
            $liveRows = (clone $query)
                ->where('id', '>', $sinceId)
                ->orderBy('id', 'asc')
                ->limit($perPage)
                ->get();

            $liveActivities = $liveRows->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'user' => $activity->user ? [
                        'id' => $activity->user->id,
                        'name' => $activity->user->name,
                        'email' => $activity->user->email,
                    ] : null,
                    'action' => $activity->old_action ?: ($activity->event ?: 'updated'),
                    'entity' => $activity->entity_type,
                    'entity_name' => $activity->entity_name,
                    'description' => $activity->description,
                    'source' => $activity->source ?: 'system',
                    'created_at' => $activity->created_at?->toIso8601String(),
                ];
            });

            $latestId = (int) ($liveRows->max('id') ?: $sinceId);

            return response()->api($liveActivities, 200, [
                'since_id' => $sinceId,
                'latest_id' => $latestId,
                'count' => $liveActivities->count(),
                'mode' => 'incremental',
            ]);
        }

        $paginator = $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $activities = collect($paginator->items())->map(function ($activity) {
            return [
                'id' => $activity->id,
                'user' => $activity->user ? [
                    'id' => $activity->user->id,
                    'name' => $activity->user->name,
                    'email' => $activity->user->email,
                ] : null,
                'action' => $activity->old_action ?: ($activity->event ?: 'updated'),
                'entity' => $activity->entity_type,
                'entity_name' => $activity->entity_name,
                'description' => $activity->description,
                'source' => $activity->source ?: 'system',
                'created_at' => $activity->created_at?->toIso8601String(),
            ];
        });

        return response()->api($activities, 200, [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ]);
    }
}
