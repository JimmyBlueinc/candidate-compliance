<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntakeTokenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $tokens = $user
            ? $user->tokens()
                ->where('name', 'intake')
                ->orderByDesc('created_at')
                ->limit(50)
                ->get()
                ->map(function ($t) {
                    return [
                        'id' => $t->id,
                        'name' => $t->name,
                        'last_used_at' => $t->last_used_at?->toIso8601String(),
                        'expires_at' => $t->expires_at?->toIso8601String(),
                        'created_at' => $t->created_at?->toIso8601String(),
                    ];
                })
                ->values()
            : collect();

        return response()->json([
            'data' => $tokens,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:50'],
            'expires_in_days' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:730'],
        ]);

        $name = 'intake';
        if (!empty($validated['name'])) {
            $name = 'intake';
        }

        $expiresAt = null;
        if (array_key_exists('expires_in_days', $validated) && $validated['expires_in_days']) {
            $expiresAt = now()->addDays((int) $validated['expires_in_days']);
        }

        $plainTextToken = $user->createToken($name, ['intake'], $expiresAt)->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $plainTextToken,
                'expires_at' => $expiresAt?->toIso8601String(),
            ],
        ], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $deleted = $user->tokens()->whereKey($id)->delete();

        return response()->json([
            'deleted' => (bool) $deleted,
        ]);
    }
}
