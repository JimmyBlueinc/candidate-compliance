<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemMessageController extends Controller
{
    public function banner(Request $request): JsonResponse
    {
        $now = now();

        $msg = SystemMessage::query()
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderByDesc('id')
            ->first();

        if (!$msg || !$msg->message) {
            return response()->api(null);
        }

        return response()->api([
            'id' => $msg->id,
            'message' => $msg->message,
            'starts_at' => $msg->starts_at?->toIso8601String(),
            'ends_at' => $msg->ends_at?->toIso8601String(),
        ]);
    }

    public function upsert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
        ]);

        $msg = SystemMessage::create([
            'message' => $validated['message'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'created_by_user_id' => $request->user()?->id,
        ]);

        return response()->api([
            'id' => $msg->id,
        ], 201);
    }

    public function clear(Request $request): JsonResponse
    {
        SystemMessage::query()->where('is_active', true)->update(['is_active' => false]);

        return response()->api(null, 200, [], 'Cleared.');
    }
}
