<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    /**
     * Get all users (platform_admin only).
     */
    public function getUsers(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        $orgId = Org::id($request);

        $users = User::withCount('credentials')
            ->when($currentUser->role === 'platform_admin' && $orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->when($currentUser->role !== 'platform_admin', fn ($q) => $q->where('organization_id', $currentUser->organization_id))
            ->when($currentUser->role === 'org_super_admin', fn ($q) => $q->whereIn('role', ['admin', 'org_super_admin']))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'access_status' => $user->access_status ?? 'active',
                    'avatar_url' => $user->avatar_url,
                    'credentials_count' => $user->credentials_count,
                    'created_at' => $user->created_at?->toISOString(),
                    'updated_at' => $user->updated_at?->toISOString(),
                ];
            });

        return response()->json([
            'users' => $users,
        ]);
    }

    /**
     * Create a new user.
     * - platform_admin: can create org_super_admin/admin/candidate (global)
     * - org_super_admin: can create admin/candidate within their organization
     */
    public function createUser(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        $orgId = Org::id($request);

        if ($currentUser->role === 'platform_admin' && !$orgId) {
            throw ValidationException::withMessages([
                'organization' => ['Organization context is required to create a user.'],
            ]);
        }

        $allowedRoles = $currentUser->role === 'platform_admin'
            ? ['org_super_admin', 'admin', 'candidate']
            : ['admin'];

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'role' => ['required', 'string', 'in:' . implode(',', $allowedRoles)],
        ]);

        if ($currentUser->role !== 'platform_admin' && $currentUser->role !== 'org_super_admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $tempPassword = Str::password(12);

        $user = User::create([
            'organization_id' => $currentUser->role === 'platform_admin' ? $orgId : $currentUser->organization_id,
            'name' => htmlspecialchars(strip_tags($request->name), ENT_QUOTES, 'UTF-8'),
            'email' => filter_var($request->email, FILTER_SANITIZE_EMAIL),
            'password' => Hash::make($tempPassword),
            'role' => $request->role,
            'must_change_password' => true,
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'avatar_url' => $user->avatar_url,
            ],
            'credentials' => [
                'email' => $user->email,
                'temp_password' => $tempPassword,
            ],
        ], 201);
    }

    /**
     * Update a user.
     * - platform_admin: can update any user
     * - org_super_admin: can update users in their organization except platform_admin
     */
    public function updateUser(Request $request, $id): JsonResponse
    {
        $currentUser = $request->user();
        $orgId = Org::id($request);

        $user = User::findOrFail($id);

        if ($currentUser->role !== 'platform_admin' && $currentUser->role !== 'org_super_admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($currentUser->role === 'org_super_admin') {
            if ((int) $user->organization_id !== (int) $currentUser->organization_id) {
                return response()->json(['message' => 'You cannot manage users outside your organization.'], 403);
            }
            if ($user->role === 'platform_admin') {
                return response()->json(['message' => 'You cannot modify platform admin accounts.'], 403);
            }
        }

        $allowedRoles = $currentUser->role === 'platform_admin'
            ? ['platform_admin', 'org_super_admin', 'admin', 'candidate']
            : ($currentUser->role === 'org_super_admin'
                ? ['admin']
                : ['org_super_admin', 'admin', 'candidate']);

        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
            'role' => ['sometimes', 'string', 'in:' . implode(',', $allowedRoles)],
            'access_status' => ['sometimes', 'string', 'in:active,suspended,terminated'],
        ]);

        if ($request->filled('name')) {
            $user->name = htmlspecialchars(strip_tags($request->name), ENT_QUOTES, 'UTF-8');
        }

        if ($request->filled('email')) {
            $user->email = filter_var($request->email, FILTER_SANITIZE_EMAIL);
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        if ($request->filled('role')) {
            $newRole = $request->role;

            $user->role = $newRole;
        }

        if ($request->filled('access_status')) {
            // org_super_admin cannot change their own access status
            if ($currentUser->role === 'org_super_admin' && (int) $user->id === (int) $currentUser->id) {
                throw ValidationException::withMessages([
                    'access_status' => ['You cannot change your own access status.'],
                ]);
            }

            // org_super_admin cannot modify platform_admin access
            if ($currentUser->role === 'org_super_admin' && $user->role === 'platform_admin') {
                return response()->json(['message' => 'You cannot modify platform admin accounts.'], 403);
            }

            $user->access_status = $request->access_status;
        }

        $user->save();
        $user->refresh();

        return response()->json([
            'message' => 'User updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'access_status' => $user->access_status ?? 'active',
                'avatar_url' => $user->avatar_url,
            ],
        ]);
    }

    /**
     * Delete a user.
     * - platform_admin: can delete any user
     * - org_super_admin: can delete users in their organization except themselves
     */
    public function deleteUser(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $currentUser = $request->user();

        if ($currentUser->role !== 'platform_admin' && $currentUser->role !== 'org_super_admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($currentUser->role === 'org_super_admin') {
            if ((int) $user->organization_id !== (int) $currentUser->organization_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            if ($user->id === $currentUser->id) {
                throw ValidationException::withMessages([
                    'user' => ['You cannot delete your own account.'],
                ]);
            }
            if ($user->role === 'platform_admin') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        // Prevent users from deleting themselves
        if ($user->id === $currentUser->id) {
            throw ValidationException::withMessages([
                'user' => ['You cannot delete your own account.'],
            ]);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }
}

