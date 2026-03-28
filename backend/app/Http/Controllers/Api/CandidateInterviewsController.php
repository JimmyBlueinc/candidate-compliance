<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateInterview;
use App\Models\Notification;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CandidateInterviewsController extends Controller
{
    public function index(Request $request, int $candidateId): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $candidate = Candidate::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($candidateId);

        $rows = CandidateInterview::query()
            ->where('tenant_id', $orgId)
            ->where('candidate_id', $candidate->id)
            ->with('scheduler:id,name,role')
            ->orderBy('starts_at')
            ->limit(200)
            ->get();

        return response()->api($rows);
    }

    public function store(Request $request, int $candidateId): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $candidate = Candidate::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($candidateId);

        $validated = $request->validate([
            'stage' => ['required', 'string', 'max:80'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_link' => ['nullable', 'url', 'max:2000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $row = CandidateInterview::query()->create([
            'tenant_id' => $orgId,
            'candidate_id' => $candidate->id,
            'scheduled_by_user_id' => (int) $request->user()->id,
            'stage' => trim((string) $validated['stage']),
            'location' => $validated['location'] ?? null,
            'meeting_link' => $validated['meeting_link'] ?? null,
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'] ?? null,
            'status' => 'scheduled',
            'notes' => $validated['notes'] ?? null,
        ]);

        if (!empty($candidate->user_id)) {
            Notification::query()->create([
                'tenant_id' => $orgId,
                'user_id' => (int) $candidate->user_id,
                'type' => 'interview',
                'entity_type' => 'candidate_interview',
                'entity_id' => (int) $row->id,
                'data' => [
                    'message' => 'A new interview has been scheduled for you.',
                    'stage' => $row->stage,
                    'starts_at' => optional($row->starts_at)->toIso8601String(),
                ],
                'created_at' => now(),
            ]);
        }

        return response()->api($row->load('scheduler:id,name,role'), 201, [], 'Interview scheduled.');
    }

    public function update(Request $request, int $interviewId): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $row = CandidateInterview::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($interviewId);

        $validated = $request->validate([
            'stage' => ['sometimes', 'string', 'max:80'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'meeting_link' => ['sometimes', 'nullable', 'url', 'max:2000'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'string', 'in:scheduled,completed,cancelled,no_show'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:4000'],
        ]);

        $row->fill($validated);
        $row->save();

        return response()->api($row->load('scheduler:id,name,role'), 200, [], 'Interview updated.');
    }

    public function destroy(Request $request, int $interviewId): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $row = CandidateInterview::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($interviewId);

        $row->delete();

        return response()->api(['deleted' => true], 200, [], 'Interview deleted.');
    }
}

