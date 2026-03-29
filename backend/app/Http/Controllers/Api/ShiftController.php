<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Candidate;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\ShiftRequest;
use App\Models\ShiftTemplate;
use App\Support\Org;
use App\Services\AvailabilityIndexService;
use App\Services\AvailabilityService;
use App\Services\NotificationService;
use App\Services\ShiftAssignmentService;
use App\Services\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Carbon\Carbon;

class ShiftController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        try {
            $query = Shift::query()
                ->with([
                    'facility:id,name',
                    'template:id,name,role,facility_id',
                    'assignment:id,tenant_id,candidate_id,facility_name',
                    'assignment.candidate:id,user_id,name,specialty',
                    'shiftAssignments:id,tenant_id,shift_id,candidate_id,status,approved_at',
                    'shiftAssignments.candidate:id,user_id,name,specialty',
                    'requests:id,tenant_id,shift_id,candidate_id,status,requested_at',
                ])
                ->where('tenant_id', $orgId);

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->filled('facility_id')) {
                $query->where('facility_id', (int) $request->input('facility_id'));
            }

            if ($request->filled('assignment_id')) {
                $query->where('assignment_id', (int) $request->input('assignment_id'));
            }

            $rows = $query->orderByDesc('starts_at')->limit(500)->get();
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Shifts are not available yet. Please ensure database migrations have been applied.',
            ], 500);
        }

        $data = $rows->map(function (Shift $shift) {
            $tz = (string) ($shift->timezone ?: $shift->template?->timezone ?: 'UTC');
            $starts = $shift->starts_at ? Carbon::parse($shift->starts_at)->timezone($tz) : null;
            $ends = $shift->ends_at ? Carbon::parse($shift->ends_at)->timezone($tz) : null;

            $pendingRequest = $shift->requests
                ? $shift->requests->firstWhere('status', 'pending')
                : null;

            $approvedAssignment = $shift->shiftAssignments
                ? $shift->shiftAssignments
                    ->whereIn('status', ['approved', 'completed'])
                    ->sortByDesc('approved_at')
                    ->first()
                : null;

            $candidate = $approvedAssignment?->candidate ?: $shift->assignment?->candidate;

            return [
                'id' => $shift->id,
                'facility' => (string) ($shift->facility?->name ?? $shift->assignment?->facility_name ?? ''),
                'date' => $starts?->format('Y-m-d'),
                'start_time' => $starts?->format('H:i'),
                'end_time' => $ends?->format('H:i'),
                'status' => (string) $shift->status,
                'request_id' => $pendingRequest?->id,
                'assigned_candidate' => $candidate ? [
                    'id' => (int) $candidate->id,
                    'user_id' => (int) ($candidate->user_id ?? 0),
                    'name' => (string) ($candidate->name ?? 'Candidate'),
                    'specialty' => (string) ($candidate->specialty ?? ''),
                ] : null,
            ];
        })->values();

        return response()->api($data);
    }

    public function templates(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $templates = ShiftTemplate::query()
            ->where('tenant_id', $orgId)
            ->where('active', true)
            ->orderBy('name')
            ->get([
                'id',
                'tenant_id',
                'facility_id',
                'name',
                'role',
                'start_time',
                'end_time',
                'break_minutes',
                'timezone',
            ]);

        return response()->api($templates);
    }

    public function activeAssignments(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $assignments = Assignment::query()
            ->with([
                'candidate:id,name,first_name,last_name,email',
                'facility:id,name',
            ])
            ->where('tenant_id', $orgId)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        $data = $assignments->map(function (Assignment $a) {
            return [
                'id' => $a->id,
                'candidate_id' => $a->candidate_id,
                'candidate' => $a->candidate ? [
                    'id' => $a->candidate->id,
                    'name' => $a->candidate->name,
                    'first_name' => $a->candidate->first_name,
                    'last_name' => $a->candidate->last_name,
                    'email' => $a->candidate->email,
                ] : null,
                'facility_id' => $a->facility_id,
                'facility' => $a->facility ? [
                    'id' => $a->facility->id,
                    'name' => $a->facility->name,
                ] : null,
                'facility_name' => $a->facility_name,
                'status' => (string) $a->status,
                'start_date' => $a->start_date?->format('Y-m-d'),
                'end_date' => $a->end_date?->format('Y-m-d'),
            ];
        });

        return response()->api($data);
    }

    public function availabilityPreview(Request $request, AvailabilityService $availability): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $validated = $request->validate([
            'assignment_id' => ['required', 'integer'],
            'shift_template_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
        ]);

        $assignment = Assignment::query()
            ->where('tenant_id', $orgId)
            ->find((int) $validated['assignment_id']);

        if (!$assignment) {
            return response()->json(['message' => 'Assignment not found.'], 422);
        }

        $template = ShiftTemplate::query()
            ->where('tenant_id', $orgId)
            ->find((int) $validated['shift_template_id']);

        if (!$template) {
            return response()->json(['message' => 'Shift template not found.'], 422);
        }

        $tz = $template->timezone ?: 'UTC';
        $startsAt = Carbon::parse((string) $validated['date'] . ' ' . $template->start_time, $tz)->utc();
        $endsAt = Carbon::parse((string) $validated['date'] . ' ' . $template->end_time, $tz)->utc();
        if ($endsAt->lte($startsAt)) {
            $endsAt = $endsAt->addDay();
        }

        $candidateId = (int) ($assignment->candidate_id ?? 0);
        if ($candidateId <= 0) {
            return response()->api([
                'available' => true,
                'conflicts' => [],
            ]);
        }

        $result = $availability->evaluateWindow($orgId, $candidateId, $startsAt, $endsAt);

        return response()->api([
            'available' => (string) ($result['status'] ?? '') === 'available',
            'status' => (string) ($result['status'] ?? 'no_declared'),
            'hard_block' => (bool) ($result['hard_block'] ?? false),
        ]);
    }

    public function availabilityPreviewShift(Request $request, AvailabilityService $availability): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $validated = $request->validate([
            'shift_id' => ['required', 'integer'],
        ]);

        $shift = Shift::query()
            ->with(['assignment:id,candidate_id'])
            ->where('tenant_id', $orgId)
            ->find((int) $validated['shift_id']);

        if (!$shift) {
            return response()->json(['message' => 'Shift not found.'], 422);
        }

        $candidateId = (int) ($shift->assignment?->candidate_id ?? 0);
        if ($candidateId <= 0) {
            return response()->api([
                'available' => true,
                'status' => 'no_candidate',
                'hard_block' => false,
            ]);
        }

        $result = $availability->evaluateWindow($orgId, $candidateId, $shift->starts_at, $shift->ends_at);

        return response()->api([
            'available' => (string) ($result['status'] ?? '') === 'available',
            'status' => (string) ($result['status'] ?? 'no_declared'),
            'hard_block' => (bool) ($result['hard_block'] ?? false),
        ]);
    }

    public function markCandidateUnavailable(Request $request, AvailabilityIndexService $service): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $validated = $request->validate([
            'candidate_id' => ['required', 'integer'],
            'start' => ['required', 'date'],
            'end' => ['required', 'date'],
        ]);

        $candidate = Candidate::query()
            ->where('tenant_id', $orgId)
            ->find((int) $validated['candidate_id']);

        if (!$candidate) {
            return response()->json(['message' => 'Candidate not found.'], 422);
        }

        $service->markCandidateUnavailable(
            candidateId: (int) $candidate->id,
            start: (string) $validated['start'],
            end: (string) $validated['end'],
        );

        return response()->api(['ok' => true]);
    }

    public function available(Request $request): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $candidate = Candidate::query()
            ->where('tenant_id', $orgId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->first();

        if (!$candidate) {
            return response()->json(['message' => 'Candidate profile not found.'], 404);
        }

        try {
            $query = Shift::query()
                ->with([
                    'facility:id,name',
                    'template:id,name,role,facility_id,timezone',
                ])
                ->where('tenant_id', $orgId)
                ->whereIn('status', ['open', 'assigned', 'in_progress']);

            if ($candidate->specialty) {
                $query->whereHas('template', function ($q) use ($candidate) {
                    $q->where('role', (string) $candidate->specialty);
                });
            }

            $rows = $query->orderBy('starts_at')->limit(500)->get();
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Shifts are not available yet. Please ensure database migrations have been applied.',
            ], 500);
        }

        $shiftIds = $rows->pluck('id')->all();

        $requests = ShiftRequest::query()
            ->where('tenant_id', $orgId)
            ->whereIn('shift_id', $shiftIds)
            ->where('candidate_id', $candidate->id)
            ->get()
            ->keyBy('shift_id');

        $assignments = ShiftAssignment::query()
            ->where('tenant_id', $orgId)
            ->whereIn('shift_id', $shiftIds)
            ->where('candidate_id', $candidate->id)
            ->orderByDesc('approved_at')
            ->get()
            ->groupBy('shift_id');

        $data = $rows->map(function (Shift $shift) use ($requests, $assignments) {
            $tz = (string) ($shift->timezone ?: $shift->template?->timezone ?: 'UTC');
            $starts = $shift->starts_at ? Carbon::parse($shift->starts_at)->timezone($tz) : null;
            $ends = $shift->ends_at ? Carbon::parse($shift->ends_at)->timezone($tz) : null;

            $request = $requests->get($shift->id);
            $myAssignments = $assignments->get($shift->id);
            $myAssignment = $myAssignments ? $myAssignments->first() : null;

            $status = (string) $shift->status;
            if ($myAssignment && in_array((string) $myAssignment->status, ['approved', 'completed'], true) && (string) $shift->status !== 'cancelled') {
                $status = 'assigned';
            } elseif ($request && (string) $request->status === 'pending') {
                $status = 'requested';
            } elseif (in_array((string) $shift->status, ['open', 'assigned', 'in_progress'], true)) {
                $status = 'open';
            }

            return [
                'id' => $shift->id,
                'facility' => (string) ($shift->facility?->name ?? ''),
                'date' => $starts?->format('Y-m-d'),
                'start_time' => $starts?->format('H:i'),
                'end_time' => $ends?->format('H:i'),
                'notes' => (string) ($shift->title ?? ''),
                'status' => $status,
                'request_id' => $request?->id,
                'checked_in_at' => $myAssignment?->checked_in_at?->toIso8601String(),
                'checked_out_at' => $myAssignment?->checked_out_at?->toIso8601String(),
            ];
        })->values();

        return response()->api($data);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $shift = Shift::query()
            ->with([
                'facility:id,name',
                'template:id,name,role,facility_id',
                'requests',
                'shiftAssignments',
            ])
            ->where('tenant_id', $orgId)
            ->findOrFail($id);

        return response()->api($shift);
    }

    public function store(Request $request, ShiftService $service): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $validated = $request->validate([
            'shift_template_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'assignment_id' => ['required', 'integer'],
            'facility_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $shift = $service->createShiftFromTemplate(
            tenantId: $orgId,
            shiftTemplateId: (int) $validated['shift_template_id'],
            date: (string) $validated['date'],
            assignmentId: (int) $validated['assignment_id'],
            facilityId: array_key_exists('facility_id', $validated) ? ($validated['facility_id'] !== null ? (int) $validated['facility_id'] : null) : null,
            actor: $request->user()
        );

        return response()->api($shift, 201);
    }

    public function requestShift(Request $request, int $id, ShiftService $service): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $candidate = Candidate::query()
            ->where('tenant_id', $orgId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->first();

        if (!$candidate) {
            return response()->json(['message' => 'Candidate profile not found.'], 404);
        }

        $validated = $request->validate([
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        $req = $service->requestShift($orgId, $id, (int) $candidate->id, $validated['notes'] ?? null, $user);

        return response()->api($req, 201);
    }

    public function withdrawRequest(Request $request, int $id, ShiftService $service): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $candidate = Candidate::query()
            ->where('tenant_id', $orgId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->first();

        if (!$candidate) {
            return response()->json(['message' => 'Candidate profile not found.'], 404);
        }

        $req = $service->withdrawRequest($orgId, $id, (int) $candidate->id, $user);

        return response()->api($req);
    }

    public function approveRequest(Request $request, int $requestId, ShiftAssignmentService $service): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $assignment = $service->approveRequest($orgId, $requestId, $request->user());

        $shiftRequest = ShiftRequest::query()
            ->with(['candidate:id,user_id,name,email'])
            ->where('tenant_id', $orgId)
            ->find($requestId);

        if ($shiftRequest) {
            $this->notifyShiftRequestDecision($shiftRequest, (int) $orgId, 'approved', null);
        }

        return response()->api($assignment);
    }

    public function rejectRequest(Request $request, int $requestId, ShiftAssignmentService $service): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $req = $service->rejectRequest($orgId, $requestId, (string) $validated['reason'], $request->user());
        $req->loadMissing(['candidate:id,user_id,name,email']);
        $this->notifyShiftRequestDecision($req, (int) $orgId, 'rejected', (string) $validated['reason']);

        return response()->api($req);
    }

    private function notifyShiftRequestDecision(ShiftRequest $req, int $tenantId, string $decision, ?string $reason): void
    {
        $candidateUserId = (int) ($req->candidate?->user_id ?? 0);
        $candidateName = (string) ($req->candidate?->name ?? 'Candidate');

        if ($candidateUserId > 0) {
            $this->notificationService->notify(
                [$candidateUserId],
                $decision === 'approved' ? 'shift_request_approved' : 'shift_request_rejected',
                'shift_request',
                (int) $req->id,
                [
                    'candidate_name' => $candidateName,
                    'shift_id' => (int) $req->shift_id,
                    'status' => $decision,
                    'reason' => $reason,
                ],
                $tenantId
            );
        }

        $this->notificationService->notifyAdmins(
            $tenantId,
            $decision === 'approved' ? 'shift_request_approved' : 'shift_request_rejected',
            'shift_request',
            (int) $req->id,
            [
                'candidate_name' => $candidateName,
                'shift_id' => (int) $req->shift_id,
                'status' => $decision,
                'reason' => $reason,
            ]
        );
    }

    public function checkIn(Request $request, int $id, ShiftService $service): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $candidate = Candidate::query()
            ->where('tenant_id', $orgId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->first();

        if (!$candidate) {
            return response()->json(['message' => 'Candidate profile not found.'], 404);
        }

        $assignment = $service->checkIn($orgId, $id, (int) $candidate->id, $user);

        return response()->api($assignment);
    }

    public function checkOut(Request $request, int $id, ShiftService $service): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $candidate = Candidate::query()
            ->where('tenant_id', $orgId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->first();

        if (!$candidate) {
            return response()->json(['message' => 'Candidate profile not found.'], 404);
        }

        $assignment = $service->checkOut($orgId, $id, (int) $candidate->id, $user);

        return response()->api($assignment);
    }

    public function cancel(Request $request, int $id, ShiftService $service): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $shift = $service->cancelShift($orgId, $id, $request->user());

        return response()->api($shift);
    }

    public function complete(Request $request, int $id, ShiftService $service): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $result = $service->completeShift($orgId, $id, $request->user());

        return response()->api($result);
    }
}
