<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GoogleAuthController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        $google = $this->verifyGoogleToken($request->string('id_token')->toString());

        return response()->json([
            'profile' => [
                'email' => $google['email'] ?? null,
                'name' => $google['name'] ?? null,
                'picture' => $google['picture'] ?? null,
                'email_verified' => (bool) ($google['email_verified'] ?? false),
            ],
        ]);
    }

    public function authenticate(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => ['required', 'string'],
            'intent' => ['sometimes', 'string', 'in:login,signup'],
            'tenant_id' => ['sometimes', 'nullable', 'integer'],
            'role' => ['sometimes', 'string', 'in:admin,candidate'],
        ]);

        $google = $this->verifyGoogleToken($request->string('id_token')->toString());
        $email = strtolower(trim((string) ($google['email'] ?? '')));
        $name = trim((string) ($google['name'] ?? ''));
        $providerId = (string) ($google['sub'] ?? '');

        if ($email === '' || $providerId === '') {
            throw ValidationException::withMessages([
                'email' => ['Google token did not include a valid identity.'],
            ]);
        }

        $user = User::query()->where('email', $email)->first();
        $intent = (string) $request->input('intent', 'login');

        if (!$user && $intent === 'login') {
            throw ValidationException::withMessages([
                'email' => ['No account exists for this Google email. Please sign up first.'],
            ]);
        }

        if (!$user) {
            $orgId = $request->integer('tenant_id') ?: null;
            if (!$orgId) {
                throw ValidationException::withMessages([
                    'tenant_id' => ['Organization context is required for Google sign-up.'],
                ]);
            }

            $org = Organization::query()->find($orgId);
            if (!$org) {
                throw ValidationException::withMessages([
                    'tenant_id' => ['Organization was not found.'],
                ]);
            }

            $user = User::query()->create([
                'organization_id' => $org->id,
                'name' => $name !== '' ? $name : strtok($email, '@'),
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                'role' => (string) $request->input('role', 'candidate'),
                'auth_provider' => 'google',
                'provider_id' => $providerId,
                'avatar_path' => null,
            ]);
        } else {
            $user->auth_provider = $user->auth_provider ?: 'google';
            $user->provider_id = $user->provider_id ?: $providerId;
            $user->save();
        }

        if (in_array($user->access_status ?? 'active', ['suspended', 'terminated'], true)) {
            return response()->json([
                'message' => 'Your account access has been restricted. Please contact your organization administrator.',
            ], 403);
        }

        $expiresAt = now()->addDays(30);
        $token = $user->createToken('auth-token', ['*'], $expiresAt)->plainTextToken;
        $org = $user->organization_id ? Organization::query()->find($user->organization_id) : null;

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
                'organization' => $org ? [
                    'id' => $org->id,
                    'name' => $org->name,
                    'slug' => $org->slug,
                    'subdomain' => $org->subdomain,
                    'onboarding_step' => $org->onboarding_step,
                    'onboarding_completed_at' => $org->onboarding_completed_at?->toIso8601String(),
                ] : null,
                'needs_onboarding' => $org ? ($org->onboarding_completed_at === null) : false,
                'avatar_url' => $google['picture'] ?? $user->avatar_url,
                'updated_at' => $user->updated_at ? $user->updated_at->toIso8601String() : null,
            ],
            'token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
            'message' => 'Google authentication successful',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function verifyGoogleToken(string $idToken): array
    {
        $aud = (string) config('services.google.client_id');
        if ($aud === '') {
            throw ValidationException::withMessages([
                'google' => ['Google sign-in is not configured on this environment.'],
            ]);
        }

        $response = Http::timeout(8)->get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if (!$response->ok()) {
            throw ValidationException::withMessages([
                'google' => ['Google token validation failed.'],
            ]);
        }

        $payload = $response->json();
        if (!is_array($payload)) {
            throw ValidationException::withMessages([
                'google' => ['Google token payload was invalid.'],
            ]);
        }

        if (($payload['aud'] ?? null) !== $aud) {
            throw ValidationException::withMessages([
                'google' => ['Google client mismatch.'],
            ]);
        }

        if (($payload['email_verified'] ?? 'false') !== 'true') {
            throw ValidationException::withMessages([
                'google' => ['Google account email is not verified.'],
            ]);
        }

        return $payload;
    }
}

