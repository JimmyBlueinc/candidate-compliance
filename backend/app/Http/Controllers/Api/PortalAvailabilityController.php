<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateAvailability;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalAvailabilityController extends Controller
{
    private function getCandidate(Request $request, int $orgId): ?Candidate
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        return Candidate::query()
            ->where('tenant_id', $orgId)
            ->where('user_id', $user->id)
            ->first();
    }

    public function index(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $candidate = $this->getCandidate($request, $orgId);
        if (!$candidate) {
            return response()->json(['message' => 'Candidate profile not found.'], 404);
        }

        $windows = CandidateAvailability::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $orgId)
            ->where('candidate_id', $candidate->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return response()->api(['windows' => $windows]);
    }

    public function store(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $candidate = $this->getCandidate($request, $orgId);
        if (!$candidate) {
            return response()->json(['message' => 'Candidate profile not found.'], 404);
        }

        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', 'min:1', 'max:7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'is_available' => ['required', 'boolean'],
        ]);

        $window = CandidateAvailability::create([
            'tenant_id' => $orgId,
            'candidate_id' => $candidate->id,
            'day_of_week' => (int) $validated['day_of_week'],
            'start_time' => $validated['start_time'] . ':00',
            'end_time' => $validated['end_time'] . ':00',
            'is_available' => (bool) $validated['is_available'],
        ]);

        return response()->api(['window' => $window], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $candidate = $this->getCandidate($request, $orgId);
        if (!$candidate) {
            return response()->json(['message' => 'Candidate profile not found.'], 404);
        }

        $window = CandidateAvailability::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $orgId)
            ->where('candidate_id', $candidate->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'day_of_week' => ['sometimes', 'integer', 'min:1', 'max:7'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i'],
            'is_available' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('start_time', $validated)) {
            $window->start_time = $validated['start_time'] . ':00';
        }
        if (array_key_exists('end_time', $validated)) {
            $window->end_time = $validated['end_time'] . ':00';
        }
        if (array_key_exists('day_of_week', $validated)) {
            $window->day_of_week = (int) $validated['day_of_week'];
        }
        if (array_key_exists('is_available', $validated)) {
            $window->is_available = (bool) $validated['is_available'];
        }

        if ($window->end_time <= $window->start_time) {
            return response()->json(['message' => 'End time must be after start time.'], 422);
        }

        $window->save();

        return response()->api(['window' => $window]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $candidate = $this->getCandidate($request, $orgId);
        if (!$candidate) {
            return response()->json(['message' => 'Candidate profile not found.'], 404);
        }

        $window = CandidateAvailability::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $orgId)
            ->where('candidate_id', $candidate->id)
            ->where('id', $id)
            ->firstOrFail();

        $window->delete();

        return response()->api(['ok' => true]);
    }
}
