<template>
  <div class="space-y-6">
    <AppPageHeader
      :title="integration?.label || 'Integration Setup'"
      :subtitle="integration?.description || 'Configure credentials, sync behavior, and connection health.'"
      :breadcrumb="{ label: 'Integrations', to: { name: 'dashboard.integrations' } }"
    >
      <template #actions>
        <AppButton variant="secondary" size="sm" @click="reload" :loading="loading">Refresh</AppButton>
        <AppButton size="sm" @click="runTest" :loading="testing">Test Connection</AppButton>
      </template>
    </AppPageHeader>

    <div v-if="status" class="px-4 py-3 rounded-[var(--radius-lg)] bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm">
      {{ status }}
    </div>
    <div v-if="error" class="px-4 py-3 rounded-[var(--radius-lg)] bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm">
      {{ error }}
    </div>
    <div v-if="usingSettingsFallback" class="px-4 py-3 rounded-[var(--radius-lg)] bg-amber-500/10 border border-amber-500/20 text-amber-300 text-sm">
      Compatibility mode is active. Credentials and settings are saved from this UI for this organization.
    </div>
    <div v-if="loadNotFound" class="px-4 py-3 rounded-[var(--radius-lg)] bg-amber-500/10 border border-amber-500/20 text-amber-300 text-sm flex items-center justify-between gap-3">
      <span>This integration cannot be loaded right now. Please return to the integrations catalog.</span>
      <AppButton variant="secondary" size="sm" @click="goBack">Back to Integrations</AppButton>
    </div>

    <div v-if="integration" class="grid grid-cols-1 xl:grid-cols-3 gap-5">
      <AppCard title="Connection Status" subtitle="Current health and sync information.">
        <div class="space-y-3 text-sm">
          <div class="flex items-center justify-between">
            <span class="text-[color:var(--aq-muted)]">Status</span>
            <span class="rounded-full px-2 py-0.5 text-xs font-semibold"
              :class="integration.status === 'connected' ? 'bg-emerald-500/15 text-emerald-300' : integration.status === 'error' ? 'bg-rose-500/15 text-rose-300' : 'bg-[color:var(--aq-surface-2)] text-[color:var(--aq-muted)]'">
              {{ integration.status }}
            </span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[color:var(--aq-muted)]">Enabled</span>
            <input type="checkbox" v-model="form.enabled" />
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[color:var(--aq-muted)]">Connected at</span>
            <span>{{ formatDate(integration.connected_at) }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[color:var(--aq-muted)]">Last sync</span>
            <span>{{ formatDate(integration.last_synced_at) }}</span>
          </div>
          <div v-if="integration.last_error" class="text-rose-300 text-xs">
            {{ integration.last_error }}
          </div>
        </div>
      </AppCard>

      <AppCard title="Setup Guidance" subtitle="Use org-level app credentials and environment-safe callback URLs.">
        <div class="space-y-2 text-sm text-[color:var(--aq-muted)]">
          <p><span class="font-semibold text-[color:var(--aq-fg)]">Auth Method:</span> {{ integration.auth_method }}</p>
          <p><span class="font-semibold text-[color:var(--aq-fg)]">Category:</span> {{ integration.category }}</p>
          <p><span class="font-semibold text-[color:var(--aq-fg)]">Scopes:</span> {{ integration.required_scopes?.length ? integration.required_scopes.join(', ') : 'Not required' }}</p>
          <a v-if="integration.docs_url" :href="integration.docs_url" target="_blank" rel="noreferrer" class="text-[color:var(--aq-primary)] hover:underline">
            Integration documentation
          </a>
        </div>
      </AppCard>

      <AppCard title="Actions" subtitle="Save changes, reconnect, or disable this integration.">
        <div class="space-y-2">
          <AppButton class="w-full" :loading="saving" @click="save">Save Configuration</AppButton>
          <AppButton variant="secondary" class="w-full" :loading="testing" @click="runTest">Validate Connection</AppButton>
          <AppButton variant="secondary" class="w-full" :loading="reconnecting" @click="reconnectConnection">Reconnect</AppButton>
          <AppButton variant="ghost" class="w-full" @click="disableConnection">Disable Integration</AppButton>
        </div>
      </AppCard>
    </div>

    <div v-if="integration" class="grid grid-cols-1 lg:grid-cols-2 gap-5">
      <AppCard title="Credentials" subtitle="Stored per organization. Secret values are masked after save.">
        <div class="space-y-3">
          <div v-for="field in integration.credentials_schema || []" :key="`cred-${field.key}`">
            <div class="mb-1 flex items-center justify-between">
              <label class="block text-xs font-semibold uppercase tracking-[0.1em] text-[color:var(--aq-muted)]">{{ field.label }}</label>
              <span
                v-if="integration?.credential_presence?.[field.key]"
                class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-emerald-300"
              >
                Configured
              </span>
            </div>
            <input
              v-model="form.credentials[field.key]"
              :type="field.type === 'password' ? 'password' : 'text'"
              :placeholder="integration?.credential_presence?.[field.key] ? 'Stored securely. Enter new value to rotate.' : (field.required ? 'Required' : 'Optional')"
              class="w-full rounded-lg border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] px-3 py-2 text-sm text-[color:var(--aq-fg)]"
            />
            <p v-if="field.help_text" class="mt-1 text-xs text-[color:var(--aq-muted)]">{{ field.help_text }}</p>
          </div>
        </div>
      </AppCard>

      <AppCard title="Sync & Behavior" subtitle="Control integration behavior and mapping preferences.">
        <div class="space-y-3">
          <div v-for="field in integration.settings_schema || []" :key="`setting-${field.key}`">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.1em] text-[color:var(--aq-muted)]">{{ field.label }}</label>
            <select
              v-if="field.type === 'select'"
              v-model="form.settings[field.key]"
              class="w-full rounded-lg border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] px-3 py-2 text-sm text-[color:var(--aq-fg)]"
            >
              <option v-for="opt in field.options || []" :key="opt" :value="opt">{{ opt }}</option>
            </select>
            <label v-else-if="field.type === 'boolean'" class="inline-flex items-center gap-2 text-sm text-[color:var(--aq-fg)]">
              <input type="checkbox" v-model="form.settings[field.key]" />
              Enable
            </label>
            <input
              v-else
              v-model="form.settings[field.key]"
              type="text"
              class="w-full rounded-lg border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] px-3 py-2 text-sm text-[color:var(--aq-fg)]"
            />
          </div>
        </div>
      </AppCard>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiGet, apiPost, apiPut } from '../../lib/api';
