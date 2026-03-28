<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IntegrationConnection;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IntegrationController extends Controller
{
    private const SUPPORTED = [
        'google_drive' => 'Google Drive',
        'google_calendar' => 'Google Calendar',
        'slack' => 'Slack',
        'dropbox' => 'Dropbox',
        'quickbooks' => 'QuickBooks',
        'zapier' => 'Zapier',
    ];

    public function index(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $rows = IntegrationConnection::query()
            ->where('organization_id', $orgId)
            ->get()
            ->keyBy('key');

        $items = [];
        foreach (self::SUPPORTED as $key => $label) {
            /** @var IntegrationConnection|null $row */
            $row = $rows->get($key);
            $items[] = [
                'key' => $key,
                'label' => $label,
                'enabled' => (bool) ($row?->enabled ?? false),
                'status' => $row?->status ?? 'disconnected',
                'settings' => $row?->settings ?? [],
                'connected_at' => $row?->connected_at?->toIso8601String(),
                'last_synced_at' => $row?->last_synced_at?->toIso8601String(),
                'last_error' => $row?->last_error,
            ];
        }

        return response()->api(['integrations' => $items]);
    }

    public function upsert(Request $request, string $key): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        if (!array_key_exists($key, self::SUPPORTED)) {
            return response()->json(['message' => 'Unsupported integration key.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
            'status' => 'sometimes|string|in:disconnected,connected,error',
            'settings' => 'sometimes|array',
            'last_error' => 'sometimes|nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = $validator->validated();
        $enabled = (bool) ($payload['enabled'] ?? false);

        $row = IntegrationConnection::query()->firstOrNew([
            'organization_id' => $orgId,
            'key' => $key,
        ]);

        $status = $payload['status'] ?? ($enabled ? 'connected' : 'disconnected');
        $row->enabled = $enabled;
        $row->status = $status;
        if (array_key_exists('settings', $payload)) {
            $row->settings = $payload['settings'] ?? [];
        }
        $row->last_error = $payload['last_error'] ?? null;
        $row->connected_at = $enabled && !$row->connected_at ? now() : ($enabled ? $row->connected_at : null);
        $row->save();

        return response()->api([
            'integration' => [
                'key' => $row->key,
                'label' => self::SUPPORTED[$row->key] ?? $row->key,
                'enabled' => (bool) $row->enabled,
                'status' => $row->status,
                'settings' => $row->settings ?? [],
                'connected_at' => $row->connected_at?->toIso8601String(),
                'last_synced_at' => $row->last_synced_at?->toIso8601String(),
                'last_error' => $row->last_error,
            ],
        ], 200, [], 'Integration updated.');
    }
}

