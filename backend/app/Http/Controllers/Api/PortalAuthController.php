<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\CandidateLoginCodeMail;
use App\Models\Candidate;
use App\Models\CandidateCredential;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PortalAuthController extends Controller
{
    public function requestCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $email = strtolower(trim($validated['email']));

        $candidate = Candidate::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $orgId)
            ->where('email', $email)
            ->first();

        if (!$candidate) {
            return response()->json([
                'message' => 'If your email is recognized, a login code will be issued.',
            ]);
        }

        $code = (string) random_int(100000, 999999);
        $codeHash = Hash::make($code);

        DB::table('candidate_login_codes')
            ->where('organization_id', $orgId)
            ->where('email', $email)
            ->delete();

        DB::table('candidate_login_codes')->insert([
            'organization_id' => $orgId,
            'email' => $email,
            'code_hash' => $codeHash,
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $orgName = Org::model($request)?->name ?? 'your organization';
            Mail::to($email)->send(new CandidateLoginCodeMail(
                organizationName: $orgName,
                code: $code,
                expiresMinutes: 10,
            ));
        } catch (\Throwable $e) {
            Log::error('Candidate portal login code email failed', [
                'tenant_id' => $orgId,
                'email' => $email,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
        }

        Log::info('Candidate portal login code issued', [
            'tenant_id' => $orgId,
            'email' => $email,
            'code' => $code,
        ]);

        return response()->json([
            'message' => 'Login code issued.',
        ]);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $email = strtolower(trim($validated['email']));
        $code = trim($validated['code']);

        $row = DB::table('candidate_login_codes')
            ->where('organization_id', $orgId)
            ->where('email', $email)
            ->orderByDesc('id')
            ->first();

        if (!$row || !$row->expires_at || now()->greaterThan($row->expires_at)) {
            return response()->json([
                'message' => 'Invalid or expired code.',
            ], 422);
        }

        if ((int) ($row->attempts ?? 0) >= 5) {
            return response()->json([
                'message' => 'Too many attempts. Request a new code.',
            ], 429);
        }

        if (!Hash::check($code, (string) $row->code_hash)) {
            DB::table('candidate_login_codes')->where('id', $row->id)->update([
                'attempts' => (int) ($row->attempts ?? 0) + 1,
                'updated_at' => now(),
            ]);

            return response()->json([
                'message' => 'Invalid or expired code.',
            ], 422);
        }

        $candidate = Candidate::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $orgId)
            ->where('email', $email)
            ->first();

        if (!$candidate) {
            return response()->json([
                'message' => 'Invalid login.',
            ], 403);
        }

        $existingUser = User::query()
            ->where('organization_id', $orgId)
            ->where('email', $email)
            ->first();

        $user = $existingUser ?: User::create([
            'organization_id' => $orgId,
            'name' => $candidate->name ?: ($candidate->first_name ? trim(($candidate->first_name ?? '') . ' ' . ($candidate->last_name ?? '')) : 'Candidate'),
            'email' => $email,
            'password' => Hash::make(Str::random(64)),
            'role' => 'candidate',
            'access_status' => 'active',
            'must_change_password' => false,
        ]);

        if (!$existingUser) {
            $user->refresh();
        }

        if ((string) ($user->role ?? '') !== 'candidate') {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        if (!$candidate->user_id) {
            $candidate->user_id = $user->id;
            $candidate->save();
        }

        DB::table('candidate_login_codes')->where('id', $row->id)->delete();

        $expiresAt = now()->addHours(24);
        $token = $user->createToken('candidate-portal', ['portal'], $expiresAt)->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'organization_id' => $user->organization_id,
            ],
            'token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
            'message' => 'Logged in successfully.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || (string) ($user->role ?? '') !== 'candidate') {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $candidate = Candidate::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $orgId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->first();

        $credentialsCount = 0;
        $approvedCredentialsCount = 0;

        if ($candidate) {
            $credentialsCount = CandidateCredential::query()
                ->where('tenant_id', $orgId)
                ->where('candidate_id', $candidate->id)
                ->count();

            $approvedCredentialsCount = CandidateCredential::query()
                ->where('tenant_id', $orgId)
                ->where('candidate_id', $candidate->id)
                ->where('status', 'verified')
                ->count();
        }

        return response()->json([
            'candidate' => $candidate ? [
                'id' => $candidate->id,
                'tenant_id' => $candidate->tenant_id,
                'first_name' => $candidate->first_name,
                'last_name' => $candidate->last_name,
                'name' => $candidate->name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'specialty' => $candidate->specialty,
                'last_applied_at' => $candidate->last_applied_at?->toIso8601String(),
            ] : null,
            'credentials_count' => $credentialsCount,
            'approved_credentials_count' => $approvedCredentialsCount,
        ]);
    }
}
