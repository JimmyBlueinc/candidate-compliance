<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrgController extends Controller
{
    public function staffUsersForCandidateChat(Request $request): JsonResponse
    {
        $orgId = Org::id($request);

        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $staffRoles = ['admin', 'recruiter', 'compliance', 'scheduler', 'finance', 'logistics'];
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
