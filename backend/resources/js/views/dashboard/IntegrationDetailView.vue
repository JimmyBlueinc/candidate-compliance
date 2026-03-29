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
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to load integration.';
    const code = Number(e?.response?.status || 0);
    loadNotFound.value = code === 404 || code === 422;
  } finally {
    loading.value = false;
  }
}

async function save() {
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

reload();
</script>
