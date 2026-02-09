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
        $search = $request->input('search', '');
        $orgId = Org::id($request);
        $effectiveOrgId = $user?->role === 'platform_admin' ? $orgId : $user?->organization_id;

        $query = ActivityLog::with('user:id,name,email')
            ->orderBy('created_at', 'desc');

        if ($effectiveOrgId) {
            $query->where('organization_id', $effectiveOrgId);
        }

        // Filter by action
        if ($action) {
            $query->where('action', $action);
        } elseif ($filter !== 'all') {
            $query->where('action', $filter);
        }

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('entity_name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
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

        $paginator = $query->paginate($perPage);

        $activities = collect($paginator->items())->map(function ($activity) {
            return [
                'id' => $activity->id,
                'user' => $activity->user ? [
                    'id' => $activity->user->id,
                    'name' => $activity->user->name,
                    'email' => $activity->user->email,
                ] : null,
                'action' => $activity->action,
                'entity' => $activity->entity,
                'entity_name' => $activity->entity_name,
                'description' => $activity->description,
                'created_at' => $activity->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $activities,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
