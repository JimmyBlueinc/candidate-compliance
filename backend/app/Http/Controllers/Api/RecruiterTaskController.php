<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Notification;
use App\Models\RecruiterTask;
use App\Models\User;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecruiterTaskController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $mine = $request->boolean('mine', true);
        $baseQuery = RecruiterTask::query()->where('tenant_id', $orgId);
        if ($mine) {
            $baseQuery->where('assigned_to_user_id', (int) $request->user()->id);
        }

        $now = now();
        $todayEnd = now()->endOfDay();
        $sevenDaysAgo = now()->subDays(7);

        $openCount = (clone $baseQuery)->whereIn('status', ['open', 'in_progress'])->count();
        $overdueCount = (clone $baseQuery)
            ->whereIn('status', ['open', 'in_progress'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', $now)
            ->count();
        $dueTodayCount = (clone $baseQuery)
            ->whereIn('status', ['open', 'in_progress'])
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [$now, $todayEnd])
            ->count();
        $completedLast7d = (clone $baseQuery)
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $sevenDaysAgo)
            ->count();

        return response()->api([
            'open' => $openCount,
            'overdue' => $overdueCount,
            'due_today' => $dueTodayCount,
            'completed_last_7d' => $completedLast7d,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $validated = $request->validate([
            'status' => ['sometimes', 'nullable', 'string', 'in:open,in_progress,completed,cancelled'],
            'mine' => ['sometimes', 'boolean'],
            'candidate_id' => ['sometimes', 'nullable', 'integer'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:200'],
        ]);

        $query = RecruiterTask::query()
            ->where('tenant_id', $orgId)
            ->with([
                'candidate:id,name,first_name,last_name,email,specialty',
                'assignee:id,name,role',
                'assigner:id,name,role',
            ]);

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['candidate_id'])) {
            $query->where('candidate_id', (int) $validated['candidate_id']);
        }

        if (($validated['mine'] ?? false) === true) {
            $query->where('assigned_to_user_id', (int) $request->user()->id);
        }

        $perPage = (int) ($validated['per_page'] ?? 50);

        $rows = $query
            ->orderByRaw("CASE WHEN status IN ('open','in_progress') THEN 0 ELSE 1 END")
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->orderBy('due_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->api($rows->items(), 200, [
            'current_page' => $rows->currentPage(),
            'last_page' => $rows->lastPage(),
            'per_page' => $rows->perPage(),
            'total' => $rows->total(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $validated = $request->validate([
            'candidate_id' => ['sometimes', 'nullable', 'integer'],
            'assigned_to_user_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high,urgent'],
            'recurrence' => ['sometimes', 'string', 'in:none,daily,weekly,monthly'],
            'recurrence_interval' => ['sometimes', 'integer', 'min:1', 'max:52'],
            'due_at' => ['sometimes', 'nullable', 'date'],
            'remind_at' => ['sometimes', 'nullable', 'date'],
        ]);

        if (!empty($validated['candidate_id'])) {
            Candidate::query()
                ->where('tenant_id', $orgId)
                ->findOrFail((int) $validated['candidate_id']);
        }

        $assignee = User::query()
            ->where('organization_id', $orgId)
            ->where('id', (int) $validated['assigned_to_user_id'])
            ->firstOrFail();

        $task = RecruiterTask::query()->create([
            'tenant_id' => $orgId,
            'candidate_id' => $validated['candidate_id'] ?? null,
            'assigned_by_user_id' => (int) $request->user()->id,
            'assigned_to_user_id' => (int) $assignee->id,
            'title' => trim((string) $validated['title']),
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'] ?? 'medium',
            'status' => 'open',
            'recurrence' => $validated['recurrence'] ?? 'none',
            'recurrence_interval' => (int) ($validated['recurrence_interval'] ?? 1),
            'due_at' => $validated['due_at'] ?? null,
            'remind_at' => $validated['remind_at'] ?? null,
        ]);

        if ((int) $assignee->id !== (int) $request->user()->id) {
            Notification::query()->create([
                'tenant_id' => $orgId,
                'user_id' => (int) $assignee->id,
                'type' => 'task',
                'entity_type' => 'recruiter_task',
                'entity_id' => (int) $task->id,
                'data' => [
                    'message' => 'A recruiter task has been assigned to you.',
                    'title' => $task->title,
                    'priority' => $task->priority,
                    'due_at' => optional($task->due_at)->toIso8601String(),
                ],
                'created_at' => now(),
            ]);
        }

        return response()->api($task->load(['candidate:id,name,first_name,last_name,email,specialty', 'assignee:id,name,role', 'assigner:id,name,role']), 201, [], 'Task created.');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $task = RecruiterTask::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($id);

        $validated = $request->validate([
            'assigned_to_user_id' => ['sometimes', 'integer'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high,urgent'],
            'status' => ['sometimes', 'string', 'in:open,in_progress,completed,cancelled'],
            'recurrence' => ['sometimes', 'string', 'in:none,daily,weekly,monthly'],
            'recurrence_interval' => ['sometimes', 'integer', 'min:1', 'max:52'],
            'due_at' => ['sometimes', 'nullable', 'date'],
            'remind_at' => ['sometimes', 'nullable', 'date'],
        ]);

        if (array_key_exists('assigned_to_user_id', $validated)) {
            User::query()
                ->where('organization_id', $orgId)
                ->where('id', (int) $validated['assigned_to_user_id'])
                ->firstOrFail();
        }

        $previousStatus = (string) $task->status;
        $task->fill($validated);
        if (($validated['status'] ?? null) === 'completed' && !$task->completed_at) {
            $task->completed_at = now();
        }
        if (($validated['status'] ?? null) !== 'completed') {
            $task->completed_at = null;
        }
        $task->save();

        if ($previousStatus !== 'completed' && (string) $task->status === 'completed' && (string) $task->recurrence !== 'none') {
            $this->spawnRecurringFollowUp($task);
        }

        return response()->api($task->load(['candidate:id,name,first_name,last_name,email,specialty', 'assignee:id,name,role', 'assigner:id,name,role']), 200, [], 'Task updated.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $task = RecruiterTask::query()
            ->where('tenant_id', $orgId)
            ->findOrFail($id);

        $task->delete();

        return response()->api(['deleted' => true], 200, [], 'Task deleted.');
    }

    private function spawnRecurringFollowUp(RecruiterTask $task): void
    {
        $interval = max(1, (int) ($task->recurrence_interval ?? 1));
        $nextDueAt = $task->due_at ? $task->due_at->copy() : now();

        $nextDueAt = match ((string) $task->recurrence) {
            'daily' => $nextDueAt->addDays($interval),
            'weekly' => $nextDueAt->addWeeks($interval),
            'monthly' => $nextDueAt->addMonths($interval),
            default => $nextDueAt,
        };

        $next = RecruiterTask::query()->create([
            'tenant_id' => (int) $task->tenant_id,
            'candidate_id' => $task->candidate_id,
            'assigned_by_user_id' => (int) $task->assigned_by_user_id,
            'assigned_to_user_id' => (int) $task->assigned_to_user_id,
            'title' => (string) $task->title,
            'description' => $task->description,
            'priority' => (string) $task->priority,
            'status' => 'open',
            'recurrence' => (string) $task->recurrence,
            'recurrence_interval' => $interval,
            'due_at' => $nextDueAt,
            'remind_at' => null,
            'completed_at' => null,
        ]);

        Notification::query()->create([
            'tenant_id' => (int) $next->tenant_id,
            'user_id' => (int) $next->assigned_to_user_id,
            'type' => 'task',
            'entity_type' => 'recruiter_task',
            'entity_id' => (int) $next->id,
            'data' => [
                'message' => 'A recurring recruiter task has been generated.',
                'title' => $next->title,
                'priority' => $next->priority,
                'due_at' => optional($next->due_at)->toIso8601String(),
            ],
            'created_at' => now(),
        ]);
    }
}

