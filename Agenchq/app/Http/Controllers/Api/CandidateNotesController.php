<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateNote;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CandidateNotesController extends Controller
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

        $notes = CandidateNote::query()
            ->where('tenant_id', $orgId)
            ->where('candidate_id', $candidate->id)
            ->with('author:id,name,role')
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        return response()->api($notes);
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
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $note = CandidateNote::query()->create([
            'tenant_id' => $orgId,
            'candidate_id' => $candidate->id,
            'created_by_user_id' => (int) $request->user()->id,
            'body' => trim((string) $validated['body']),
        ]);

        return response()->api($note->load('author:id,name,role'), 201, [], 'Note saved.');
    }

    public function destroy(Request $request, int $noteId): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $note = CandidateNote::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($noteId);

        $user = $request->user();
        $canDeleteAny = in_array((string) ($user->role ?? ''), ['platform_admin', 'org_super_admin', 'admin', 'recruiter'], true);
        if (!$canDeleteAny && (int) $note->created_by_user_id !== (int) $user->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $note->delete();

        return response()->api(['deleted' => true], 200, [], 'Note deleted.');
    }
}

