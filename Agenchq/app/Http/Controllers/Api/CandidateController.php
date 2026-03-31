<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateCredential;
use App\Services\ComplianceService;
use App\Services\CredentialService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Document;
use App\Models\JobOrder;
use App\Models\OrganizationSetting;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use App\Support\Org;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CandidateController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'specialty' => ['sometimes', 'nullable', 'string', 'max:255'],
            'job_order_id' => ['sometimes', 'nullable', 'integer'],
            'sort_match' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $query = Candidate::query()
            ->where('tenant_id', $orgId);

        if (!empty($validated['q'])) {
            $q = trim((string) $validated['q']);
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', '%' . $q . '%')
                    ->orWhere('first_name', 'like', '%' . $q . '%')
                    ->orWhere('last_name', 'like', '%' . $q . '%')
                    ->orWhere('email', 'like', '%' . $q . '%')
                    ->orWhere('phone', 'like', '%' . $q . '%');
            });
        }

        if (!empty($validated['specialty'])) {
            $query->where('specialty', $validated['specialty']);
        }

        $jobOrder = null;
        if (!empty($validated['job_order_id'])) {
            $jobOrder = JobOrder::query()
                ->where('tenant_id', $orgId)
                ->find((int) $validated['job_order_id']);
        }

        $perPage = (int) ($validated['per_page'] ?? 25);
        if ($perPage < 1) {
            $perPage = 25;
        }
        if ($perPage > 200) {
            $perPage = 200;
        }

        $paginator = $query
            ->orderByDesc('last_applied_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $sortMatch = array_key_exists('sort_match', $validated) ? (bool) $validated['sort_match'] : true;
        $weights = $this->resolveMatchingWeights($orgId);
        $searchQuery = strtolower(trim((string) ($validated['q'] ?? '')));

        $items = collect($paginator->items())
            ->map(function (Candidate $candidate) use ($jobOrder, $searchQuery, $weights) {
                [$score, $reasons] = $this->calculateMatchScore($candidate, $jobOrder, $searchQuery, $weights);
                $row = $candidate->toArray();
                $row['match_score'] = $score;
                $row['match_reasons'] = $reasons;
                return $row;
            });

        if ($sortMatch) {
            $items = $items->sortByDesc('match_score')->values();
        }

        return response()->api($items->all(), 200, [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'matching_weights' => $weights,
        ]);
    }

    private function calculateMatchScore(Candidate $candidate, ?JobOrder $jobOrder, string $searchQuery, array $weights): array
    {
        $score = 0;
        $reasons = [];

        $candidateSpecialty = strtolower(trim((string) ($candidate->specialty ?? '')));
        $candidateName = strtolower(trim((string) ($candidate->name ?? '')));
        $candidateEmail = strtolower(trim((string) ($candidate->email ?? '')));
        $candidateTags = collect($candidate->tags ?? [])
            ->map(fn ($tag) => strtolower(trim((string) $tag)))
            ->filter()
            ->values()
            ->all();

        if ($jobOrder) {
            $jobSpecialty = strtolower(trim((string) ($jobOrder->specialty ?? '')));
            $jobTitle = strtolower(trim((string) ($jobOrder->title ?? '')));
            $jobRole = strtolower(trim((string) ($jobOrder->role ?? '')));

            if ($jobSpecialty !== '' && $candidateSpecialty === $jobSpecialty) {
                $score += (int) $weights['exact_specialty'];
                $reasons[] = 'Exact specialty match';
            } elseif ($jobSpecialty !== '' && str_contains($candidateSpecialty, $jobSpecialty)) {
                $score += (int) $weights['specialty_overlap'];
                $reasons[] = 'Specialty overlap';
            }

            $keywords = collect([$jobSpecialty, $jobTitle, $jobRole])
                ->flatMap(fn ($text) => preg_split('/\s+/', $text ?: ''))
                ->map(fn ($word) => strtolower(trim((string) $word)))
                ->filter(fn ($word) => strlen($word) >= 3)
                ->unique()
                ->values();

            $hits = 0;
            foreach ($keywords as $keyword) {
                if (
                    str_contains($candidateName, $keyword) ||
                    str_contains($candidateSpecialty, $keyword) ||
                    in_array($keyword, $candidateTags, true)
                ) {
                    $hits++;
                }
            }

            if ($hits > 0) {
                $score += min((int) $weights['keyword_alignment_cap'], $hits * (int) $weights['keyword_alignment_per_hit']);
                $reasons[] = 'Keyword alignment';
            }
        }

        if ($searchQuery !== '') {
            if (str_contains($candidateName, $searchQuery)) {
                $score += (int) $weights['name_relevance'];
                $reasons[] = 'Name relevance';
            }
            if (str_contains($candidateEmail, $searchQuery)) {
                $score += (int) $weights['email_relevance'];
            }
            if (str_contains($candidateSpecialty, $searchQuery)) {
                $score += (int) $weights['specialty_relevance'];
            }
        }

        $years = (float) ($candidate->years_experience ?? 0);
        if ($years > 0) {
            $score += min((int) $weights['experience_cap'], (int) floor($years));
            if ($years >= 5) {
                $reasons[] = 'Experienced candidate';
            }
        }

        if ($candidate->last_applied_at) {
            $days = abs((int) now()->diffInDays($candidate->last_applied_at));
            if ($days <= 30) {
                $score += (int) $weights['recency_30d'];
            } elseif ($days <= 90) {
                $score += (int) $weights['recency_90d'];
            }
        }

        return [$score, array_values(array_unique($reasons))];
    }

    private function resolveMatchingWeights(int $orgId): array
    {
        $defaults = OrganizationSetting::defaults()['module_preferences']['matching_weights'] ?? [];
        $settings = OrganizationSetting::query()
            ->where('organization_id', $orgId)
            ->first();

        $fromSettings = (array) data_get($settings?->module_preferences, 'matching_weights', []);
        $merged = array_merge($defaults, $fromSettings);

        return [
            'exact_specialty' => max(0, (int) ($merged['exact_specialty'] ?? 40)),
            'specialty_overlap' => max(0, (int) ($merged['specialty_overlap'] ?? 24)),
            'keyword_alignment_per_hit' => max(0, (int) ($merged['keyword_alignment_per_hit'] ?? 4)),
            'keyword_alignment_cap' => max(0, (int) ($merged['keyword_alignment_cap'] ?? 22)),
            'name_relevance' => max(0, (int) ($merged['name_relevance'] ?? 20)),
            'email_relevance' => max(0, (int) ($merged['email_relevance'] ?? 10)),
            'specialty_relevance' => max(0, (int) ($merged['specialty_relevance'] ?? 12)),
            'experience_cap' => max(0, (int) ($merged['experience_cap'] ?? 14)),
            'recency_30d' => max(0, (int) ($merged['recency_30d'] ?? 8)),
            'recency_90d' => max(0, (int) ($merged['recency_90d'] ?? 4)),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $rows = Candidate::query()
            ->where('tenant_id', $orgId)
            ->orderByDesc('last_applied_at')
            ->orderByDesc('created_at')
            ->limit(500)
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
                    'license_type' => $c->license_type,
                    'years_experience' => $c->years_experience,
                    'address_line1' => $c->address_line1,
                    'address_line2' => $c->address_line2,
                    'city' => $c->city,
                    'state' => $c->state,
                    'postal_code' => $c->postal_code,
                    'country' => $c->country,
                    'source' => $c->source,
                    'tags' => $c->tags,
                    'work_authorization' => $c->work_authorization,
                    'background_check' => $c->background_check,
                    'drug_screen' => $c->drug_screen,
                    'vaccination' => $c->vaccination,
                    'last_applied_at' => $c->last_applied_at?->toIso8601String(),
                    'resume_path' => $c->resume_path,
                    'created_at' => $c->created_at?->toIso8601String(),
                ];
            })
            ->values();

        return response()->api($rows);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $candidate = Candidate::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($id);

        $credentials = [];
        $statusCounts = [
            'active' => 0,
            'expiring_soon' => 0,
            'expired' => 0,
            'pending' => 0,
        ];

        $credentialRows = CandidateCredential::query()
            ->where('tenant_id', $orgId)
            ->where('candidate_id', $candidate->id)
            ->with(['credentialType:id,name'])
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        $credentialService = app(CredentialService::class);
        $credentials = $credentialRows
            ->map(function (CandidateCredential $cred) use (&$statusCounts, $credentialService, $candidate) {
                $status = (string) ($cred->status ?? 'pending');
                $expiresAt = $cred->expires_at;

                if ($status === 'verified') {
                    if ($expiresAt && $expiresAt->isPast()) {
                        $statusCounts['expired'] = (int) $statusCounts['expired'] + 1;
                    } elseif ($expiresAt && $expiresAt->lte(now()->addDays(30))) {
                        $statusCounts['expiring_soon'] = (int) $statusCounts['expiring_soon'] + 1;
                    } else {
                        $statusCounts['active'] = (int) $statusCounts['active'] + 1;
                    }
                } else {
                    $statusCounts['pending'] = (int) $statusCounts['pending'] + 1;
                }

                return [
                    'id' => $cred->id,
                    'credential_type' => (string) ($cred->credentialType?->name ?? 'Credential'),
                    'candidate_name' => (string) ($candidate->name ?: trim(($candidate->first_name ?? '') . ' ' . ($candidate->last_name ?? ''))),
                    'specialty' => $candidate->specialty,
                    'issue_date' => $cred->issued_at?->format('Y-m-d'),
                    'expiry_date' => $cred->expires_at?->format('Y-m-d'),
                    'status' => $status,
                    'status_color' => null,
                    'document_url' => $credentialService->signedDocumentUrl($cred),
                ];
            })
            ->values();

        $readiness = app(ComplianceService::class)->evaluateWorkerCompliance($orgId, (int) $candidate->id, null);
        $global = $readiness['global'] ?? [];
        $status = (string) ($readiness['status'] ?? ($global['status'] ?? 'ready'));
        $reason = null;
        $reasonType = null;

        $missing = $global['missing'] ?? [];
        $expired = $global['expired'] ?? [];
        $pending = $global['pending'] ?? [];
        $rejected = $global['rejected'] ?? [];

        if (is_array($missing) && count($missing) > 0) {
            $reason = $missing[0];
            $reasonType = 'missing';
        } elseif (is_array($expired) && count($expired) > 0) {
            $reason = $expired[0];
            $reasonType = 'expired';
        } elseif (is_array($rejected) && count($rejected) > 0) {
            $reason = $rejected[0];
            $reasonType = 'rejected';
        } elseif (is_array($pending) && count($pending) > 0) {
            $reason = $pending[0];
            $reasonType = 'pending';
        }

        return response()->api([
            'candidate' => [
                'id' => $candidate->id,
                'tenant_id' => $candidate->tenant_id,
                'user_id' => $candidate->user_id,
                'first_name' => $candidate->first_name,
                'last_name' => $candidate->last_name,
                'name' => $candidate->name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'specialty' => $candidate->specialty,
                'license_type' => $candidate->license_type,
                'years_experience' => $candidate->years_experience,
                'address_line1' => $candidate->address_line1,
                'address_line2' => $candidate->address_line2,
                'city' => $candidate->city,
                'state' => $candidate->state,
                'postal_code' => $candidate->postal_code,
                'country' => $candidate->country,
                'source' => $candidate->source,
                'notes' => $candidate->notes,
                'tags' => $candidate->tags,
                'work_authorization' => $candidate->work_authorization,
                'background_check' => $candidate->background_check,
                'drug_screen' => $candidate->drug_screen,
                'vaccination' => $candidate->vaccination,
                'last_applied_at' => $candidate->last_applied_at?->toIso8601String(),
                'resume_path' => $candidate->resume_path,
                'created_at' => $candidate->created_at?->toIso8601String(),
                'updated_at' => $candidate->updated_at?->toIso8601String(),
            ],
            'compliance' => [
                'status_counts' => $statusCounts,
                'readiness' => [
                    'status' => $status,
                    'reason_type' => $reasonType,
                    'reason' => $reason,
                ],
            ],
            'credentials' => $credentials,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:50'],
        ]);

        $email = strtolower(trim($validated['email']));

        $exists = Candidate::query()
            ->where('tenant_id', $orgId)
            ->where('email', $email)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Candidate with this email already exists.',
            ], 409);
        }

        $fullName = trim($validated['first_name'] . ' ' . $validated['last_name']);

        $tags = [];
        if (array_key_exists('tags', $validated) && is_array($validated['tags'])) {
            $tags = array_values(array_unique(array_filter($validated['tags'], fn ($t) => $t !== null && trim((string) $t) !== '')));
        }

        $candidate = Candidate::create([
            'tenant_id' => $orgId,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => $fullName,
            'email' => $email,
            'phone' => $validated['phone'] ?? null,
            'specialty' => $validated['specialty'] ?? null,
            'tags' => $tags,
        ]);

        return response()->api([
            'id' => $candidate->id,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $candidate = Candidate::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($id);

        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'string', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'specialty' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:50'],
        ]);

        if (array_key_exists('email', $validated)) {
            $validated['email'] = strtolower(trim((string) $validated['email']));
        }

        if (array_key_exists('first_name', $validated) || array_key_exists('last_name', $validated)) {
            $first = array_key_exists('first_name', $validated) ? (string) $validated['first_name'] : (string) $candidate->first_name;
            $last = array_key_exists('last_name', $validated) ? (string) $validated['last_name'] : (string) $candidate->last_name;
            $validated['name'] = trim($first . ' ' . $last);
        }

        if (array_key_exists('tags', $validated) && is_array($validated['tags'])) {
            $validated['tags'] = array_values(array_unique(array_filter($validated['tags'], fn ($t) => $t !== null && trim((string) $t) !== '')));
        }

        $candidate->update($validated);

        return response()->api($candidate);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $candidate = Candidate::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($id);

        $linkedUserId = (int) ($candidate->user_id ?? 0);

        try {
            DB::transaction(function () use ($candidate, $orgId, $linkedUserId): void {
                $candidate->delete();

                if ($linkedUserId <= 0) {
                    return;
                }

                $linkedUser = User::query()
                    ->where('organization_id', $orgId)
                    ->whereKey($linkedUserId)
                    ->where('role', 'candidate')
                    ->first();

                if (!$linkedUser) {
                    return;
                }

                // Remove restrictive user-linked rows before deleting the account.
                DB::table('messages')
                    ->where('user_id', $linkedUser->id)
                    ->orWhere('recipient_id', $linkedUser->id)
                    ->delete();

                DB::table('notifications')
                    ->where('user_id', $linkedUser->id)
                    ->delete();

                DB::table('personal_access_tokens')
                    ->where('tokenable_type', User::class)
                    ->where('tokenable_id', $linkedUser->id)
                    ->delete();

                $linkedUser->delete();
            });
        } catch (QueryException $e) {
            Log::error('Failed deleting candidate and linked account', [
                'candidate_id' => $candidate->id,
                'linked_user_id' => $linkedUserId > 0 ? $linkedUserId : null,
                'organization_id' => $orgId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to fully delete this candidate due to related records.',
            ], 422);
        }

        return response()->api([
            'deleted' => true,
        ]);
    }

    public function documents(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $candidate = Candidate::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($id);

        $docs = Document::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $orgId)
            ->where('candidate_id', $candidate->id)
            ->with(['credential:id,credential_type,issue_date,expiry_date,email'])
            ->orderByDesc('created_at')
            ->limit(300)
            ->get()
            ->map(function (Document $d) {
                $url = $d->file_path
                    ? Storage::disk(config('filesystems.uploads_disk', config('filesystems.default')))->url($d->file_path)
                    : null;

                return [
                    'id' => $d->id,
                    'candidate_id' => $d->candidate_id,
                    'credential_id' => $d->credential_id,
                    'type' => $d->type,
                    'name' => $d->name,
                    'file_path' => $d->file_path,
                    'url' => $url,
                    'review_status' => $d->review_status,
                    'rejection_reason' => $d->rejection_reason,
                    'reviewed_by_user_id' => $d->reviewed_by_user_id,
                    'reviewed_at' => $d->reviewed_at?->toIso8601String(),
                    'created_at' => $d->created_at?->toIso8601String(),
                    'meta' => $d->meta,
                    'credential' => $d->credential ? [
                        'id' => $d->credential->id,
                        'credential_type' => $d->credential->credential_type,
                        'issue_date' => $d->credential->issue_date?->format('Y-m-d'),
                        'expiry_date' => $d->credential->expiry_date?->format('Y-m-d'),
                        'email' => $d->credential->email,
                    ] : null,
                ];
            })
            ->values();

        return response()->api([
            'candidate' => [
                'id' => $candidate->id,
                'name' => $candidate->name ?: trim(($candidate->first_name ?? '') . ' ' . ($candidate->last_name ?? '')),
                'email' => $candidate->email,
            ],
            'documents' => $docs,
        ]);
    }

    public function credentials(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $candidate = Candidate::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($id);

        $rows = CandidateCredential::query()
            ->where('tenant_id', $orgId)
            ->where('candidate_id', $candidate->id)
            ->with([
                'credentialType:id,name,category',
                'latestRejectedVerification:id,credential_id,notes,status,created_at',
                'latestReviewFeedback:id,credential_id,notes,status,created_at',
            ])
            ->orderByDesc('created_at')
            ->limit(300)
            ->get();

        $service = app(CredentialService::class);

        $items = $rows
            ->map(function (CandidateCredential $cred) use ($service) {
                return [
                    'id' => $cred->id,
                    'candidate_id' => $cred->candidate_id,
                    'credential_type' => $cred->credentialType ? [
                        'id' => $cred->credentialType->id,
                        'name' => $cred->credentialType->name,
                        'category' => $cred->credentialType->category,
                    ] : null,
                    'issued_at' => $cred->issued_at?->toIso8601String(),
                    'expires_at' => $cred->expires_at?->toIso8601String(),
                    'status' => $cred->status,
                    'latest_rejection_reason' => in_array((string) $cred->status, ['rejected', 'needs_correction'], true)
                        ? ($cred->latestReviewFeedback?->notes ?? $cred->latestRejectedVerification?->notes)
                        : null,
                    'document_path' => $cred->document_path,
                    'preview_url' => $service->signedDocumentUrl($cred),
                    'verified_at' => $cred->verified_at?->toIso8601String(),
                    'created_at' => $cred->created_at?->toIso8601String(),
                ];
            })
            ->values();

        return response()->api([
            'candidate' => [
                'id' => $candidate->id,
                'name' => $candidate->name ?: trim(($candidate->first_name ?? '') . ' ' . ($candidate->last_name ?? '')),
                'email' => $candidate->email,
            ],
            'credentials' => $items,
        ]);
    }

    public function export(Request $request)
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $candidates = Candidate::query()
            ->where('tenant_id', $orgId)
            ->orderBy('name')
            ->get();

        if (strtolower((string) $request->query('format')) === 'csv') {
            $fileName = 'candidates-export-' . date('Y-m-d') . '.csv';

            return response()->stream(function () use ($candidates) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['First Name', 'Last Name', 'Email', 'Phone', 'Specialty', 'License Type', 'Years Experience', 'City', 'State', 'Source', 'Tags', 'Created At']);

                foreach ($candidates as $c) {
                    fputcsv($out, [
                        $c->first_name,
                        $c->last_name,
                        $c->email,
                        $c->phone,
                        $c->specialty,
                        $c->license_type,
                        $c->years_experience,
                        $c->city,
                        $c->state,
                        $c->source,
                        is_array($c->tags) ? implode(', ', $c->tags) : '',
                        $c->created_at?->format('Y-m-d H:i:s'),
                    ]);
                }

                fclose($out);
            }, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                'Cache-Control' => 'no-store, no-cache',
            ]);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $headers = ['First Name', 'Last Name', 'Email', 'Phone', 'Specialty', 'License Type', 'Years Experience', 'City', 'State', 'Source', 'Tags', 'Created At'];
        foreach ($headers as $i => $header) {
            $sheet->setCellValueByColumnAndRow($i + 1, 1, $header);
        }

        // Fill data
        $rowIdx = 2;
        foreach ($candidates as $c) {
            $data = [
                $c->first_name,
                $c->last_name,
                $c->email,
                $c->phone,
                $c->specialty,
                $c->license_type,
                $c->years_experience,
                $c->city,
                $c->state,
                $c->source,
                is_array($c->tags) ? implode(', ', $c->tags) : '',
                $c->created_at?->format('Y-m-d H:i:s'),
            ];
            
            foreach ($data as $colIdx => $value) {
                $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowIdx, $value);
            }
            $rowIdx++;
        }

        $writer = new Xlsx($spreadsheet);
        
        $fileName = 'candidates-export-' . date('Y-m-d') . '.xlsx';

        return response()->stream(
            function () use ($writer) {
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }
}
