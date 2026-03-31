<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Scopes\TenantScope;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntakeController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->currentAccessToken() || !$user->tokenCan('intake')) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $orgId = (int) ($user->organization_id ?? 0);
        if (!$orgId) {
            return response()->json([
                'message' => 'Tenant context missing for API key.',
            ], 400);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'specialty' => ['required', 'string', 'max:255'],
            'resume' => ['sometimes', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ]);

        $email = strtolower(trim($validated['email']));

        $candidate = Candidate::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $orgId)
            ->where('email', $email)
            ->first();

        $fullName = trim($validated['first_name'] . ' ' . $validated['last_name']);

        $tags = ['Web-Lead', 'New', trim($validated['specialty'])];
        $tags = array_values(array_unique(array_filter($tags, fn ($t) => $t !== null && trim((string) $t) !== '')));

        $resumePath = null;
        if ($request->hasFile('resume')) {
            $resumePath = $request->file('resume')->store('tenants/' . $orgId . '/intake_resumes', config('filesystems.uploads_disk', config('filesystems.default')));
        }

        if ($candidate) {
            $existingTags = is_array($candidate->tags) ? $candidate->tags : [];
            $mergedTags = array_values(array_unique(array_merge($existingTags, $tags)));

            $candidate->fill([
                'tenant_id' => $orgId,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'name' => $fullName,
                'email' => $email,
                'phone' => $validated['phone'],
                'specialty' => $validated['specialty'],
                'tags' => $mergedTags,
                'last_applied_at' => now(),
            ]);

            if ($resumePath) {
                $candidate->resume_path = $resumePath;
            }

            $candidate->save();

            return response()->json([
                'message' => 'Candidate updated successfully.',
                'updated' => true,
                'data' => [
                    'id' => $candidate->id,
                    'tenant_id' => $candidate->tenant_id,
                    'first_name' => $candidate->first_name,
                    'last_name' => $candidate->last_name,
                    'email' => $candidate->email,
                    'phone' => $candidate->phone,
                    'specialty' => $candidate->specialty,
                    'tags' => $candidate->tags,
                    'last_applied_at' => $candidate->last_applied_at?->toIso8601String(),
                ],
            ], 200);
        }

        $candidate = Candidate::create([
            'tenant_id' => $orgId,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => $fullName,
            'email' => $email,
            'phone' => $validated['phone'],
            'specialty' => $validated['specialty'],
            'tags' => $tags,
            'last_applied_at' => now(),
            'resume_path' => $resumePath,
        ]);

        return response()->json([
            'message' => 'Candidate created successfully.',
            'updated' => false,
            'data' => [
                'id' => $candidate->id,
                'tenant_id' => $candidate->tenant_id,
                'first_name' => $candidate->first_name,
                'last_name' => $candidate->last_name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'specialty' => $candidate->specialty,
                'tags' => $candidate->tags,
                'last_applied_at' => $candidate->last_applied_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function recent(Request $request): JsonResponse
    {
        $user = $request->user();
        $orgId = (int) ($user?->organization_id ?? 0);
        if (!$orgId) {
            return response()->json([
                'message' => 'Tenant context missing.',
            ], 400);
        }

        $query = Candidate::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $orgId)
            ->whereNotNull('last_applied_at')
            ->whereJsonContains('tags', 'Web-Lead')
            ->orderByDesc('last_applied_at')
            ->limit(5)
            ->get()
            ->map(function (Candidate $c) {
                return [
                    'id' => $c->id,
                    'first_name' => $c->first_name,
                    'last_name' => $c->last_name,
                    'name' => $c->name,
                    'email' => $c->email,
                    'phone' => $c->phone,
                    'specialty' => $c->specialty,
                    'tags' => $c->tags,
                    'last_applied_at' => $c->last_applied_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'data' => $query,
        ]);
    }

    public function adminStore(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'specialty' => ['required', 'string', 'max:255'],
        ]);

        $email = strtolower(trim($validated['email']));

        $candidate = Candidate::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $orgId)
            ->where('email', $email)
            ->first();

        $fullName = trim($validated['first_name'] . ' ' . $validated['last_name']);

        $tags = ['Web-Lead', 'New', trim($validated['specialty'])];
        $tags = array_values(array_unique(array_filter($tags, fn ($t) => $t !== null && trim((string) $t) !== '')));

        if ($candidate) {
            $existingTags = is_array($candidate->tags) ? $candidate->tags : [];
            $mergedTags = array_values(array_unique(array_merge($existingTags, $tags)));

            $candidate->fill([
                'tenant_id' => $orgId,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'name' => $fullName,
                'email' => $email,
                'phone' => $validated['phone'],
                'specialty' => $validated['specialty'],
                'tags' => $mergedTags,
                'last_applied_at' => now(),
            ]);

            $candidate->save();

            return response()->json([
                'message' => 'Candidate updated successfully.',
                'updated' => true,
                'data' => [
                    'id' => $candidate->id,
                ],
            ], 200);
        }

        $candidate = Candidate::create([
            'tenant_id' => $orgId,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => $fullName,
            'email' => $email,
            'phone' => $validated['phone'],
            'specialty' => $validated['specialty'],
            'tags' => $tags,
            'last_applied_at' => now(),
        ]);

        return response()->json([
            'message' => 'Candidate created successfully.',
            'updated' => false,
            'data' => [
                'id' => $candidate->id,
            ],
        ], 201);
    }
}