import AppPageHeader from '../../components/ui/AppPageHeader.vue';
import AppCard from '../../components/ui/AppCard.vue';
import AppButton from '../../components/ui/AppButton.vue';

const route = useRoute();
const router = useRouter();
const loading = ref(false);
const saving = ref(false);
const testing = ref(false);
const reconnecting = ref(false);
const status = ref('');
const error = ref('');
const integration = ref(null);
const loadNotFound = ref(false);
const usingSettingsFallback = ref(false);

const form = reactive({
  enabled: false,
  settings: {},
  credentials: {},
});

function hydrateForm(row) {
  form.enabled = row.enabled === true;
  form.settings = { ...(row.settings || {}) };
  form.credentials = {};
}

function integrationKey() {
  return String(route.params.key || '');
}

const FALLBACK_DEFS = {
  google_drive: {
    key: 'google_drive',
    label: 'Google Drive',
    description: 'Store and sync candidate and compliance files.',
    auth_method: 'oauth2',
    category: 'storage',
    docs_url: 'https://developers.google.com/drive',
    required_scopes: ['drive.file'],
    settings_schema: [
      { key: 'folder_id', label: 'Default Folder ID', type: 'text', required: false },
      { key: 'sync_direction', label: 'Sync Direction', type: 'select', required: true, options: ['push', 'pull', 'bidirectional'] },
    ],
    credentials_schema: [
      { key: 'client_id', label: 'Google Client ID', type: 'text', required: true },
      { key: 'client_secret', label: 'Google Client Secret', type: 'password', required: true },
    ],
  },
  google_calendar: {
    key: 'google_calendar',
    label: 'Google Calendar',
    description: 'Sync interviews, orientation, and shift-related events.',
    auth_method: 'oauth2',
    category: 'scheduling',
    docs_url: 'https://developers.google.com/calendar',
    required_scopes: ['calendar.events'],
    settings_schema: [
      { key: 'calendar_id', label: 'Calendar ID', type: 'text', required: false },
      { key: 'auto_create_events', label: 'Auto-create events', type: 'boolean', required: false },
    ],
    credentials_schema: [
      { key: 'client_id', label: 'Google Client ID', type: 'text', required: true },
      { key: 'client_secret', label: 'Google Client Secret', type: 'password', required: true },
    ],
  },
  slack: {
    key: 'slack',
    label: 'Slack',
    description: 'Send recruiting, compliance, and operations alerts.',
    auth_method: 'oauth2_or_webhook',
    category: 'communication',
    docs_url: 'https://api.slack.com',
    required_scopes: ['chat:write', 'channels:read'],
    settings_schema: [
      { key: 'default_channel', label: 'Default Channel', type: 'text', required: false },
      { key: 'notify_on_new_applications', label: 'Notify on new applications', type: 'boolean', required: false },
    ],
    credentials_schema: [
      { key: 'bot_token', label: 'Bot Token', type: 'password', required: false },
      { key: 'webhook_url', label: 'Incoming Webhook URL', type: 'text', required: false },
    ],
  },
  dropbox: {
    key: 'dropbox',
    label: 'Dropbox',
    description: 'Archive and exchange credential and onboarding packets.',
    auth_method: 'oauth2',
    category: 'storage',
    docs_url: 'https://developers.dropbox.com',
    required_scopes: ['files.content.write'],
    settings_schema: [{ key: 'root_path', label: 'Root Path', type: 'text', required: false }],
    credentials_schema: [
      { key: 'client_id', label: 'Dropbox App Key', type: 'text', required: true },
      { key: 'client_secret', label: 'Dropbox App Secret', type: 'password', required: true },
    ],
  },
  quickbooks: {
    key: 'quickbooks',
    label: 'QuickBooks',
    description: 'Sync invoices, payment statuses, and accounting mapping.',
    auth_method: 'oauth2',
    category: 'finance',
    docs_url: 'https://developer.intuit.com',
    required_scopes: ['com.intuit.quickbooks.accounting'],
    settings_schema: [
      { key: 'realm_id', label: 'Realm ID', type: 'text', required: false },
      { key: 'auto_push_invoices', label: 'Auto-push invoices', type: 'boolean', required: false },
    ],
    credentials_schema: [
      { key: 'client_id', label: 'QuickBooks Client ID', type: 'text', required: true },
      { key: 'client_secret', label: 'QuickBooks Client Secret', type: 'password', required: true },
    ],
  },
  zapier: {
    key: 'zapier',
    label: 'Zapier',
    description: 'Trigger no-code automations from staffing lifecycle events.',
    auth_method: 'api_key_or_webhook',
    category: 'automation',
    docs_url: 'https://platform.zapier.com',
    required_scopes: [],
    settings_schema: [
      { key: 'event_namespace', label: 'Event Namespace', type: 'text', required: false },
      { key: 'emit_candidate_events', label: 'Emit candidate events', type: 'boolean', required: false },
    ],
    credentials_schema: [
      { key: 'api_key', label: 'Zapier API Key', type: 'password', required: false },
      { key: 'webhook_url', label: 'Zapier Hook URL', type: 'text', required: false },
    ],
  },
};

