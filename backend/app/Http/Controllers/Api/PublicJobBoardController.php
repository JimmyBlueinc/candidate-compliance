<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\JobOrder;
use App\Models\Placement;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PublicJobBoardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = JobOrder::query()
            ->withoutGlobalScopes()
            ->leftJoin('organizations', 'job_orders.tenant_id', '=', 'organizations.id')
            ->leftJoin('facilities', 'job_orders.facility_id', '=', 'facilities.id')
            ->where('job_orders.published', true)
            ->where('job_orders.status', 'open')
            ->where('organizations.is_active', true);

        if ($request->filled('org')) {
            $query->where('organizations.slug', $request->input('org'));
        }

        if ($request->filled('specialty')) {
            $query->where('job_orders.specialty', $request->input('specialty'));
        }

        if ($request->filled('work_mode')) {
            $query->where('job_orders.work_mode', $request->input('work_mode'));
        }

        if ($request->filled('city')) {
            $query->where('facilities.city', $request->input('city'));
        }

        if ($request->filled('state')) {
            $query->where('facilities.state', $request->input('state'));
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            if ($q !== '') {
                $query->where(function ($sub) use ($q) {
                    $sub->where('job_orders.title', 'like', '%' . $q . '%')
                        ->orWhere('job_orders.facility_name', 'like', '%' . $q . '%')
                        ->orWhere('job_orders.specialty', 'like', '%' . $q . '%')
                        ->orWhere('organizations.name', 'like', '%' . $q . '%');
                });
            }
        }

        $rows = $query
            ->orderByDesc('job_orders.created_at')
            ->limit(500)
            ->get([
                'job_orders.id',
                'job_orders.tenant_id',
                'organizations.name as organization_name',
                'organizations.slug as organization_slug',
                'job_orders.title',
                'job_orders.facility_id',
                'job_orders.facility_name',
                'facilities.city as facility_city',
                'facilities.state as facility_state',
                'job_orders.specialty',
                'job_orders.work_mode',
                'job_orders.description',
                'job_orders.start_date',
                'job_orders.end_date',
                'job_orders.pay_rate',
                'job_orders.stipend_weekly',
                'job_orders.created_at',
            ]);

        return response()->json([
            'data' => $rows,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $job = JobOrder::query()
            ->withoutGlobalScopes()
            ->leftJoin('organizations', 'job_orders.tenant_id', '=', 'organizations.id')
            ->leftJoin('facilities', 'job_orders.facility_id', '=', 'facilities.id')
            ->where('job_orders.published', true)
            ->where('job_orders.status', 'open')
            ->where('organizations.is_active', true)
            ->where('job_orders.id', $id)
            ->first([
                'job_orders.id',
                'job_orders.tenant_id',
                'organizations.name as organization_name',
                'organizations.slug as organization_slug',
                'job_orders.title',
                'job_orders.role',
                'job_orders.required_staff',
                'job_orders.facility_id',
                'job_orders.facility_name',
                'facilities.address as facility_address',
                'facilities.city as facility_city',
                'facilities.state as facility_state',
                'facilities.country as facility_country',
                'job_orders.specialty',
                'job_orders.work_mode',
                'job_orders.pay_rate',
                'job_orders.bill_rate',
                'job_orders.stipend_weekly',
                'job_orders.start_date',
                'job_orders.end_date',
                'job_orders.description',
                'job_orders.created_at',
            ]);

        if (!$job) {
            return response()->json(['message' => 'Job not found.'], 404);
        }

        return response()->json([
            'data' => $job,
        ]);
    }

    /**
     * Apply-first flow:
     * - Candidate signs up scoped to the org that posted this job
     * - Enforces onboarding_stage check before allowing application
     */
    public function apply(Request $request, int $id): JsonResponse
    {
        $job = JobOrder::query()
            ->withoutGlobalScopes()
            ->where('published', true)
            ->where('status', 'open')
            ->find($id);

        if (!$job) {
            return response()->json(['message' => 'Job not found.'], 404);
        }

        $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:50'],
            'specialty' => ['nullable', 'string', 'max:255'],
        ]);

        $email = strtolower(trim((string) $request->input('email')));

        $existingUser = User::query()
            ->where('organization_id', $job->tenant_id)
            ->where('email', $email)
            ->first();

        if ($existingUser && !Hash::check((string) $request->input('password'), (string) $existingUser->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = $existingUser ?: User::create([
            'organization_id' => $job->tenant_id,
            'name' => trim((string) $request->input('first_name') . ' ' . (string) $request->input('last_name')),
            'email' => $email,
            'password' => Hash::make((string) $request->input('password')),
            'role' => 'candidate',
            'access_status' => 'active',
            'must_change_password' => false,
        ]);

        if ((string) ($user->role ?? '') !== 'candidate') {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $candidate = Candidate::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $job->tenant_id)
            ->where('email', $email)
            ->first();

        // Check if candidate has completed Phase 2 (documents) for job application
        if ($candidate) {
            $phase2Complete = $this->checkPhase2Complete($job->tenant_id, $candidate);
            
            if (!$phase2Complete) {
                $expiresAt = now()->addHours(24);
                $token = $user->createToken('candidate-portal', ['portal'], $expiresAt)->plainTextToken;

                return response()->json([
                    'requires_onboarding' => true,
                    'requires_phase2' => true,
                    'candidate' => [
                        'id' => $candidate->id,
                        'name' => $candidate->name,
                        'email' => $candidate->email,
                    ],
                    'token' => $token,
                    'expires_at' => $expiresAt->toIso8601String(),
                    'message' => 'Please upload your documents in My Profile to apply for jobs.',
                ], 403);
            }
        }

        // Create candidate if not exists (Phase 1 complete from apply)
        if (!$candidate) {
            $candidate = Candidate::query()->withoutGlobalScopes()->create([
                'tenant_id' => $job->tenant_id,
                'user_id' => $user->id,
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'name' => $user->name,
                'email' => $email,
                'phone' => $request->input('phone'),
                'specialty' => $request->input('specialty'),
                'tags' => [],
                'onboarding_stage' => 'basic_complete',
                'last_applied_at' => now(),
            ]);

            // New candidates need to complete Phase 2 (documents) to apply
            $expiresAt = now()->addHours(24);
            $token = $user->createToken('candidate-portal', ['portal'], $expiresAt)->plainTextToken;

            return response()->json([
                'requires_onboarding' => true,
                'requires_phase2' => true,
                'candidate' => [
                    'id' => $candidate->id,
                    'name' => $candidate->name,
                    'email' => $candidate->email,
                ],
                'token' => $token,
                'expires_at' => $expiresAt->toIso8601String(),
                'message' => 'Please upload your documents in My Profile to apply for jobs.',
            ], 403);
        }

        if (!$candidate->user_id) {
            $candidate->user_id = $user->id;
        }

        $candidate->last_applied_at = now();
        $candidate->save();

        $placement = Placement::query()->withoutGlobalScopes()->firstOrCreate([
            'tenant_id' => $job->tenant_id,
            'candidate_id' => $candidate->id,
            'job_order_id' => $job->id,
        ], [
            'stage' => 'applied',
        ]);

        $expiresAt = now()->addHours(24);
        $token = $user->createToken('candidate-portal', ['portal'], $expiresAt)->plainTextToken;

        return response()->json([
            'requires_onboarding' => false,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'organization_id' => $user->organization_id,
            ],
            'token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
            'placement' => [
                'id' => $placement->id,
                'stage' => $placement->stage,
            ],
            'message' => 'Application submitted.',
        ], 201);
    }

    /**
     * Check if candidate has completed Phase 2 (documents required for job application).
     */
    private function checkPhase2Complete(int $orgId, Candidate $candidate): bool
    {
        // Check required documents
        $uploadedKinds = \App\Models\Document::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $orgId)
            ->where('candidate_id', $candidate->id)
            ->where('type', 'onboarding')
            ->get()
            ->map(fn ($d) => strtolower(trim((string) ($d->meta['kind'] ?? ''))))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $requiredDocs = ['resume', 'government_id', 'license'];
        
        foreach ($requiredDocs as $kind) {
            if (!in_array($kind, $uploadedKinds, true)) {
                return false;
            }
        }

        return true;
    }
}
