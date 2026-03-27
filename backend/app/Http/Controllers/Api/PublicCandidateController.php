<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\TalentNetworkWelcomeMail;
use App\Models\Candidate;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PublicCandidateController extends Controller
{
    /**
     * Phase 1 Registration - Basic Intake (Low Friction)
     * Creates candidate with minimal info, no documents required
     */
    public function register(Request $request, string $orgSlug): JsonResponse
    {
        $organization = Organization::where('slug', $orgSlug)->first();

        if (!$organization) {
            return response()->json([
                'message' => 'Organization not found. Please check the URL and try again.',
                'error_code' => 'ORGANIZATION_NOT_FOUND',
            ], 404);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'role' => 'required|string|max:255',
            'years_experience' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'availability' => 'nullable|string|max:255',
        ]);

        // Check if candidate already exists for this org
        $existingCandidate = Candidate::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('email', $validated['email'])
            ->where('tenant_id', $organization->id)
            ->first();

        if ($existingCandidate) {
            return response()->json([
                'message' => 'This email is already registered. Please log in instead.',
                'error_code' => 'EMAIL_ALREADY_REGISTERED',
                'candidate_id' => $existingCandidate->id,
                'onboarding_stage' => $existingCandidate->onboarding_stage,
            ], 409);
        }

        // Check if user already exists for this email (in any organization)
        $existingUser = User::where('email', $validated['email'])->first();
        
        // If user exists but belongs to a different organization, still allow registration
        // but link to existing user account
        
        // Generate temp password for new users only
        $tempPassword = null;
        $user = $existingUser;
        
        if (!$existingUser) {
            // Generate a random temp password (12 chars, letters and numbers)
            $tempPassword = Str::password(12, true, true, false, false);
            
            $user = User::create([
                'organization_id' => $organization->id,
                'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                'email' => $validated['email'],
                'password' => Hash::make($tempPassword),
                'role' => 'candidate',
                'access_status' => 'active',
                'must_change_password' => true,
            ]);
        } else {
            // User exists - they already have a password, no temp password needed
            // They can log in with their existing credentials
            $tempPassword = null;
        }

        // Create candidate with Phase 1 complete
        $candidate = Candidate::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->create([
            'tenant_id' => $organization->id,
            'user_id' => $user->id,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'role' => $validated['role'],
            'years_experience' => $validated['years_experience'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'availability' => $validated['availability'] ?? null,
            'onboarding_stage' => 'basic_complete',
            'source' => 'public_registration',
        ]);

        // Send welcome email with temp password
        $profileUrl = "https://{$organization->subdomain}.agenchq.com/portal/profile";
        $loginUrl = "https://{$organization->subdomain}.agenchq.com/portal";
        try {
            Mail::to($candidate->email)->send(new TalentNetworkWelcomeMail(
                organizationName: $organization->name,
                profileUrl: $profileUrl,
                name: $candidate->first_name,
                tempPassword: $tempPassword,
                loginUrl: $loginUrl,
            ));
        } catch (\Exception $e) {
            \Log::error('[PublicCandidateController] Failed to send welcome email', [
                'error' => $e->getMessage(),
                'candidate_id' => $candidate->id,
            ]);
        }

        return response()->json([
            'message' => 'Registration successful. Check your email for next steps.',
            'candidate' => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'email' => $candidate->email,
                'onboarding_stage' => $candidate->onboarding_stage,
            ],
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
            ],
        ], 201);
    }

    /**
     * Get candidate status by email (for returning users)
     */
    public function status(Request $request, string $orgSlug): JsonResponse
    {
        $organization = Organization::where('slug', $orgSlug)->first();

        if (!$organization) {
            return response()->json([
                'message' => 'Organization not found. Please check the URL and try again.',
                'error_code' => 'ORGANIZATION_NOT_FOUND',
            ], 404);
        }

        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $candidate = Candidate::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('email', $validated['email'])
            ->where('tenant_id', $organization->id)
            ->first();

        if (!$candidate) {
            return response()->json([
                'exists' => false,
                'message' => 'No account found with this email. Please register to create an account.',
                'error_code' => 'ACCOUNT_NOT_FOUND',
            ]);
        }

        return response()->json([
            'exists' => true,
            'candidate' => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'email' => $candidate->email,
                'onboarding_stage' => $candidate->onboarding_stage,
            ],
            'message' => $candidate->onboarding_stage === 'fully_completed'
                ? 'Profile complete. You can apply for jobs.'
                : 'Please complete your profile to apply for jobs.',
        ]);
    }
}
