<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

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

        $rows = User::query()
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
            ->get(['id', 'name', 'email', 'role', 'avatar_path', 'updated_at', 'last_activity_at'])
            ->map(function (User $u) use ($hasLastActivity, $fiveMinutesAgo) {
                $activity = $hasLastActivity && $u->last_activity_at ? $u->last_activity_at : $u->updated_at;
                $isOnline = $activity ? $activity->greaterThanOrEqualTo($fiveMinutesAgo) : false;

                return [
                    'id' => (int) $u->id,
                    'name' => (string) ($u->name ?: 'Unknown User'),
                    'email' => (string) ($u->email ?? ''),
                    'role' => (string) ($u->role ?? 'user'),
                    'avatar' => $u->avatar_url,
                    'is_online' => (bool) $isOnline,
                ];
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
