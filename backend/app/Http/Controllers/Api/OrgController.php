<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class OrgController extends Controller
{
    public function chatUsers(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $user = $request->user();
        $role = (string) ($user->role ?? '');
        $q = trim((string) $request->query('q', ''));
        $staffRoles = ['org_super_admin', 'admin', 'recruiter', 'compliance', 'scheduler', 'finance', 'logistics', 'platform_admin'];
        $isCandidate = $role === 'candidate';
        $isStaff = in_array($role, $staffRoles, true);

        if (!$isCandidate && !$isStaff) {
            return response()->json(['data' => []]);
        }

        $allowedRoles = $isCandidate
            ? $staffRoles
            : array_values(array_unique(array_merge($staffRoles, ['candidate'])));

        $fiveMinutesAgo = now()->subMinutes(5);
        $hasLastActivity = Schema::hasColumn('users', 'last_activity_at');
        $unreadCounts = Message::query()
            ->where('tenant_id', $orgId)
            ->where('recipient_id', $user->id)
            ->whereNull('read_at')
            ->groupBy('user_id')
            ->selectRaw('user_id, COUNT(*) as unread_count')
            ->pluck('unread_count', 'user_id');

        $users = User::query()
            ->where('organization_id', $orgId)
            ->where('id', '!=', $user->id)
            ->whereIn('role', $allowedRoles)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', '%' . $q . '%')
                        ->orWhere('email', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('name')
            ->limit(120)
            ->get(['id', 'name', 'email', 'role', 'avatar_path', 'updated_at', 'last_activity_at']);

        $participantIds = $users->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $lastByPeer = collect();
        if (!empty($participantIds)) {
            $threadMessages = Message::query()
                ->where('tenant_id', $orgId)
                ->where(function ($q) use ($user, $participantIds) {
                    $q->where(function ($sq) use ($user, $participantIds) {
                        $sq->where('user_id', $user->id)
                            ->whereIn('recipient_id', $participantIds);
                    })->orWhere(function ($sq) use ($user, $participantIds) {
                        $sq->whereIn('user_id', $participantIds)
                            ->where('recipient_id', $user->id);
                    });
                })
                ->orderByDesc('id')
                ->limit(1500)
                ->get(['id', 'user_id', 'recipient_id', 'body', 'created_at']);

            foreach ($threadMessages as $msg) {
                $peerId = (int) ((int) $msg->user_id === (int) $user->id ? $msg->recipient_id : $msg->user_id);
                if ($peerId <= 0 || $lastByPeer->has($peerId)) {
                    continue;
                }

                $lastByPeer->put($peerId, [
                    'preview' => Str::limit(trim(preg_replace('/\s+/', ' ', (string) $msg->body)), 80, '...'),
                    'at' => $msg->created_at?->toIso8601String(),
                ]);
            }
        }

        $rows = $users
            ->map(function (User $u) use ($hasLastActivity, $fiveMinutesAgo, $unreadCounts, $lastByPeer) {
                $activity = $hasLastActivity && $u->last_activity_at ? $u->last_activity_at : $u->updated_at;
                $isOnline = $activity ? $activity->greaterThanOrEqualTo($fiveMinutesAgo) : false;

                return [
                    'id' => (int) $u->id,
                    'name' => (string) ($u->name ?: 'Unknown User'),
                    'email' => (string) ($u->email ?? ''),
                    'role' => (string) ($u->role ?? 'user'),
                    'avatar' => $u->avatar_url,
                    'is_online' => (bool) $isOnline,
                    'unread_count' => (int) ($unreadCounts[(int) $u->id] ?? 0),
                    'last_message_preview' => (string) ($lastByPeer[(int) $u->id]['preview'] ?? ''),
                    'last_message_at' => (string) ($lastByPeer[(int) $u->id]['at'] ?? ''),
                ];
            })
            ->sort(function (array $a, array $b) {
                if ($a['is_online'] !== $b['is_online']) {
                    return $a['is_online'] ? -1 : 1;
                }

                return strcasecmp((string) $a['name'], (string) $b['name']);
            })
            ->values();

        return response()->api($rows);
    }

    public function staffUsersForCandidateChat(Request $request): JsonResponse
    {
        $orgId = Org::id($request);

        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $staffRoles = ['org_super_admin', 'platform_admin', 'admin', 'recruiter', 'compliance', 'scheduler', 'finance', 'logistics'];
        $q = trim((string) $request->query('q', ''));

        $query = User::query()
            ->where('organization_id', $orgId)
            ->whereIn('role', $staffRoles);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', '%' . $q . '%')
                    ->orWhere('email', 'like', '%' . $q . '%');
            });
        }

        $rows = $query
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'email', 'role']);

        return response()->api($rows);
    }

    public function candidateUsers(Request $request): JsonResponse
    {
        $orgId = Org::id($request);

        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $q = trim((string) $request->query('q', ''));

        $query = User::query()
            ->where('organization_id', $orgId)
            ->where('role', 'candidate');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', '%' . $q . '%')
                    ->orWhere('email', 'like', '%' . $q . '%');
            });
        }

        $rows = $query
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'email']);

        return response()->api($rows);
    }

    public function recruiters(Request $request): JsonResponse
    {
        $orgId = Org::id($request);

        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $recruiters = User::where('organization_id', $orgId)
            ->whereIn('role', ['recruiter', 'org_super_admin', 'admin'])
            ->get(['id', 'name', 'role']);

        return response()->api($recruiters);
    }
}
