<template>
  <div class="space-y-6">
    <AppCard title="Compliance Dashboard" subtitle="Readiness overview and required actions." accent accent-color="violet">
      <template #actions>
        <AppButton variant="secondary" size="sm" @click="refresh">
          <RefreshCw class="w-4 h-4" />
          Refresh
        </AppButton>
      </template>

      <div v-if="loading" class="text-sm text-[color:var(--aq-muted)]">Loading...</div>
      <div v-else class="space-y-6">
        <div v-if="error" class="text-sm text-rose-400">{{ error }}</div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <AppStatCard label="Blocked" :value="summary.blocked_count" color="rose" />
          <AppStatCard label="Pending Verification" :value="summary.pending_count" color="amber" />
          <AppStatCard label="Expiring Soon (30d)" :value="summary.expiring_soon_count" color="primary" />
          <AppStatCard label="Ready" :value="summary.ready_count" color="emerald" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
          <AppCard title="Workers Blocked" accent accent-color="rose" hoverable>
            <div class="max-h-[400px] overflow-y-auto -mx-6">
              <div v-if="blocked.length === 0" class="px-6 py-4 text-sm text-[color:var(--aq-muted)]">No blocked workers.</div>
              <div v-for="row in blocked" :key="row.candidate?.id" class="px-6 py-4 border-b border-[color:var(--aq-border)]">
                <div class="font-semibold text-[color:var(--aq-fg)] truncate">{{ row.candidate?.name || '—' }}</div>
                <div class="mt-1 text-xs text-[color:var(--aq-muted)] truncate">{{ reasonText(row) }}</div>
              </div>
            </div>
          </AppCard>

          <AppCard title="Pending Verification" accent accent-color="amber" hoverable>
            <div class="max-h-[400px] overflow-y-auto -mx-6">
              <div v-if="pending.length === 0" class="px-6 py-4 text-sm text-[color:var(--aq-muted)]">No pending verifications.</div>
              <div v-for="row in pending" :key="row.candidate?.id" class="px-6 py-4 border-b border-[color:var(--aq-border)]">
                <div class="font-semibold text-[color:var(--aq-fg)] truncate">{{ row.candidate?.name || '—' }}</div>
                <div class="mt-1 text-xs text-[color:var(--aq-muted)] truncate">{{ reasonText(row) }}</div>
              </div>
            </div>
          </AppCard>

          <AppCard title="Expiring Soon" accent accent-color="cyan" hoverable>
            <div class="max-h-[400px] overflow-y-auto -mx-6">
              <div v-if="expiringSoon.length === 0" class="px-6 py-4 text-sm text-[color:var(--aq-muted)]">No upcoming expirations.</div>
              <div v-for="row in expiringSoon" :key="row.candidate?.id" class="px-6 py-4 border-b border-[color:var(--aq-border)]">
                <div class="font-semibold text-[color:var(--aq-fg)] truncate">{{ row.candidate?.name || '—' }}</div>
                <div class="mt-1 text-xs text-[color:var(--aq-muted)] truncate">
                  {{ (row.expiring_soon && row.expiring_soon[0] && (row.expiring_soon[0].name || row.expiring_soon[0].category)) ? 'Expiring: ' + (row.expiring_soon[0].name || row.expiring_soon[0].category) : 'Credential expiring soon' }}
                </div>
              </div>
            </div>
          </AppCard>
        </div>

        <div class="rounded-2xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-card)] p-4">
          <div class="text-xs text-[color:var(--aq-muted)]">
            Verification work is managed in the Compliance Queue.
          </div>
          <div class="mt-3">
            <router-link
              class="inline-flex px-4 py-2 rounded-xl text-xs font-bold border transition-colors bg-[color:var(--aq-primary)]/10 border-[color:var(--aq-primary)]/30 text-[color:var(--aq-primary)] hover:bg-[color:var(--aq-primary)]/20"
              :to="{ name: 'dashboard.compliance_queue' }"
            >
              Open Compliance Queue
            </router-link>
          </div>
        </div>
      </div>
    </AppCard>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { apiGet } from '../../lib/api';
import { RefreshCw } from 'lucide-vue-next';
import AppCard from '../../components/ui/AppCard.vue';
import AppButton from '../../components/ui/AppButton.vue';
import AppStatCard from '../../components/ui/AppStatCard.vue';

const loading = ref(false);
const error = ref('');

const summary = ref({ blocked_count: 0, pending_count: 0, expiring_soon_count: 0, ready_count: 0 });
const blocked = ref([]);
const pending = ref([]);
const expiringSoon = ref([]);

function reasonText(row) {
    const reason = row?.reason;
    if (!reason) return row?.status ? `Status: ${row.status}` : '—';
    const name = reason?.name || reason?.category || 'Credential';
    if (row?.reason_type === 'missing') return `Missing: ${name}`;
    if (row?.reason_type === 'expired') return `Expired: ${name}`;
    if (row?.reason_type === 'pending') return `Pending: ${name}`;
    return name;
}

async function refresh() {
    loading.value = true;
    error.value = '';
    try {
        const res = await apiGet('/v1/compliance/dashboard');
        const data = res?.data || res;
        summary.value = data?.summary || summary.value;
        blocked.value = Array.isArray(data?.blocked) ? data.blocked : [];
        pending.value = Array.isArray(data?.pending_verification) ? data.pending_verification : [];
        expiringSoon.value = Array.isArray(data?.expiring_soon) ? data.expiring_soon : [];
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Failed to load compliance dashboard.';
    } finally {
        loading.value = false;
    }
}

refresh();
</script>
