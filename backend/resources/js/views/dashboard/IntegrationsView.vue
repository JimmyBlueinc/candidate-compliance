<template>
  <div class="space-y-6">
    <AppPageHeader
      title="Integrations"
      subtitle="Configure and monitor organization-wide integrations for storage, communication, scheduling, and finance."
    >
      <template #actions>
        <AppButton variant="secondary" size="sm" :loading="loading" @click="reload">
          <RefreshCw class="w-4 h-4" />
          Refresh
        </AppButton>
      </template>
    </AppPageHeader>

    <div v-if="status" class="px-4 py-3 rounded-[var(--radius-lg)] bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm">
      {{ status }}
    </div>
    <div v-if="error" class="px-4 py-3 rounded-[var(--radius-lg)] bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm">
      {{ error }}
    </div>
    <div v-if="!supportsIntegrationApi" class="px-4 py-3 rounded-[var(--radius-lg)] bg-amber-500/10 border border-amber-500/20 text-amber-300 text-sm">
      Running compatibility mode for integrations in this environment. You can still open Manage and configure per-organization credentials from the UI.
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <AppStatCard label="Connected" :value="connectedCount" :icon="PlugZap" color="emerald" />
      <AppStatCard label="Disconnected" :value="availableCount" :icon="Sparkles" color="violet" />
      <AppStatCard label="Needs Attention" :value="errorCount" :icon="Cloud" color="cyan" />
      <AppStatCard label="Automation Active" :value="automationEnabledCount" :icon="Workflow" color="amber" />
    </div>

    <AppCard title="Integration Catalog" subtitle="Each integration has a dedicated setup page with credentials, sync options, and health checks.">
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <article
          v-for="item in integrationCards"
          :key="item.key"
          class="rounded-[var(--radius-xl)] border border-[color:var(--aq-border)] p-4 bg-[color:var(--aq-surface-card)]"
          :style="item.enabled ? cardEnabledStyle : cardStyle"
        >
          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="text-sm font-semibold text-[color:var(--aq-fg)]">{{ item.label }}</div>
              <div class="text-xs text-[color:var(--aq-muted)] mt-1 leading-relaxed">{{ item.description }}</div>
              <div class="mt-2 flex items-center gap-2 text-[11px] text-[color:var(--aq-muted)]">
                <span class="rounded-full bg-[color:var(--aq-surface-2)] px-2 py-0.5 uppercase tracking-[0.12em]">{{ item.category }}</span>
                <span class="rounded-full bg-[color:var(--aq-surface-2)] px-2 py-0.5 uppercase tracking-[0.12em]">{{ formatAuthMethod(item.auth_method) }}</span>
              </div>
            </div>
            <span class="text-[10px] font-semibold px-2 py-1 rounded-full"
              :class="item.status === 'connected' ? 'bg-emerald-500/15 text-emerald-300' : item.status === 'error' ? 'bg-rose-500/15 text-rose-300' : 'bg-[color:var(--aq-surface-2)] text-[color:var(--aq-muted)]'">
              {{ item.status }}
            </span>
          </div>

          <div class="mt-4 text-xs text-[color:var(--aq-muted)]">
            <div v-if="item.last_synced_at">Last sync: {{ formatDate(item.last_synced_at) }}</div>
            <div v-else>No sync recorded yet</div>
            <div v-if="item.last_error" class="mt-1 text-rose-300">{{ item.last_error }}</div>
          </div>

          <div class="mt-4 flex items-center justify-between gap-2">
            <label class="text-xs font-semibold text-[color:var(--aq-muted)]">Enable</label>
            <input
              type="checkbox"
              :checked="item.enabled"
              @change="toggleIntegration(item.key, $event.target.checked)"
            />
          </div>
          <div class="mt-3 flex gap-2">
            <RouterLink
              :to="{ name: 'dashboard.integrations.detail', params: { key: item.key } }"
              class="inline-flex flex-1 items-center justify-center rounded-lg border border-[color:var(--aq-border)] px-3 py-2 text-xs font-semibold text-[color:var(--aq-fg)] hover:bg-[color:var(--aq-surface-2)] transition"
            >
              Manage
            </RouterLink>
            <a
              v-if="item.docs_url"
              :href="item.docs_url"
              target="_blank"
              rel="noreferrer"
              class="inline-flex items-center justify-center rounded-lg border border-[color:var(--aq-border)] px-3 py-2 text-xs font-semibold text-[color:var(--aq-muted)] hover:text-[color:var(--aq-fg)] transition"
            >
              Docs
            </a>
          </div>
        </article>
      </div>
    </AppCard>

    <AppCard title="Operations Tip" subtitle="How to use this area">
      <div class="text-sm text-[color:var(--aq-muted)] leading-relaxed">
        Enable connectors by organization, then attach credentials/sync jobs per integration workflow. This keeps
        operations modular while ensuring every toggle is backed by persistent backend state.
      </div>
    </AppCard>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { apiGet, apiPost, apiPut } from '../../lib/api';
import { PlugZap, Sparkles, Cloud, Workflow, RefreshCw } from 'lucide-vue-next';
import AppPageHeader from '../../components/ui/AppPageHeader.vue';
import AppCard from '../../components/ui/AppCard.vue';
import AppButton from '../../components/ui/AppButton.vue';
import AppStatCard from '../../components/ui/AppStatCard.vue';

const loading = ref(false);
const saving = ref(false);
const status = ref('');
const error = ref('');
const integrations = ref([]);
const modulePreferences = ref({});
const supportsIntegrationApi = ref(true);

