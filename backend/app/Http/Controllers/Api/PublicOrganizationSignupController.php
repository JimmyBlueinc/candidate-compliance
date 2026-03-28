<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OrganizationWelcomeMail;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PublicOrganizationSignupController extends Controller
{
    public function signup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization_name' => ['required', 'string', 'max:255'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $orgName = trim($validated['organization_name']);

        $baseSlug = Str::slug($orgName);
        if (!$baseSlug) {
            $baseSlug = 'agency';
        }

        $slug = $baseSlug;
        $i = 0;
        while (Organization::query()->where('slug', $slug)->exists()) {
            $i++;
            $slug = $baseSlug . '-' . $i;
            if ($i > 500) {
                $slug = $baseSlug . '-' . Str::lower(Str::random(6));
                break;
            }
        }

        try {
            [$org, $owner, $tempPassword] = DB::transaction(function () use ($orgName, $slug, $validated) {
                $org = Organization::create([
                    'name' => $orgName,
                    'slug' => $slug,
                    'is_active' => true,
                    'onboarding_step' => 'subdomain',
                    'onboarding_completed_at' => null,
                ]);

                $tempPassword = Str::password(12);

                $owner = User::create([
                    'organization_id' => $org->id,
                    'name' => htmlspecialchars(strip_tags($validated['admin_name']), ENT_QUOTES, 'UTF-8'),
                    'email' => filter_var($validated['admin_email'], FILTER_SANITIZE_EMAIL),
                    'password' => Hash::make($tempPassword),
                    'must_change_password' => true,
                    'role' => 'org_super_admin',
                ]);

                return [$org, $owner, $tempPassword];
            });
        } catch (\Throwable $e) {
            Log::error('Public organization signup failed', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return response()->json([
                'message' => 'Unable to complete signup right now. Please try again later.',
            ], 500);
        }

        $appUrl = rtrim(config('app.url'), '/');
        $loginUrl = $appUrl . '/login';

        $emailSent = false;
        try {
            Mail::to($owner->email)->send(new OrganizationWelcomeMail(
                organizationName: $org->name,
                loginUrl: $loginUrl,
                email: $owner->email,
                tempPassword: $tempPassword,
            ));

            $emailSent = true;
        } catch (\Throwable $e) {
            Log::error('Public organization signup email failed', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
        }

        $meta = [];

        $exposeTempPassword = filter_var(env('SIGNUP_EXPOSE_TEMP_PASSWORD', false), FILTER_VALIDATE_BOOL);
        if (app()->environment('local') || $exposeTempPassword) {
            $meta['temp_password'] = $tempPassword;
        }

        $meta['email_sent'] = $emailSent;

        return response()->json([
            'data' => [
                'organization' => [
                    'id' => $org->id,
                    'name' => $org->name,
                    'slug' => $org->slug,
                    'subdomain' => $org->subdomain,
                    'onboarding_completed_at' => $org->onboarding_completed_at?->toIso8601String(),
                    'onboarding_step' => $org->onboarding_step,
                ],
                'owner' => [
                    'id' => $owner->id,
                    'name' => $owner->name,
                    'email' => $owner->email,
                    'role' => $owner->role,
                ],
            ],
            'meta' => (object) $meta,
            'message' => 'Organization signup successful. Check your email for login details.',
        ], 201);
    }
}
