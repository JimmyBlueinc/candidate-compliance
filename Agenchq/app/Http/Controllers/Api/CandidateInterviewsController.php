<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateInterview;
use App\Models\Notification;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use App\Support\Org;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;

class CandidateInterviewsController extends Controller
{
    public function myInterviews(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || (string) ($user->role ?? '') !== 'candidate') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $orgId = (int) (Org::id($request) ?: $user->organization_id ?: 0);

        $candidate = Candidate::query()
            ->withoutGlobalScope(TenantScope::class)
            ->when($orgId > 0, fn ($q) => $q->where('tenant_id', $orgId))
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->orderByDesc('id')
            ->first();

        if (!$candidate) {
            return response()->api([]);
        }

        if (!Schema::hasTable('candidate_interviews')) {
            return response()->api([]);
        }

        $rows = CandidateInterview::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', (int) $candidate->tenant_id)
            ->where('candidate_id', (int) $candidate->id)
            ->with('scheduler:id,name,role')
            ->orderBy('starts_at')
            ->limit(200)
            ->get();

        return response()->api($rows);
    }

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
            'interviewer_user_ids' => ['sometimes', 'array'],
            'interviewer_user_ids.*' => ['integer'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $interviewerIds = $this->sanitizeInterviewerIds($orgId, $validated['interviewer_user_ids'] ?? []);
        $this->assertNoConflict(
            $orgId,
            (int) $candidate->id,
            (string) $validated['starts_at'],
            (string) ($validated['ends_at'] ?? $validated['starts_at']),
            $interviewerIds
        );

        $row = CandidateInterview::query()->create([
            'tenant_id' => $orgId,
            'candidate_id' => $candidate->id,
            'scheduled_by_user_id' => (int) $request->user()->id,
            'interviewer_user_ids' => $interviewerIds,
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
            'interviewer_user_ids' => ['sometimes', 'array'],
            'interviewer_user_ids.*' => ['integer'],
            'status' => ['sometimes', 'string', 'in:scheduled,completed,cancelled,no_show'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:4000'],
        ]);

        $nextStartsAt = (string) ($validated['starts_at'] ?? optional($row->starts_at)->toIso8601String() ?? now()->toIso8601String());
        $nextEndsAt = (string) ($validated['ends_at'] ?? optional($row->ends_at)->toIso8601String() ?? $nextStartsAt);
        $interviewerIds = $this->sanitizeInterviewerIds($orgId, $validated['interviewer_user_ids'] ?? ($row->interviewer_user_ids ?? []));
        $this->assertNoConflict(
            $orgId,
            (int) $row->candidate_id,
            $nextStartsAt,
            $nextEndsAt,
            $interviewerIds,
            (int) $row->id
        );

        $payload = $validated;
        $payload['interviewer_user_ids'] = $interviewerIds;
        $row->fill($payload);
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

    public function calendarLinks(Request $request, int $interviewId): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $row = CandidateInterview::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($interviewId);

        $title = trim('Interview: ' . (string) $row->stage);
        $details = trim((string) ($row->notes ?? 'Candidate interview'));
        $location = trim((string) ($row->location ?? $row->meeting_link ?? ''));
        $start = optional($row->starts_at)->format('Ymd\THis\Z');
        $end = optional($row->ends_at ?? $row->starts_at?->copy()->addHour())->format('Ymd\THis\Z');

        $googleUrl = 'https://calendar.google.com/calendar/render?action=TEMPLATE'
            . '&text=' . rawurlencode($title)
            . '&details=' . rawurlencode($details)
            . '&location=' . rawurlencode($location)
            . '&dates=' . rawurlencode($start . '/' . $end);

        $outlookUrl = 'https://outlook.live.com/calendar/0/deeplink/compose?path=%2Fcalendar%2Faction%2Fcompose'
            . '&subject=' . rawurlencode($title)
            . '&body=' . rawurlencode($details)
            . '&location=' . rawurlencode($location)
            . '&startdt=' . rawurlencode(optional($row->starts_at)->toIso8601String() ?? '')
            . '&enddt=' . rawurlencode(optional($row->ends_at ?? $row->starts_at?->copy()->addHour())->toIso8601String() ?? '');

        return response()->api([
            'google' => $googleUrl,
            'outlook' => $outlookUrl,
        ]);
    }

    public function downloadIcs(Request $request, int $interviewId)
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $row = CandidateInterview::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($interviewId);

        $start = optional($row->starts_at)->copy();
        $end = optional($row->ends_at)->copy() ?: optional($row->starts_at)->copy()?->addHour();
        $uid = sprintf('interview-%d@agenchq.com', (int) $row->id);
        $summary = str_replace(["\r", "\n"], ' ', 'Interview: ' . (string) $row->stage);
        $description = str_replace(["\r", "\n"], '\\n', (string) ($row->notes ?? 'Candidate interview'));
        $location = str_replace(["\r", "\n"], ' ', (string) ($row->location ?? $row->meeting_link ?? ''));

        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Agenchq//Interview Scheduler//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:' . ($start ? $start->utc()->format('Ymd\THis\Z') : now()->utc()->format('Ymd\THis\Z')),
            'DTEND:' . ($end ? $end->utc()->format('Ymd\THis\Z') : now()->utc()->addHour()->format('Ymd\THis\Z')),
            'SUMMARY:' . $summary,
            'DESCRIPTION:' . $description,
            'LOCATION:' . $location,
            'END:VEVENT',
            'END:VCALENDAR',
            '',
        ]);

        $filename = 'interview-' . (int) $row->id . '.ics';
        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function sanitizeInterviewerIds(int $orgId, array $incoming): array
    {
        $ids = collect($incoming)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (count($ids) === 0) {
            return [];
        }

        $validIds = User::query()
            ->where('organization_id', $orgId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return $validIds;
    }

    private function assertNoConflict(
        int $orgId,
        int $candidateId,
        string $startsAt,
        string $endsAt,
        array $interviewerIds,
        ?int $ignoreInterviewId = null
    ): void {
        $start = Carbon::parse($startsAt);
        $end = Carbon::parse($endsAt);
        if ($end->lessThanOrEqualTo($start)) {
            $end = $start->copy()->addHour();
        }

        $base = CandidateInterview::query()
            ->where('tenant_id', $orgId)
            ->where('status', 'scheduled')
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($w) use ($start, $end) {
                    $w->whereNotNull('ends_at')
                        ->where('starts_at', '<', $end)
                        ->where('ends_at', '>', $start);
                })->orWhere(function ($w) use ($start, $end) {
                    $w->whereNull('ends_at')
                        ->where('starts_at', '>=', $start)
                        ->where('starts_at', '<', $end);
                });
            });

        if ($ignoreInterviewId) {
            $base->where('id', '!=', $ignoreInterviewId);
        }

        $candidateConflict = (clone $base)
            ->where('candidate_id', $candidateId)
            ->exists();

        if ($candidateConflict) {
            throw ValidationException::withMessages([
                'starts_at' => ['Candidate already has an overlapping scheduled interview.'],
            ]);
        }

        if (count($interviewerIds) > 0) {
            $panelConflict = (clone $base)->where(function ($q) use ($interviewerIds) {
                foreach ($interviewerIds as $userId) {
                    $q->orWhereJsonContains('interviewer_user_ids', $userId);
                }
            })->exists();

            if ($panelConflict) {
                throw ValidationException::withMessages([
                    'interviewer_user_ids' => ['One or more selected interviewers have a scheduling conflict.'],
                ]);
            }
        }
    }
}