function formatDate(value) {
  if (!value) return 'N/A';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return 'N/A';
  return d.toLocaleString();
}

async function reload() {
  loading.value = true;
  status.value = '';
  error.value = '';
  loadNotFound.value = false;
  try {
    const response = await apiGet(`/v1/integrations/${encodeURIComponent(integrationKey())}`);
    const row = response?.data?.integration || response?.integration || response;
    integration.value = row;
    hydrateForm(row);
    usingSettingsFallback.value = false;
  } catch (e) {
    const code = Number(e?.response?.status || 0);
    if (code === 404 || code === 422) {
      try {
        await loadFromSettingsFallback();
        usingSettingsFallback.value = true;
      } catch (fallbackError) {
        error.value = fallbackError?.response?.data?.message || fallbackError?.message || 'Failed to load integration.';
        loadNotFound.value = true;
      }
    } else {
      error.value = e?.response?.data?.message || e?.message || 'Failed to load integration.';
    }
  } finally {
    loading.value = false;
  }
}

async function save() {
  if (usingSettingsFallback.value) {
    await saveFallback();
    return;
  }
  saving.value = true;
  status.value = '';
  error.value = '';
  try {
    const credentialsPayload = {};
    for (const [key, value] of Object.entries(form.credentials || {})) {
      if (value !== null && value !== undefined && String(value).trim() !== '') {
        credentialsPayload[key] = value;
      }
    }
    const hasCredentialUpdates = Object.keys(credentialsPayload).length > 0;

    const response = await apiPut(`/v1/integrations/${encodeURIComponent(integrationKey())}`, {
      enabled: !!form.enabled,
      status: form.enabled ? 'connected' : 'disconnected',
      settings: form.settings,
      ...(hasCredentialUpdates ? { credentials: credentialsPayload } : {}),
    });
    integration.value = response?.data?.integration || response?.integration || integration.value;
    form.credentials = {};
    status.value = `${integration.value?.label || 'Integration'} settings saved.`;
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to save integration settings.';
  } finally {
    saving.value = false;
  }
}

