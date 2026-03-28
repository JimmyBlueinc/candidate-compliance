<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\LoginAlertMail;
use App\Mail\UserWelcomeMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use App\Support\Org;
use App\Models\Organization;
use App\Models\OrganizationDomain;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        $orgId = Org::id($request);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where(function ($query) use ($orgId) {
                    if ($orgId) {
                        return $query->where('organization_id', $orgId);
                    }
                    return $query->whereNull('organization_id');
                }),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['sometimes', 'string', 'in:admin,candidate'],
            'avatar' => ['sometimes', 'file', 'image', 'max:2048'], // 2MB
        ]);

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

        $sendWelcomeEmail = filter_var(env('SEND_WELCOME_EMAIL_ON_REGISTER', true), FILTER_VALIDATE_BOOL);
        if ($sendWelcomeEmail) {
            try {
                $appUrl = rtrim(config('app.url'), '/');
                $loginUrl = $appUrl . '/login';
                $orgName = $org?->name ?? 'your organization';

                Mail::to($user->email)->send(new UserWelcomeMail(
                    name: $user->name,
                    organizationName: $orgName,
                    loginUrl: $loginUrl,
                ));
            } catch (\Throwable $e) {
                Log::error('Welcome email failed', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                ]);
            }
        }

        // Set token expiration: 30 days for new registrations (extended session)
        $expiresAt = now()->addDays(30);
        $token = $user->createToken('auth-token', ['*'], $expiresAt)->plainTextToken;

        // Refresh to get latest avatar_url
        $user->refresh();

        $orgForPayload = $org ?: ($user->organization_id ? Organization::query()->find($user->organization_id) : null);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? null,
                'address' => $user->address ?? null,
                'job_title' => $user->job_title ?? null,
                'department' => $user->department ?? null,
                'role' => $user->role,
                'access_status' => $user->access_status ?? 'active',
                'must_change_password' => (bool) ($user->must_change_password ?? false),
                'organization_id' => $user->organization_id,
                'organization' => $orgForPayload ? [
                    'id' => $orgForPayload->id,
                    'name' => $orgForPayload->name,
                    'slug' => $orgForPayload->slug,
                    'subdomain' => $orgForPayload->subdomain,
                    'onboarding_step' => $orgForPayload->onboarding_step,
                    'onboarding_completed_at' => $orgForPayload->onboarding_completed_at?->toIso8601String(),
                ] : null,
                'needs_onboarding' => $orgForPayload ? ($orgForPayload->onboarding_completed_at === null) : false,
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
        Log::channel('stderr')->info('Login request received', [
            'method' => (string) $request->method(),
            'path' => (string) $request->path(),
            'ip' => (string) $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'has_cookie_header' => $request->headers->has('cookie'),
            'has_x_xsrf_token_header' => $request->headers->has('x-xsrf-token'),
            'has_authorization_header' => $request->headers->has('authorization'),
            'content_type' => (string) $request->header('content-type'),
            'accept' => (string) $request->header('accept'),
            'origin' => (string) $request->header('origin'),
            'referer' => (string) $request->header('referer'),
            'x_org_host' => (string) $request->header('x-org-host'),
        ]);

        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'remember_me' => ['sometimes', 'boolean'],
        ]);

        $email = strtolower(trim((string) $request->email));
        $orgId = $this->resolveOrganizationIdFromRequest($request);
        $user = null;

        if ($orgId) {
            $user = User::query()
                ->where('organization_id', $orgId)
                ->where('email', $email)
                ->first();
        } else {
            $matches = User::query()->where('email', $email)->limit(2)->get();
            if ($matches->count() > 1) {
                throw ValidationException::withMessages([
                    'email' => ['Multiple accounts use this email. Please log in from your organization subdomain.'],
                ]);
            }
            $user = $matches->first();
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            Log::warning('Login failed: invalid credentials', ['email' => $request->email]);
            throw ValidationException::withMessages([
                'email' => ['The email or password you entered is incorrect. Please try again.'],
            ]);
        }

        if (in_array($user->access_status ?? 'active', ['suspended', 'terminated'], true)) {
            return response()->json([
                'message' => 'Your account access has been restricted. Please contact your organization administrator.',
            ], 403);
        }

        // Set token expiration: 30 days for "remember me", 24 hours otherwise
        $expiresAt = $request->boolean('remember_me') 
            ? now()->addDays(30) 
            : now()->addHours(24);

        $token = $user->createToken('auth-token', ['*'], $expiresAt)->plainTextToken;

        $org = null;
        if ($user->role !== 'platform_admin') {
            $org = Org::model($request);
        }
        
        $orgForPayload = $org ?: ($user->organization_id ? Organization::query()->find($user->organization_id) : null);

        $sendLoginAlert = filter_var(env('SEND_LOGIN_ALERT_EMAIL', false), FILTER_VALIDATE_BOOL);
        if ($sendLoginAlert) {
            try {
                $orgName = $orgForPayload?->name ?? 'your organization';
                Mail::to($user->email)->send(new LoginAlertMail(
                    name: $user->name,
                    organizationName: $orgName,
                    ip: (string) $request->ip(),
                    userAgent: (string) $request->userAgent(),
                    loggedInAt: now()->toDateTimeString(),
                ));
            } catch (\Throwable $e) {
                Log::error('Login alert email failed', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                ]);
            }
        }

        // Refresh to get latest avatar_url
        $user->refresh();

        Log::channel('stderr')->info('Login response prepared', [
            'user_id' => $user->id,
            'has_token' => !empty($token),
            'token_length' => is_string($token) ? strlen($token) : null,
            'has_org' => $orgForPayload !== null,
        ]);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? null,
                'address' => $user->address ?? null,
                'job_title' => $user->job_title ?? null,
                'department' => $user->department ?? null,
                'role' => $user->role,
                'access_status' => $user->access_status ?? 'active',
                'must_change_password' => (bool) ($user->must_change_password ?? false),
                'organization_id' => $user->organization_id,
                'organization' => $orgForPayload ? [
                    'id' => $orgForPayload->id,
                    'name' => $orgForPayload->name,
                    'slug' => $orgForPayload->slug,
                    'subdomain' => $orgForPayload->subdomain,
                    'onboarding_step' => $orgForPayload->onboarding_step,
                    'onboarding_completed_at' => $orgForPayload->onboarding_completed_at?->toIso8601String(),
                ] : null,
                'needs_onboarding' => $orgForPayload ? ($orgForPayload->onboarding_completed_at === null) : false,
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
        $orgForPayload = $org ?: ($user->organization_id ? Organization::query()->find($user->organization_id) : null);
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'access_status' => $user->access_status ?? 'active',
                'must_change_password' => (bool) ($user->must_change_password ?? false),
                'organization_id' => $user->organization_id,
                'organization' => $orgForPayload ? [
                    'id' => $orgForPayload->id,
                    'name' => $orgForPayload->name,
                    'slug' => $orgForPayload->slug,
                    'subdomain' => $orgForPayload->subdomain,
                    'onboarding_step' => $orgForPayload->onboarding_step,
                    'onboarding_completed_at' => $orgForPayload->onboarding_completed_at?->toIso8601String(),
                ] : null,
                'needs_onboarding' => $orgForPayload ? ($orgForPayload->onboarding_completed_at === null) : false,
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
                'phone' => $user->phone ?? null,
                'address' => $user->address ?? null,
                'job_title' => $user->job_title ?? null,
                'department' => $user->department ?? null,
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
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where(function ($query) use ($user) {
                        if ($user->organization_id) {
                            return $query->where('organization_id', $user->organization_id);
                        }
                        return $query->whereNull('organization_id');
                    })
                    ->ignore($user->id),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'job_title' => ['sometimes', 'nullable', 'string', 'max:120'],
            'department' => ['sometimes', 'nullable', 'string', 'max:120'],
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

        if ($request->has('phone')) {
            $user->phone = $request->filled('phone')
                ? htmlspecialchars(strip_tags((string) $request->phone), ENT_QUOTES, 'UTF-8')
                : null;
        }

        if ($request->has('address')) {
            $user->address = $request->filled('address')
                ? htmlspecialchars(strip_tags((string) $request->address), ENT_QUOTES, 'UTF-8')
                : null;
        }

        if ($request->has('job_title')) {
            $user->job_title = $request->filled('job_title')
                ? htmlspecialchars(strip_tags((string) $request->job_title), ENT_QUOTES, 'UTF-8')
                : null;
        }

        if ($request->has('department')) {
            $user->department = $request->filled('department')
                ? htmlspecialchars(strip_tags((string) $request->department), ENT_QUOTES, 'UTF-8')
                : null;
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
                'phone' => $user->phone ?? null,
                'address' => $user->address ?? null,
                'job_title' => $user->job_title ?? null,
                'department' => $user->department ?? null,
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

        $email = strtolower(trim((string) $request->email));
        $orgId = $this->resolveOrganizationIdFromRequest($request);

        $user = null;
        if ($orgId) {
            $user = User::query()
                ->where('organization_id', $orgId)
                ->where('email', $email)
                ->first();
        } else {
            $matches = User::query()->where('email', $email)->limit(2)->get();
            if ($matches->count() === 1) {
                $user = $matches->first();
            }
        }

        // Don't reveal if email exists or not for security
        // Always return success message
        if ($user) {
            // Get frontend URL from config (works with cached config)
            $frontendUrl = config('app.frontend_url', 'https://agenchq.com');
            
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

        $email = strtolower(trim((string) $request->email));
        $orgId = $this->resolveOrganizationIdFromRequest($request);

        $user = null;
        if ($orgId) {
            $user = User::query()
                ->where('organization_id', $orgId)
                ->where('email', $email)
                ->first();
        } else {
            $matches = User::query()->where('email', $email)->limit(2)->get();
            if ($matches->count() > 1) {
                return response()->json([
                    'message' => 'Invalid or expired reset token.',
                ], 400);
            }
            $user = $matches->first();
        }

        if (!$user || !Password::broker()->tokenExists($user, (string) $request->token)) {
            return response()->json([
                'message' => 'Invalid or expired reset token.',
            ], 400);
        }

        $user->password = Hash::make((string) $request->password);
        $user->save();
        Password::broker()->deleteToken($user);

        return response()->json([
            'message' => 'Password has been reset successfully.',
        ]);
    }

    private function resolveOrganizationIdFromRequest(Request $request): ?int
    {
        $orgId = Org::id($request);
        if ($orgId) {
            return (int) $orgId;
        }

        $headerTenant = (int) ($request->header('X-Tenant-Id') ?: 0);
        if ($headerTenant > 0) {
            return $headerTenant;
        }

        $host = $request->header('X-Org-Host');
        if (!$host) {
            $host = (string) parse_url((string) $request->header('Origin'), PHP_URL_HOST);
        }
        if (!$host) {
            $host = (string) parse_url((string) $request->header('Referer'), PHP_URL_HOST);
        }

        $host = strtolower(trim((string) preg_replace('#:\\d+$#', '', (string) $host)));
        if ($host === '') {
            return null;
        }

        $domain = OrganizationDomain::query()
            ->where('domain', $host)
            ->where('is_active', true)
            ->first();
        if ($domain) {
            return (int) $domain->organization_id;
        }

        if (str_ends_with($host, '.agenchq.com')) {
            $subdomain = str_replace('.agenchq.com', '', $host);
            $org = Organization::query()
                ->where('subdomain', $subdomain)
                ->where('is_active', true)
                ->first();
            if ($org) {
                return (int) $org->id;
            }
        }

        return null;
    }
}


