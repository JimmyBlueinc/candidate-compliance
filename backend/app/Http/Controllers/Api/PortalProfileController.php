<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Document;
use App\Models\Scopes\TenantScope;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalProfileController extends Controller
{
    private function getCandidate(Request $request, int $orgId): ?Candidate
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        return Candidate::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $orgId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->first();
    }

    private function getUploadedKinds(int $orgId, int $candidateId): array
    {
        return Document::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $orgId)
            ->where('candidate_id', $candidateId)
            ->where('type', 'onboarding')
            ->get()
            ->map(fn (Document $d) => strtolower(trim((string) ($d->meta['kind'] ?? ''))))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function computeOnboarding(int $orgId, Candidate $candidate): array
    {
        // Phase 1: Personal Information (required for Jobs access)
        $phase1Fields = [
            'first_name',
            'last_name',
            'email',
            'phone',
            'address_line1',
            'city',
            'state',
            'postal_code',
            'country',
        ];

        $phase1Missing = [];
        foreach ($phase1Fields as $key) {
            $v = $candidate->{$key} ?? null;
            if ($v === null || (is_string($v) && trim($v) === '')) {
                $phase1Missing[] = $key;
            }
        }

        $phase1Complete = count($phase1Missing) === 0;

        // Phase 2: Credentials & Documents (required for job applications)
        $phase2Fields = [
            'specialty',
            'license_type',
            'years_experience',
            'work_authorization',
            'drug_screen',
            'vaccination',
        ];

        $phase2Missing = [];
        foreach ($phase2Fields as $key) {
            $v = $candidate->{$key} ?? null;
            if (in_array($key, ['work_authorization', 'drug_screen', 'vaccination'], true)) {
                $raw = $candidate->getRawOriginal($key);
                if ($raw === null) {
                    $phase2Missing[] = $key;
                }
                continue;
            }
            if ($v === null || (is_string($v) && trim($v) === '')) {
                $phase2Missing[] = $key;
            }
        }

        $uploadedKinds = $this->getUploadedKinds($orgId, $candidate->id);
        $requiredDocs = [
            'resume',
            'government_id',
            'license',
        ];

        $missingDocs = [];
        foreach ($requiredDocs as $kind) {
            if (!in_array($kind, $uploadedKinds, true)) {
                $missingDocs[] = $kind;
            }
        }

        $phase2Complete = count($phase2Missing) === 0 && count($missingDocs) === 0;

        return [
            'phase1_complete' => $phase1Complete,
            'phase2_complete' => $phase2Complete,
            'complete' => $phase2Complete, // Full completion for backward compatibility
            'phase1_missing' => $phase1Missing,
            'phase2_missing' => $phase2Missing,
            'missing_docs' => $missingDocs,
            'uploaded_kinds' => $uploadedKinds,
        ];
    }

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || (string) ($user->role ?? '') !== 'candidate') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $candidate = $this->getCandidate($request, $orgId);
        if (!$candidate) {
            return response()->json(['message' => 'Candidate profile not found.'], 404);
        }

        $onboarding = $this->computeOnboarding($orgId, $candidate);

        return response()->api([
            'candidate' => $candidate,
            'onboarding' => $onboarding,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || (string) ($user->role ?? '') !== 'candidate') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $candidate = $this->getCandidate($request, $orgId);
        if (!$candidate) {
            return response()->json(['message' => 'Candidate profile not found.'], 404);
        }

        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'specialty' => ['sometimes', 'nullable', 'string', 'max:255'],
            'license_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'years_experience' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:80'],
            'address_line1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'state' => ['sometimes', 'nullable', 'string', 'max:50'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:30'],
            'country' => ['sometimes', 'nullable', 'string', 'max:100'],
            'work_authorization' => ['sometimes', 'nullable', 'boolean'],
            'background_check' => ['sometimes', 'nullable', 'boolean'],
            'drug_screen' => ['sometimes', 'nullable', 'boolean'],
            'vaccination' => ['sometimes', 'nullable', 'boolean'],
        ]);

        $candidate->fill($validated);

        if (array_key_exists('first_name', $validated) || array_key_exists('last_name', $validated)) {
            $full = trim((string) ($candidate->first_name ?? '') . ' ' . (string) ($candidate->last_name ?? ''));
            if ($full !== '') {
                $candidate->name = $full;
            }
        }

        $candidate->save();

        $onboarding = $this->computeOnboarding($orgId, $candidate);
        if (($onboarding['complete'] ?? false) && !$candidate->onboarding_completed_at) {
            $candidate->onboarding_completed_at = now();
            $candidate->save();
        }

        return response()->api([
            'candidate' => $candidate->fresh(),
            'onboarding' => $onboarding,
        ], 200, [], 'Profile updated.');
    }

    public function upload(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || (string) ($user->role ?? '') !== 'candidate') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $candidate = $this->getCandidate($request, $orgId);
        if (!$candidate) {
            return response()->json(['message' => 'Candidate profile not found.'], 404);
        }

        $validated = $request->validate([
            'kind' => ['required', 'string', 'in:resume,government_id,license,work_authorization,background_check,drug_screen,vaccination'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $kind = strtolower(trim((string) $validated['kind']));
        $file = $validated['file'];
        $path = $file->store('tenants/' . $orgId . '/candidate_docs', config('filesystems.default'));

        $label = match ($kind) {
            'resume' => 'Resume',
            'government_id' => 'Government ID',
            'license' => 'License',
            'work_authorization' => 'Work Authorization',
            'background_check' => 'Background Check',
            'drug_screen' => 'Drug Screen',
            'vaccination' => 'Vaccination Record',
            default => ucfirst(str_replace('_', ' ', $kind)),
        };

        $doc = Document::create([
            'tenant_id' => $orgId,
            'candidate_id' => $candidate->id,
            'type' => 'onboarding',
            'name' => $label,
            'file_path' => $path,
            'review_status' => 'pending_review',
            'meta' => [
                'kind' => $kind,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ],
        ]);

        $onboarding = $this->computeOnboarding($orgId, $candidate);

        return response()->api([
            'document' => [
                'id' => $doc->id,
                'name' => $doc->name,
                'review_status' => $doc->review_status,
            ],
            'onboarding' => $onboarding,
        ], 201, [], 'Document uploaded.');
    }
}
