<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SuperAdminController extends Controller
{
    /**
     * Create a super admin account.
     * This is a special endpoint for creating the first super admin or additional super admins.
     * Should be protected by a special secret key or only accessible by existing super admins.
     */
    public function createSuperAdmin(Request $request): JsonResponse
    {
        // Check if there are any existing super admins
        $hasSuperAdmin = User::where('role', 'platform_admin')->exists();
        
        // Debug: Log the check result (remove in production)
        \Log::info('Super admin creation attempt', [
            'has_super_admin' => $hasSuperAdmin,
            'has_secret_key' => $request->has('secret_key'),
            'has_auth_token' => $request->bearerToken() !== null,
        ]);
        
        // If no platform admin exists, require a secret key
        // If platform admin exists, require platform admin authentication
        if (!$hasSuperAdmin) {
            // First super admin creation - require secret key
            $secretKey = env('SUPER_ADMIN_SECRET_KEY', 'change-this-secret-key-in-production');
            
            $request->validate([
                'secret_key' => ['required', 'string'],
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);
            
            if ($request->secret_key !== $secretKey) {
                throw ValidationException::withMessages([
                    'secret_key' => ['Invalid secret key.'],
                ]);
            }
        } else {
            // Additional platform admin creation - require existing platform admin authentication
            // Manually authenticate the token since route doesn't have auth middleware
            $currentUser = null;
            if ($request->bearerToken()) {
                try {
                    $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->bearerToken());
                    if ($token) {
                        $currentUser = $token->tokenable;
                    }
                } catch (\Exception $e) {
                    // Token invalid or expired
                }
            }
            
            if (!$currentUser || $currentUser->role !== 'platform_admin') {
                return response()->json([
                    'message' => 'Unauthorized. Platform admin access required. Please log in as a platform admin.',
                    'hint' => 'A super admin already exists in the system. You must be logged in as a super admin to create additional super admin accounts. If you are trying to create the first super admin, please check the database or contact support.',
                ], 403);
            }
            
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);
        }

        $user = User::create([
            'organization_id' => null, // Platform admins are global
            'name' => htmlspecialchars(strip_tags($request->name), ENT_QUOTES, 'UTF-8'),
            'email' => filter_var($request->email, FILTER_SANITIZE_EMAIL),
            'password' => Hash::make($request->password),
            'role' => 'platform_admin',
        ]);

        // Only return token if this is the first super admin (for auto-login)
        // If existing super admin created it, don't return token (they're already logged in)
        $response = [
            'message' => 'Platform admin account created successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'avatar_url' => $user->avatar_url,
            ],
        ];

        if (!$hasSuperAdmin) {
            // First super admin - return token for auto-login
            $expiresAt = now()->addDays(30);
            $token = $user->createToken('auth-token', ['*'], $expiresAt)->plainTextToken;
            $response['token'] = $token;
            $response['expires_at'] = $expiresAt->toIso8601String();
        }

        return response()->json($response, 201);
    }
}

