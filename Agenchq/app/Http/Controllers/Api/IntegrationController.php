<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IntegrationConnection;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class IntegrationController extends Controller
{
    private const SUPPORTED = [
        'google_drive' => [
            'label' => 'Google Drive',
            'description' => 'Store and sync candidate and compliance files.',
            'auth_method' => 'oauth2',
            'category' => 'storage',
            'docs_url' => 'https://developers.google.com/drive',
            'required_scopes' => ['drive.file'],
            'settings_schema' => [
                ['key' => 'folder_id', 'label' => 'Default Folder ID', 'type' => 'text', 'required' => false],
                ['key' => 'sync_direction', 'label' => 'Sync Direction', 'type' => 'select', 'required' => true, 'options' => ['push', 'pull', 'bidirectional']],
            ],
            'credentials_schema' => [
                ['key' => 'client_id', 'label' => 'Google Client ID', 'type' => 'text', 'required' => true],
                ['key' => 'client_secret', 'label' => 'Google Client Secret', 'type' => 'password', 'required' => true],
            ],
        ],
        'google_calendar' => [
            'label' => 'Google Calendar',
            'description' => 'Sync interviews, orientation, and shift-related events.',
            'auth_method' => 'oauth2',
            'category' => 'scheduling',
            'docs_url' => 'https://developers.google.com/calendar',
            'required_scopes' => ['calendar.events'],
            'settings_schema' => [
                ['key' => 'calendar_id', 'label' => 'Calendar ID', 'type' => 'text', 'required' => false],
                ['key' => 'auto_create_events', 'label' => 'Auto-create events', 'type' => 'boolean', 'required' => false],
            ],
            'credentials_schema' => [
                ['key' => 'client_id', 'label' => 'Google Client ID', 'type' => 'text', 'required' => true],
                ['key' => 'client_secret', 'label' => 'Google Client Secret', 'type' => 'password', 'required' => true],
            ],
        ],
        'slack' => [
            'label' => 'Slack',
            'description' => 'Send recruiting, compliance, and operations alerts.',
            'auth_method' => 'oauth2_or_webhook',
            'category' => 'communication',
            'docs_url' => 'https://api.slack.com',
            'required_scopes' => ['chat:write', 'channels:read'],
            'settings_schema' => [
                ['key' => 'default_channel', 'label' => 'Default Channel', 'type' => 'text', 'required' => false],
                ['key' => 'notify_on_new_applications', 'label' => 'Notify on new applications', 'type' => 'boolean', 'required' => false],
            ],
            'credentials_schema' => [
                ['key' => 'bot_token', 'label' => 'Bot Token', 'type' => 'password', 'required' => false],
                ['key' => 'webhook_url', 'label' => 'Incoming Webhook URL', 'type' => 'text', 'required' => false],
            ],
        ],
        'dropbox' => [
            'label' => 'Dropbox',
            'description' => 'Archive and exchange credential and onboarding packets.',
            'auth_method' => 'oauth2',
            'category' => 'storage',
            'docs_url' => 'https://developers.dropbox.com',
            'required_scopes' => ['files.content.write'],
            'settings_schema' => [
                ['key' => 'root_path', 'label' => 'Root Path', 'type' => 'text', 'required' => false],
            ],
            'credentials_schema' => [
                ['key' => 'client_id', 'label' => 'Dropbox App Key', 'type' => 'text', 'required' => true],
                ['key' => 'client_secret', 'label' => 'Dropbox App Secret', 'type' => 'password', 'required' => true],
            ],
        ],
        'quickbooks' => [
            'label' => 'QuickBooks',
            'description' => 'Sync invoices, payment statuses, and accounting mapping.',
            'auth_method' => 'oauth2',
            'category' => 'finance',
            'docs_url' => 'https://developer.intuit.com',
            'required_scopes' => ['com.intuit.quickbooks.accounting'],
            'settings_schema' => [
                ['key' => 'realm_id', 'label' => 'Realm ID', 'type' => 'text', 'required' => false],
                ['key' => 'auto_push_invoices', 'label' => 'Auto-push invoices', 'type' => 'boolean', 'required' => false],
            ],
            'credentials_schema' => [
                ['key' => 'client_id', 'label' => 'QuickBooks Client ID', 'type' => 'text', 'required' => true],
                ['key' => 'client_secret', 'label' => 'QuickBooks Client Secret', 'type' => 'password', 'required' => true],
            ],
        ],
        'zapier' => [
            'label' => 'Zapier',
            'description' => 'Trigger no-code automations from staffing lifecycle events.',
            'auth_method' => 'api_key_or_webhook',
            'category' => 'automation',
            'docs_url' => 'https://platform.zapier.com',
            'required_scopes' => [],
            'settings_schema' => [
                ['key' => 'event_namespace', 'label' => 'Event Namespace', 'type' => 'text', 'required' => false],
                ['key' => 'emit_candidate_events', 'label' => 'Emit candidate events', 'type' => 'boolean', 'required' => false],
            ],
            'credentials_schema' => [
                ['key' => 'api_key', 'label' => 'Zapier API Key', 'type' => 'password', 'required' => false],
                ['key' => 'webhook_url', 'label' => 'Zapier Hook URL', 'type' => 'text', 'required' => false],
            ],
        ],
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
        foreach (self::SUPPORTED as $key => $meta) {
            /** @var IntegrationConnection|null $row */
            $row = $rows->get($key);
            $items[] = $this->serializeIntegration($key, $meta, $row);
        }

        return response()->api(['integrations' => $items]);
    }

    public function show(Request $request, string $key): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $meta = self::SUPPORTED[$key] ?? null;
        if (!$meta) {
            return response()->json(['message' => 'Unsupported integration key.'], 422);
        }

        $row = IntegrationConnection::query()
            ->where('organization_id', $orgId)
            ->where('key', $key)
            ->first();

        return response()->api([
            'integration' => $this->serializeIntegration($key, $meta, $row),
        ]);
    }

    public function upsert(Request $request, string $key): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $meta = self::SUPPORTED[$key] ?? null;
        if (!$meta) {
            return response()->json(['message' => 'Unsupported integration key.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
            'status' => 'sometimes|string|in:disconnected,connected,error',
            'settings' => 'sometimes|array',
            'credentials' => 'sometimes|array',
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
        $nextSettings = array_key_exists('settings', $payload)
            ? $this->sanitizeBySchema($payload['settings'] ?? [], $meta['settings_schema'] ?? [])
            : ($row->settings ?? []);
        $nextCredentials = array_key_exists('credentials', $payload)
            ? $this->sanitizeBySchema($payload['credentials'] ?? [], $meta['credentials_schema'] ?? [])
            : ($row->credentials ?? []);

        if ($enabled) {
            $missing = $this->requiredCredentialErrors($key, $meta, $nextCredentials);
            if (!empty($missing)) {
                return response()->json([
                    'message' => 'Missing required integration credentials.',
                    'errors' => [
                        'credentials' => $missing,
                    ],
                ], 422);
            }
        }

        $row->settings = $nextSettings;
        $row->credentials = $nextCredentials;
        $row->last_error = $payload['last_error'] ?? null;
        $row->connected_at = $enabled && !$row->connected_at ? now() : ($enabled ? $row->connected_at : null);
        $row->save();

        return response()->api([
            'integration' => $this->serializeIntegration($row->key, $meta, $row),
        ], 200, [], 'Integration updated.');
    }

    public function test(Request $request, string $key): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $meta = self::SUPPORTED[$key] ?? null;
        if (!$meta) {
            return response()->json(['message' => 'Unsupported integration key.'], 422);
        }

        $row = IntegrationConnection::query()->firstOrNew([
            'organization_id' => $orgId,
            'key' => $key,
        ]);

        $settings = $row->settings ?? [];
        $credentials = $row->credentials ?? [];

        $missing = [];
        foreach (($meta['credentials_schema'] ?? []) as $field) {
            if (!($field['required'] ?? false)) {
                continue;
            }
            $value = Arr::get($credentials, (string) ($field['key'] ?? ''));
            if ($value === null || $value === '') {
                $missing[] = (string) ($field['label'] ?? $field['key'] ?? 'credential');
            }
        }

        if ($key === 'slack' && empty($credentials['bot_token']) && empty($credentials['webhook_url'])) {
            $missing[] = 'Bot Token or Incoming Webhook URL';
        }

        if ($key === 'zapier' && empty($credentials['api_key']) && empty($credentials['webhook_url'])) {
            $missing[] = 'API Key or Zapier Hook URL';
        }

        if (!empty($credentials['webhook_url']) && !filter_var($credentials['webhook_url'], FILTER_VALIDATE_URL)) {
            $missing[] = 'Webhook URL must be a valid URL';
        }

        if (!empty($missing)) {
            $row->status = 'error';
            $row->last_error = 'Missing or invalid integration configuration.';
            $row->save();

            return response()->json([
                'message' => 'Integration test failed.',
                'errors' => [
                    'configuration' => $missing,
                ],
            ], 422);
        }

        $row->status = $row->enabled ? 'connected' : 'disconnected';
        $row->last_error = null;
        $row->last_synced_at = now();
        $row->save();

        return response()->api([
            'result' => [
                'ok' => true,
                'checked_at' => now()->toIso8601String(),
                'message' => 'Configuration is valid and ready for connection.',
                'settings' => $settings,
            ],
            'integration' => $this->serializeIntegration($key, $meta, $row),
        ], 200, [], 'Integration test completed.');
    }

    public function reconnect(Request $request, string $key): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $meta = self::SUPPORTED[$key] ?? null;
        if (!$meta) {
            return response()->json(['message' => 'Unsupported integration key.'], 422);
        }

        $row = IntegrationConnection::query()->firstOrNew([
            'organization_id' => $orgId,
            'key' => $key,
        ]);

        $credentials = $row->credentials ?? [];
        $missing = $this->requiredCredentialErrors($key, $meta, $credentials);
        if (!empty($missing)) {
            $row->status = 'error';
            $row->last_error = 'Missing or invalid integration configuration.';
            $row->save();

            return response()->json([
                'message' => 'Reconnect failed.',
                'errors' => [
                    'credentials' => $missing,
                ],
            ], 422);
        }

        $row->enabled = true;
        $row->status = 'connected';
        $row->last_error = null;
        $row->connected_at = $row->connected_at ?: now();
        $row->last_synced_at = now();
        $row->save();

        return response()->api([
            'integration' => $this->serializeIntegration($key, $meta, $row),
        ], 200, [], 'Integration reconnected.');
    }

    public function disable(Request $request, string $key): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $meta = self::SUPPORTED[$key] ?? null;
        if (!$meta) {
            return response()->json(['message' => 'Unsupported integration key.'], 422);
        }

        $row = IntegrationConnection::query()->firstOrNew([
            'organization_id' => $orgId,
            'key' => $key,
        ]);

        $row->enabled = false;
        $row->status = 'disconnected';
        $row->last_error = null;
        $row->save();

        return response()->api([
            'integration' => $this->serializeIntegration($key, $meta, $row),
        ], 200, [], 'Integration disabled.');
    }

    private function sanitizeBySchema(array $incoming, array $schema): array
    {
        $allowed = [];
        foreach ($schema as $field) {
            $key = (string) ($field['key'] ?? '');
            if ($key === '') {
                continue;
            }
            if (array_key_exists($key, $incoming)) {
                $allowed[$key] = $incoming[$key];
            }
        }

        return $allowed;
    }

    private function requiredCredentialErrors(string $key, array $meta, array $credentials): array
    {
        $missing = [];
        foreach (($meta['credentials_schema'] ?? []) as $field) {
            if (!($field['required'] ?? false)) {
                continue;
            }
            $value = Arr::get($credentials, (string) ($field['key'] ?? ''));
            if ($value === null || $value === '') {
                $missing[] = (string) ($field['label'] ?? $field['key'] ?? 'credential');
            }
        }

        if ($key === 'slack' && empty($credentials['bot_token']) && empty($credentials['webhook_url'])) {
            $missing[] = 'Bot Token or Incoming Webhook URL';
        }

        if ($key === 'zapier' && empty($credentials['api_key']) && empty($credentials['webhook_url'])) {
            $missing[] = 'API Key or Zapier Hook URL';
        }

        if (!empty($credentials['webhook_url']) && !filter_var($credentials['webhook_url'], FILTER_VALIDATE_URL)) {
            $missing[] = 'Webhook URL must be a valid URL';
        }

        return array_values(array_unique($missing));
    }

    private function serializeIntegration(string $key, array $meta, ?IntegrationConnection $row): array
    {
        $credentials = $row?->credentials ?? [];
        $credentialPresence = [];
        foreach (($meta['credentials_schema'] ?? []) as $field) {
            $fieldKey = (string) ($field['key'] ?? '');
            if ($fieldKey === '') {
                continue;
            }
            $credentialPresence[$fieldKey] = !empty($credentials[$fieldKey]);
        }

        return [
            'key' => $key,
            'label' => $meta['label'] ?? $key,
            'description' => $meta['description'] ?? '',
            'auth_method' => $meta['auth_method'] ?? 'api_key',
            'category' => $meta['category'] ?? 'other',
            'docs_url' => $meta['docs_url'] ?? null,
            'required_scopes' => $meta['required_scopes'] ?? [],
            'settings_schema' => $meta['settings_schema'] ?? [],
            'credentials_schema' => $meta['credentials_schema'] ?? [],
            'enabled' => (bool) ($row?->enabled ?? false),
            'status' => $row?->status ?? 'disconnected',
            'settings' => $row?->settings ?? [],
            'credential_presence' => $credentialPresence,
            'connected_at' => $row?->connected_at?->toIso8601String(),
            'last_synced_at' => $row?->last_synced_at?->toIso8601String(),
            'last_error' => $row?->last_error,
        ];
    }
}