const integrationCards = computed(() => {
  return (integrations.value || []).map((row) => ({
    ...row,
    enabled: row.enabled === true,
  }));
});

const connectedCount = computed(() => integrationCards.value.filter((x) => x.enabled).length);
const availableCount = computed(() => integrationCards.value.length - connectedCount.value);
const errorCount = computed(() => integrationCards.value.filter((x) => x.status === 'error').length);
const automationEnabledCount = computed(() => {
  const keys = ['zapier', 'slack', 'quickbooks', 'google_calendar'];
  const map = Object.fromEntries(integrationCards.value.map((x) => [x.key, x.enabled]));
  return keys.filter((k) => map[k] === true).length;
});

const cardStyle = {
  background: 'color-mix(in srgb, var(--aq-surface-card) 96%, transparent)',
};
const cardEnabledStyle = {
  background:
    'linear-gradient(160deg, color-mix(in srgb, var(--aq-primary) 14%, var(--aq-surface-card)), color-mix(in srgb, var(--aq-accent-2) 10%, var(--aq-surface-card)))',
};

function unwrap(res) {
  if (!res || typeof res !== 'object') return res;
  if (Object.prototype.hasOwnProperty.call(res, 'data')) return res.data;
  return res;
}

async function reload() {
  loading.value = true;
  status.value = '';
  error.value = '';
  try {
    const response = await apiGet('/v1/integrations');
    integrations.value = unwrap(response)?.integrations || [];
    supportsIntegrationApi.value = true;
  } catch (e) {
    const code = Number(e?.response?.status || 0);
    if (code === 404) {
      // Fallback for environments where integrations API is not yet deployed.
      supportsIntegrationApi.value = false;
      try {
        const settingsRes = await apiGet('/v1/agency/settings');
        modulePreferences.value = unwrap(settingsRes)?.settings?.module_preferences || {};
        const prefMap = modulePreferences.value?.integrations || {};
        const fallbackDefs = [
          { key: 'google_drive', label: 'Google Drive', description: 'Cloud document storage', category: 'storage', auth_method: 'oauth2', docs_url: 'https://developers.google.com/drive' },
          { key: 'google_calendar', label: 'Google Calendar', description: 'Calendar scheduling sync', category: 'scheduling', auth_method: 'oauth2', docs_url: 'https://developers.google.com/calendar' },
          { key: 'slack', label: 'Slack', description: 'Team communication and alerts', category: 'communication', auth_method: 'oauth2_or_webhook', docs_url: 'https://api.slack.com' },
          { key: 'dropbox', label: 'Dropbox', description: 'File transfer and archive', category: 'storage', auth_method: 'oauth2', docs_url: 'https://developers.dropbox.com' },
          { key: 'quickbooks', label: 'QuickBooks', description: 'Accounting and invoicing', category: 'finance', auth_method: 'oauth2', docs_url: 'https://developer.intuit.com' },
          { key: 'zapier', label: 'Zapier', description: 'Workflow automation', category: 'automation', auth_method: 'api_key_or_webhook', docs_url: 'https://platform.zapier.com' },
        ];
        integrations.value = fallbackDefs.map((d) => ({
          ...d,
          enabled: prefMap[d.key] === true,
          status: prefMap[d.key] === true ? 'connected' : 'disconnected',
          settings: {},
          connected_at: null,
          last_synced_at: null,
          last_error: null,
          docs_url: d.docs_url,
        }));
        error.value = '';
      } catch (fallbackError) {
        error.value =
          fallbackError?.response?.data?.message ||
          fallbackError?.message ||
          'Failed to load integration preferences.';
      }
    } else {
      error.value = e?.response?.data?.message || e?.message || 'Failed to load integration preferences.';
    }
  } finally {
    loading.value = false;
  }
}

async function toggleIntegration(key, enabled) {
  integrations.value = integrations.value.map((row) =>
    row.key === key ? { ...row, enabled: !!enabled, status: enabled ? 'connected' : 'disconnected' } : row
  );
  saving.value = true;
  status.value = '';
  error.value = '';
  try {
    if (supportsIntegrationApi.value) {
      if (enabled) {
        await apiPut(`/v1/integrations/${encodeURIComponent(key)}`, {
          enabled: true,
          status: 'connected',
        });
      } else {
        await apiPost(`/v1/integrations/${encodeURIComponent(key)}/disable`, {});
      }
    } else {
      const nextModulePreferences = {
        ...(modulePreferences.value || {}),
        integrations: {
          ...(modulePreferences.value?.integrations || {}),
          [key]: !!enabled,
        },
      };
      await apiPut('/v1/agency/settings', {
        module_preferences: nextModulePreferences,
      });
      modulePreferences.value = nextModulePreferences;
    }
    const label = integrationCards.value.find((d) => d.key === key)?.label || 'Integration';
    status.value = `${label} updated.`;
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to update integration.';
    await reload();
  } finally {
    saving.value = false;
  }
}

function formatAuthMethod(value) {
  const map = {
    oauth2: 'OAuth',
    oauth2_or_webhook: 'OAuth/Webhook',
    api_key_or_webhook: 'API Key/Webhook',
  };
  const key = String(value || '').toLowerCase();
  return map[key] || 'API';
}

function formatDate(value) {
  if (!value) return 'N/A';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return 'N/A';
  return d.toLocaleString();
}

reload();
</script>
