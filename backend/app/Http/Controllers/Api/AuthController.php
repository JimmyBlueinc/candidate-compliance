<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use App\Support\Org;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['sometimes', 'string', 'in:admin,candidate'],
            'avatar' => ['sometimes', 'file', 'image', 'max:2048'], // 2MB
        ]);

        $orgId = Org::id($request);
        $org = Org::model($request);

        $user = User::create([
            'organization_id' => $orgId,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'candidate',
        ]);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', config('filesystems.default'));
            // Use direct DB update to ensure it's saved
            DB::table('users')->where('id', $user->id)->update([
                'avatar_path' => $path,
                'updated_at' => now(),
            ]);
            $user->refresh();
        }

        // Set token expiration: 30 days for new registrations (extended session)
        $expiresAt = now()->addDays(30);
        $token = $user->createToken('auth-token', ['*'], $expiresAt)->plainTextToken;

        // Refresh to get latest avatar_url
        $user->refresh();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'access_status' => $user->access_status ?? 'active',
                'must_change_password' => (bool) ($user->must_change_password ?? false),
                'organization_id' => $user->organization_id,
                'organization' => $org ? [
                    'id' => $org->id,
                    'name' => $org->name,
                    'slug' => $org->slug,
                ] : null,
                'avatar_url' => $user->avatar_url,
                'updated_at' => $user->updated_at ? $user->updated_at->toIso8601String() : null,
            ],
            'token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
            'message' => 'User registered successfully',
        ], 201);
    }

    /**
     * Login user and create token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'remember_me' => ['sometimes', 'boolean'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (in_array($user->access_status ?? 'active', ['suspended', 'terminated'], true)) {
            return response()->json([
                'message' => 'Your account access has been restricted. Please contact your organization administrator.',
            ], 403);
        }

        // Revoke all existing tokens (optional - for single device login)
        // $user->tokens()->delete();

        // Set token expiration: 30 days for "remember me", 24 hours otherwise
        $expiresAt = $request->boolean('remember_me') 
            ? now()->addDays(30) 
            : now()->addHours(24);

        $token = $user->createToken('auth-token', ['*'], $expiresAt)->plainTextToken;

        $org = Org::model($request);

        // Refresh to get latest avatar_url
        $user->refresh();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'access_status' => $user->access_status ?? 'active',
                'must_change_password' => (bool) ($user->must_change_password ?? false),
                'organization_id' => $user->organization_id,
                'organization' => $org ? [
                    'id' => $org->id,
                    'name' => $org->name,
                    'slug' => $org->slug,
                ] : null,
                'avatar_url' => $user->avatar_url,
                'updated_at' => $user->updated_at ? $user->updated_at->toIso8601String() : null,
            ],
            'token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
            'message' => 'Login successful',
        ]);
    }

    /**
     * Logout user (revoke token).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get authenticated user.
     * This endpoint is used for real-time token validation.
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $request->user()->currentAccessToken();
        $org = Org::model($request);
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'access_status' => $user->access_status ?? 'active',
                'must_change_password' => (bool) ($user->must_change_password ?? false),
                'organization_id' => $user->organization_id,
                'organization' => $org ? [
                    'id' => $org->id,
                    'name' => $org->name,
                    'slug' => $org->slug,
                ] : null,
                'avatar_url' => $user->avatar_url,
                'updated_at' => $user->updated_at ? $user->updated_at->toIso8601String() : null,
            ],
            'token_expires_at' => $token->expires_at ? $token->expires_at->toIso8601String() : null,
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->password = Hash::make($request->password);
        $user->must_change_password = false;
        $user->save();
        $user->refresh();

        $org = Org::model($request);

        return response()->json([
            'message' => 'Password updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'access_status' => $user->access_status ?? 'active',
                'must_change_password' => (bool) ($user->must_change_password ?? false),
                'organization_id' => $user->organization_id,
                'organization' => $org ? [
                    'id' => $org->id,
                    'name' => $org->name,
                    'slug' => $org->slug,
                ] : null,
                'avatar_url' => $user->avatar_url,
                'updated_at' => $user->updated_at ? $user->updated_at->toIso8601String() : null,
            ],
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        // Log request details for debugging
        Log::info('Update profile request received', [
            'user_id' => $user->id,
            'has_file' => $request->hasFile('avatar'),
            'all_files' => array_keys($request->allFiles()),
            'request_content_type' => $request->header('Content-Type'),
            'request_method' => $request->method(),
        ]);

        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
            'current_password' => ['required_with:password', 'string'],
            'avatar' => ['sometimes', 'file', 'image', 'max:2048'], // 2MB
        ]);

        // Verify current password if changing password
        if ($request->filled('password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['The current password is incorrect.'],
                ]);
            }
        }

        // Update name if provided (sanitized)
        if ($request->filled('name')) {
            $user->name = htmlspecialchars(strip_tags($request->name), ENT_QUOTES, 'UTF-8');
        }

        // Update email if provided (sanitized)
        if ($request->filled('email')) {
            $user->email = filter_var($request->email, FILTER_SANITIZE_EMAIL);
        }

        // Update password if provided
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        // Handle avatar upload FIRST, before other changes
        Log::info('Checking for avatar file', [
            'has_file' => $request->hasFile('avatar'),
            'all_files' => $request->allFiles(),
            'user_id' => $user->id,
        ]);
        
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            Log::info('Avatar file received', [
                'user_id' => $user->id,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'file_mime' => $file->getMimeType(),
            ]);
            
            // Delete old avatar if exists
            if ($user->avatar_path) {
                \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->delete($user->avatar_path);
            }
            
            $path = $file->store('avatars', config('filesystems.default'));
            Log::info('Avatar file stored', [
                'user_id' => $user->id,
                'stored_path' => $path,
            ]);
            
            // Use direct DB update to ensure it's saved and not overwritten
            $updated = DB::table('users')->where('id', $user->id)->update([
                'avatar_path' => $path,
                'updated_at' => now(),
            ]);
            
            Log::info('Direct DB update result', [
                'user_id' => $user->id,
                'rows_updated' => $updated,
                'path' => $path,
            ]);
            
            // Verify it was saved by checking database directly
            $dbPath = DB::table('users')->where('id', $user->id)->value('avatar_path');
            Log::info('Database check after update', [
                'user_id' => $user->id,
                'expected_path' => $path,
                'db_path' => $dbPath,
                'match' => $dbPath === $path,
            ]);
            
            // Refresh the model to get the updated data
            $user->refresh();
            
            // Verify it was saved
            if ($user->avatar_path !== $path) {
                Log::error('Avatar path still mismatch after direct DB update', [
                    'user_id' => $user->id,
                    'expected' => $path,
                    'actual' => $user->avatar_path,
                    'db_check' => $dbPath,
                ]);
                // Try one more time with explicit save
                $user->avatar_path = $path;
                $user->save();
                $user->refresh();
            } else {
                Log::info('Avatar path saved successfully', [
                    'user_id' => $user->id,
                    'path' => $path,
                    'avatar_url' => $user->avatar_url,
                ]);
            }
        } else {
            Log::info('No avatar file in request', [
                'user_id' => $user->id,
                'request_keys' => array_keys($request->all()),
            ]);
        }
        
        // Save other profile changes (name, email, password)
        // Make sure we preserve avatar_path when saving
        $avatarPathToPreserve = $user->avatar_path;
        $user->save();
        
        // If avatar_path was lost during save, restore it
        if ($avatarPathToPreserve && $user->avatar_path !== $avatarPathToPreserve) {
            Log::warning('Avatar path was lost during save, restoring it', [
                'user_id' => $user->id,
                'expected' => $avatarPathToPreserve,
                'actual' => $user->avatar_path,
            ]);
            DB::table('users')->where('id', $user->id)->update(['avatar_path' => $avatarPathToPreserve]);
            $user->refresh();
        }
        
        // Final refresh to ensure avatar_url is up to date
        $user->refresh();
        
        // Log final state
        Log::info('Profile update final state', [
            'user_id' => $user->id,
            'avatar_path' => $user->avatar_path,
            'avatar_url' => $user->avatar_url,
        ]);

        // Ensure we get the latest avatar_url
        $avatarUrl = $user->avatar_url;
        
        // Log the response data for debugging
        Log::info('Profile update response', [
            'user_id' => $user->id,
            'avatar_path' => $user->avatar_path,
            'avatar_url' => $avatarUrl,
            'has_avatar_path' => !empty($user->avatar_path),
        ]);
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'avatar_url' => $avatarUrl,
                'updated_at' => $user->updated_at ? $user->updated_at->toIso8601String() : null,
            ],
            'message' => 'Profile updated successfully',
        ]);
    }

    /**
     * Send password reset link.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        // Don't reveal if email exists or not for security
        // Always return success message
        if ($user) {
            // Get frontend URL from config or use default
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            
            // Create password reset token
            $token = Password::createToken($user);
            
            // Build reset URL with frontend URL
            $resetUrl = rtrim($frontendUrl, '/') . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);
            
            // Send custom notification with frontend URL
            $user->sendPasswordResetNotification($token, $resetUrl);
        }

        // Always return the same message for security
        return response()->json([
            'message' => 'If that email address exists, we will send a password reset link.',
        ], 200);
    }

    /**
     * Reset password using token.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password has been reset successfully.',
            ]);
        }

        return response()->json([
            'message' => 'Invalid or expired reset token.',
        ], 400);
    }
}


