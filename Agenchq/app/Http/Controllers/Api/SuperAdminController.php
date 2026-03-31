<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\CandidateLoginCodeMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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

        return response()->api($response, 201);
    }

    public function setPlatformAdminPassword(Request $request): JsonResponse
    {
        $secretKey = env('SUPER_ADMIN_SECRET_KEY', 'change-this-secret-key-in-production');

        $request->validate([
            'secret_key' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($request->secret_key !== $secretKey) {
            throw ValidationException::withMessages([
                'secret_key' => ['Invalid secret key.'],
            ]);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || $user->role !== 'platform_admin') {
            return response()->json([
                'message' => 'Platform admin not found for the provided email.',
            ], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->api([
            'message' => 'Platform admin password updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'avatar_url' => $user->avatar_url,
            ],
        ], 200);
    }

    public function privateUpsertPlatformAdmin(Request $request): JsonResponse
    {
        $secretKey = env('SUPER_ADMIN_SECRET_KEY', 'change-this-secret-key-in-production');

        $request->validate([
            'secret_key' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($request->secret_key !== $secretKey) {
            throw ValidationException::withMessages([
                'secret_key' => ['Invalid secret key.'],
            ]);
        }

        $email = filter_var($request->email, FILTER_SANITIZE_EMAIL);
        $existing = User::where('email', $email)->first();

        if ($existing && $existing->role !== 'platform_admin') {
            return response()->json([
                'message' => 'A user already exists with this email, but is not a platform admin.',
            ], 409);
        }

        $user = $existing ?: new User();
        $user->organization_id = null;
        $user->name = htmlspecialchars(strip_tags($request->name), ENT_QUOTES, 'UTF-8');
        $user->email = $email;
        $user->password = Hash::make($request->password);
        $user->role = 'platform_admin';
        $user->save();

        return response()->api([
            'message' => $existing ? 'Platform admin updated successfully' : 'Platform admin created successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'avatar_url' => $user->avatar_url,
            ],
        ], $existing ? 200 : 201);
    }

    public function privateResetFirstPlatformAdminPassword(Request $request): JsonResponse
    {
        $secretKey = env('SUPER_ADMIN_SECRET_KEY', 'change-this-secret-key-in-production');

        $request->validate([
            'secret_key' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($request->secret_key !== $secretKey) {
            throw ValidationException::withMessages([
                'secret_key' => ['Invalid secret key.'],
            ]);
        }

        $user = User::where('role', 'platform_admin')->orderBy('id')->first();

        if (!$user) {
            return response()->json([
                'message' => 'No platform admin exists yet. Use upsert to create one.',
            ], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->api([
            'message' => 'Platform admin password reset successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'avatar_url' => $user->avatar_url,
            ],
        ], 200);
    }

    public function privateSendTestEmail(Request $request): JsonResponse
    {
        $secretKey = env('SUPER_ADMIN_SECRET_KEY', 'change-this-secret-key-in-production');

        $request->validate([
            'secret_key' => ['required', 'string'],
            'to' => ['required', 'string', 'email', 'max:255'],
        ]);

        if ($request->secret_key !== $secretKey) {
            throw ValidationException::withMessages([
                'secret_key' => ['Invalid secret key.'],
            ]);
        }

        $to = strtolower(trim((string) $request->input('to')));

        try {
            Mail::to($to)->send(new CandidateLoginCodeMail(
                organizationName: 'AgencyHQ',
                code: '123456',
                expiresMinutes: 10,
            ));

            return response()->api([
                'message' => 'Test email sent (attempted).',
                'mailer' => config('mail.default'),
                'from' => config('mail.from.address'),
                'to' => $to,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Test email failed', [
                'to' => $to,
                'mailer' => config('mail.default'),
                'from' => config('mail.from.address'),
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return response()->json([
                'message' => 'Test email failed',
                'mailer' => config('mail.default'),
                'from' => config('mail.from.address'),
                'to' => $to,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ], 500);
        }
    }
}

