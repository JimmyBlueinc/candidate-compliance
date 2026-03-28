<template>
  <div class="space-y-6">
    <AppPageHeader
      title="Integrations Hub"
      subtitle="Connect operational tools like Google Drive, Slack, and calendars."
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

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <AppStatCard label="Connected" :value="connectedCount" :icon="PlugZap" color="emerald" />
      <AppStatCard label="Ready to Connect" :value="availableCount" :icon="Sparkles" color="violet" />
      <AppStatCard label="Cloud Storage" :value="storageEnabled ? 'Enabled' : 'Disabled'" :icon="Cloud" color="cyan" />
      <AppStatCard label="Automation Apps" :value="automationEnabledCount" :icon="Workflow" color="amber" />
    </div>

    <AppCard title="Available Integrations" subtitle="Each toggle is persisted through backend integration records.">
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
            </div>
            <span class="text-[10px] font-semibold px-2 py-1 rounded-full"
              :class="item.enabled ? 'bg-emerald-500/15 text-emerald-300' : 'bg-[color:var(--aq-surface-2)] text-[color:var(--aq-muted)]'">
              {{ item.enabled ? 'Enabled' : 'Disabled' }}
            </span>
          </div>

          <div class="mt-4 flex items-center justify-between">
            <label class="text-xs font-semibold text-[color:var(--aq-muted)]">Enable</label>
            <input
              type="checkbox"
              :checked="item.enabled"
              @change="toggleIntegration(item.key, $event.target.checked)"
            />
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
import { apiGet, apiPut } from '../../lib/api';
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

const integrationDefs = [
  { key: 'google_drive', label: 'Google Drive', description: 'Store and sync compliance documents.' },
  { key: 'google_calendar', label: 'Google Calendar', description: 'Sync shifts and interviews.' },
  { key: 'slack', label: 'Slack', description: 'Send live operations alerts to channels.' },
  { key: 'dropbox', label: 'Dropbox', description: 'Share credential packets with teams.' },
  { key: 'quickbooks', label: 'QuickBooks', description: 'Streamline invoice and payment workflows.' },
  { key: 'zapier', label: 'Zapier', description: 'Automate repetitive operations tasks.' },
];

const integrationCards = computed(() => {
  const byKey = Object.fromEntries((integrations.value || []).map((row) => [row.key, row]));
  return integrationDefs.map((item) => {
    const row = byKey[item.key] || {};
    return {
      ...item,
      enabled: row.enabled === true,
      status: row.status || 'disconnected',
      settings: row.settings || {},
      connected_at: row.connected_at || null,
      last_error: row.last_error || null,
    };
  });
});

const connectedCount = computed(() => integrationCards.value.filter((x) => x.enabled).length);
const availableCount = computed(() => integrationCards.value.length - connectedCount.value);
const storageEnabled = computed(() => {
  const map = Object.fromEntries(integrationCards.value.map((x) => [x.key, x.enabled]));
  return map.google_drive === true || map.dropbox === true;
});
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
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to load integration preferences.';
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
    await apiPut(`/v1/integrations/${encodeURIComponent(key)}`, {
      enabled: !!enabled,
      status: enabled ? 'connected' : 'disconnected',
    });
    status.value = `${integrationDefs.find((d) => d.key === key)?.label || 'Integration'} updated.`;
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to update integration.';
    await reload();
  } finally {
    saving.value = false;
  }
}

reload();
</script>