async function runTest() {
  if (usingSettingsFallback.value) {
    await runTestFallback();
    return;
  }
  testing.value = true;
  status.value = '';
  error.value = '';
  try {
    await save();
    const response = await apiPost(`/v1/integrations/${encodeURIComponent(integrationKey())}/test`, {});
    const payload = response?.data?.result || response?.result || {};
    integration.value = response?.data?.integration || response?.integration || integration.value;
    status.value = payload?.message || 'Integration validation completed successfully.';
  } catch (e) {
    const details = e?.response?.data?.errors?.configuration;
    error.value = Array.isArray(details) && details.length
      ? details.join(', ')
      : (e?.response?.data?.message || e?.message || 'Integration validation failed.');
    await reload();
  } finally {
    testing.value = false;
  }
}

async function disableConnection() {
  if (usingSettingsFallback.value) {
    await disableFallback();
    return;
  }
  saving.value = true;
  status.value = '';
  error.value = '';
  try {
    const response = await apiPost(`/v1/integrations/${encodeURIComponent(integrationKey())}/disable`, {});
    integration.value = response?.data?.integration || response?.integration || integration.value;
    form.enabled = false;
    status.value = `${integration.value?.label || 'Integration'} disabled.`;
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to disable integration.';
  } finally {
    saving.value = false;
  }
}

async function reconnectConnection() {
  if (usingSettingsFallback.value) {
    await reconnectFallback();
    return;
  }
  reconnecting.value = true;
  status.value = '';
  error.value = '';
  try {
    await save();
    const response = await apiPost(`/v1/integrations/${encodeURIComponent(integrationKey())}/reconnect`, {});
    integration.value = response?.data?.integration || response?.integration || integration.value;
    form.enabled = true;
    status.value = `${integration.value?.label || 'Integration'} reconnected successfully.`;
  } catch (e) {
    const details = e?.response?.data?.errors?.credentials;
    error.value = Array.isArray(details) && details.length
      ? details.join(', ')
      : (e?.response?.data?.message || e?.message || 'Failed to reconnect integration.');
  } finally {
    reconnecting.value = false;
  }
}

function goBack() {
  router.push({ name: 'dashboard.integrations' });
}

function buildCredentialPresence(credentials = {}, schema = []) {
  const out = {};
  for (const field of schema) {
    const key = String(field?.key || '');
    if (!key) continue;
    out[key] = Boolean(credentials[key]);
  }
  return out;
}

function validateCredentialsForDef(def, credentials) {
  const missing = [];
  for (const field of def?.credentials_schema || []) {
    if (!field?.required) continue;
    const key = String(field?.key || '');
    const value = credentials?.[key];
    if (value === null || value === undefined || String(value).trim() === '') {
      missing.push(field?.label || key);
    }
  }
  if (def?.key === 'slack' && !credentials?.bot_token && !credentials?.webhook_url) {
    missing.push('Bot Token or Incoming Webhook URL');
  }
  if (def?.key === 'zapier' && !credentials?.api_key && !credentials?.webhook_url) {
    missing.push('API Key or Zapier Hook URL');
  }
  const webhookUrl = String(credentials?.webhook_url || '').trim();
  if (webhookUrl && !/^https?:\/\//i.test(webhookUrl)) {
    missing.push('Webhook URL must be a valid URL');
  }
  return Array.from(new Set(missing));
}

async function readModulePreferences() {
  const settingsRes = await apiGet('/v1/agency/settings');
  return settingsRes?.data?.settings?.module_preferences || settingsRes?.settings?.module_preferences || {};
}

async function writeModulePreferences(modulePreferences) {
  await apiPut('/v1/agency/settings', {
    module_preferences: modulePreferences,
  });
}

