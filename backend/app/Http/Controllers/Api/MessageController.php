<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageCreated;
use App\Http\Controllers\Controller;
use App\Models\JobOrder;
use App\Models\Message;
use App\Models\Placement;
use App\Models\Submission;
use App\Models\User;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * Get messages for a specific context.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $validated = $request->validate([
            'job_order_id' => ['nullable', 'integer', 'exists:job_orders,id'],
            'submission_id' => ['nullable', 'integer', 'exists:submissions,id'],
            'placement_id' => ['nullable', 'integer', 'exists:placements,id'],
            'recipient_id' => ['nullable', 'integer', 'exists:users,id'],
            'since_id' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $query = Message::query()
            ->with(['user:id,name,role', 'recipient:id,name,role'])
            ->where('tenant_id', $orgId)
            ->select([
                'id',
                'tenant_id',
                'user_id',
                'facility_id',
                'job_order_id',
                'submission_id',
                'placement_id',
                'recipient_id',
                'body',
                'read_at',
                'created_at',
                'updated_at',
            ]);

        $hasRecipientContext = false;
        $recipientId = null;

        // Filter by context if provided
        if ($request->filled('job_order_id')) {
            $query->where('job_order_id', $request->job_order_id);
        } elseif ($request->filled('submission_id')) {
            $query->where('submission_id', $request->submission_id);
        } elseif ($request->filled('placement_id')) {
            $query->where('placement_id', $request->placement_id);
        } elseif ($request->filled('recipient_id')) {
            // Direct message conversation between current user and recipient
            $hasRecipientContext = true;
            $recipientId = (int) $request->recipient_id;

            $recipient = \App\Models\User::query()->find($recipientId);
            if (!$recipient || (int) $recipient->organization_id !== (int) $orgId) {
                return response()->json(['message' => 'Unauthorized. Recipient is outside your organization.'], 403);
            }

            $query->where(function ($q) use ($user, $recipientId) {
                $q->where(function ($sq) use ($user, $recipientId) {
                    $sq->where('user_id', $user->id)->where('recipient_id', $recipientId);
                })->orWhere(function ($sq) use ($user, $recipientId) {
                    $sq->where('user_id', $recipientId)->where('recipient_id', $user->id);
                });
            });
        } else {
            // If no context, return messages where user is sender or recipient (conversations list)
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('recipient_id', $user->id);
            });
        }

        $sinceId = (int) ($validated['since_id'] ?? 0);
        if ($sinceId > 0) {
            $query->where('id', '>', $sinceId);
        }

        $limit = (int) ($validated['limit'] ?? 100);

        // Fetch newest first for DB efficiency, then reverse for chat chronology.
        $messages = $query
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        if ($hasRecipientContext && $recipientId && $messages->isNotEmpty()) {
            Message::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $orgId)
                ->where('user_id', $recipientId)
                ->where('recipient_id', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return response()->api($messages);
    }

    /**
     * Store a new message.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'job_order_id' => ['nullable', 'integer', 'exists:job_orders,id'],
            'submission_id' => ['nullable', 'integer', 'exists:submissions,id'],
            'placement_id' => ['nullable', 'integer', 'exists:placements,id'],
            'recipient_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        // Ensure at least one context or recipient is provided
        $contextFields = ['job_order_id', 'submission_id', 'placement_id', 'recipient_id'];
        $providedFields = array_intersect(array_keys(array_filter($validated)), $contextFields);
        
        if (count($providedFields) < 1) {
            return response()->json(['message' => 'Either a context (job, submission, placement) or a recipient_id must be provided.'], 422);
        }

        $field = reset($providedFields);
        $value = $validated[$field];

        if ($field === 'recipient_id') {
            $recipient = User::query()->find($value);
            if (!$recipient) {
                return response()->json(['message' => 'Recipient not found.'], 404);
            }

            if ((int) $recipient->organization_id !== (int) $orgId) {
                return response()->json(['message' => 'Unauthorized. Recipient is outside your organization.'], 403);
            }

            if ((int) $recipient->id === (int) $user->id) {
                return response()->json(['message' => 'You cannot message yourself.'], 422);
            }

            $staffRoles = ['platform_admin', 'org_super_admin', 'admin', 'recruiter', 'compliance', 'scheduler', 'finance', 'logistics'];
            $isStaff = in_array((string) ($user->role ?? ''), $staffRoles, true);
            $isCandidate = (string) ($user->role ?? '') === 'candidate';

            if ($user->facility_id) {
                return response()->json(['message' => 'Unauthorized. Facility users cannot send direct messages.'], 403);
            }

            if ($isStaff) {
                $allowedStaffRecipientRoles = ['org_super_admin', 'admin', 'recruiter', 'compliance', 'scheduler', 'finance', 'logistics', 'candidate'];
                if (!in_array((string) ($recipient->role ?? ''), $allowedStaffRecipientRoles, true)) {
                    return response()->json(['message' => 'Unauthorized. Staff can only message users within organization operations roles or candidates.'], 403);
                }
            } elseif ($isCandidate) {
                $candidateAllowedStaffRoles = ['org_super_admin', 'admin', 'recruiter', 'compliance', 'scheduler', 'finance', 'logistics', 'platform_admin'];
                if (!in_array((string) ($recipient->role ?? ''), $candidateAllowedStaffRoles, true)) {
                    return response()->json(['message' => 'Unauthorized. Candidates can only message staff.'], 403);
                }
            } else {
                return response()->json(['message' => 'Unauthorized. Direct messaging not allowed for this role.'], 403);
            }
        }

        // Scoping / Validation for contexts
        if ($user->facility_id) {
            if ($field === 'job_order_id') {
                $job = JobOrder::where('id', $value)->where('tenant_id', $orgId)->first();
                if (!$job || $job->facility_id != $user->facility_id) {
                    return response()->json(['message' => 'Unauthorized. Job does not belong to your facility.'], 403);
                }
            } elseif ($field === 'submission_id') {
                $submission = Submission::with('jobOrder')->where('id', $value)->where('tenant_id', $orgId)->first();
                if (!$submission || $submission->jobOrder->facility_id != $user->facility_id) {
                    return response()->json(['message' => 'Unauthorized. Submission does not belong to your facility.'], 403);
                }
            } elseif ($field === 'placement_id') {
                $placement = Placement::where('id', $value)->where('tenant_id', $orgId)->first();
                if (!$placement || ($placement->facility_id && $placement->facility_id != $user->facility_id)) {
                    // Fallback to check via job order
                    $job = JobOrder::find($placement->job_order_id);
                    if (!$job || $job->facility_id != $user->facility_id) {
                        return response()->json(['message' => 'Unauthorized. Placement does not belong to your facility.'], 403);
                    }
                }
            }
        }

        $messageData = [
            'tenant_id' => $orgId,
            'user_id' => $user->id,
            'facility_id' => $user->facility_id,
            'body' => $validated['body'],
            'created_at' => now(),
        ];

        foreach ($contextFields as $f) {
            if (isset($validated[$f])) {
                $messageData[$f] = $validated[$f];
            }
        }

        $message = Message::create($messageData);

        if (class_exists('App\Events\MessageCreated')) {
            MessageCreated::dispatch($message, $orgId, $user);
        }

        return response()->api($message->load(['user:id,name,role', 'recipient:id,name,role']), 201, [], 'Message sent successfully.');
    }
}