async function loadFromSettingsFallback() {
  const key = integrationKey();
  const def = FALLBACK_DEFS[key];
  if (!def) {
    throw new Error('Unsupported integration key.');
  }
  const modulePreferences = await readModulePreferences();
  const integrationsMap = modulePreferences?.integrations || {};
  const configsMap = modulePreferences?.integration_configs || {};
  const cfg = configsMap[key] || {};
  const credentials = cfg?.credentials || {};
  const enabled = cfg?.enabled ?? integrationsMap[key] === true;
  const row = {
    ...def,
    enabled: Boolean(enabled),
    status: cfg?.status || (enabled ? 'connected' : 'disconnected'),
    settings: cfg?.settings || {},
    credential_presence: buildCredentialPresence(credentials, def.credentials_schema),
    connected_at: cfg?.connected_at || null,
    last_synced_at: cfg?.last_synced_at || null,
    last_error: cfg?.last_error || null,
    _credentials_raw: credentials,
  };
  integration.value = row;
  hydrateForm(row);
}

async function saveFallback() {
  saving.value = true;
  status.value = '';
  error.value = '';
  try {
    const key = integrationKey();
    const def = FALLBACK_DEFS[key];
    const modulePreferences = await readModulePreferences();
    const integrationsMap = { ...(modulePreferences?.integrations || {}) };
    const configsMap = { ...(modulePreferences?.integration_configs || {}) };
    const currentCfg = configsMap[key] || {};
    const mergedCredentials = { ...(currentCfg?.credentials || {}) };
    for (const [k, v] of Object.entries(form.credentials || {})) {
      if (v !== null && v !== undefined && String(v).trim() !== '') {
        mergedCredentials[k] = v;
      }
    }
    const enabled = Boolean(form.enabled);
    const nextCfg = {
      ...currentCfg,
      enabled,
      status: enabled ? 'connected' : 'disconnected',
      settings: { ...(form.settings || {}) },
      credentials: mergedCredentials,
      connected_at: enabled ? (currentCfg?.connected_at || new Date().toISOString()) : null,
      last_error: null,
    };
    integrationsMap[key] = enabled;
    configsMap[key] = nextCfg;
    const nextModulePreferences = {
      ...(modulePreferences || {}),
      integrations: integrationsMap,
      integration_configs: configsMap,
    };
    await writeModulePreferences(nextModulePreferences);
    integration.value = {
      ...def,
      ...integration.value,
      enabled,
      status: nextCfg.status,
      settings: nextCfg.settings,
      credential_presence: buildCredentialPresence(mergedCredentials, def.credentials_schema),
      connected_at: nextCfg.connected_at,
      last_error: nextCfg.last_error,
      _credentials_raw: mergedCredentials,
    };
    form.credentials = {};
    status.value = `${integration.value?.label || 'Integration'} settings saved.`;
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to save integration settings.';
  } finally {
    saving.value = false;
  }
}

async function runTestFallback() {
  testing.value = true;
  status.value = '';
  error.value = '';
  try {
    await saveFallback();
    const def = FALLBACK_DEFS[integrationKey()];
    const creds = integration.value?._credentials_raw || {};
    const missing = validateCredentialsForDef(def, creds);
    if (missing.length) {
      error.value = missing.join(', ');
      integration.value = {
        ...integration.value,
        status: 'error',
        last_error: 'Missing or invalid integration configuration.',
      };
      return;
    }
    integration.value = {
      ...integration.value,
      status: integration.value?.enabled ? 'connected' : 'disconnected',
      last_synced_at: new Date().toISOString(),
      last_error: null,
    };
    status.value = 'Integration validation completed successfully.';
  } finally {
    testing.value = false;
  }
}

async function disableFallback() {
  form.enabled = false;
  await saveFallback();
  status.value = `${integration.value?.label || 'Integration'} disabled.`;
}

async function reconnectFallback() {
  reconnecting.value = true;
  status.value = '';
  error.value = '';
  try {
    await saveFallback();
    const def = FALLBACK_DEFS[integrationKey()];
    const creds = integration.value?._credentials_raw || {};
    const missing = validateCredentialsForDef(def, creds);
    if (missing.length) {
      error.value = missing.join(', ');
      return;
    }
    form.enabled = true;
    await saveFallback();
    integration.value = {
      ...integration.value,
      status: 'connected',
      last_synced_at: new Date().toISOString(),
      last_error: null,
    };
    status.value = `${integration.value?.label || 'Integration'} reconnected successfully.`;
  } finally {
    reconnecting.value = false;
  }
}

reload();
</script>
