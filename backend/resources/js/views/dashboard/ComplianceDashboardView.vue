<template>
  <div class="space-y-6">
    <div class="aq-card p-6">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="font-display text-2xl text-white">Compliance Dashboard</h2>
          <p class="text-sm text-[color:var(--p-text-muted-color)] mt-1">Readiness overview and required actions.</p>
        </div>
        <button
          type="button"
          class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
          :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
          @click="refresh"
        >
          Refresh
        </button>
      </div>

      <div v-if="loading" class="mt-6 text-sm text-[color:var(--p-text-muted-color)]">Loading...</div>
      <div v-else class="mt-6 space-y-6">
        <div v-if="error" class="text-sm text-red-400">{{ error }}</div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="p-5 rounded-2xl border border-white/5 bg-white/[0.02]">
            <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Blocked</div>
            <div class="mt-2 text-2xl font-display text-red-400">{{ summary.blocked_count }}</div>
          </div>
          <div class="p-5 rounded-2xl border border-white/5 bg-white/[0.02]">
            <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Pending Verification</div>
            <div class="mt-2 text-2xl font-display text-amber-300">{{ summary.pending_count }}</div>
          </div>
          <div class="p-5 rounded-2xl border border-white/5 bg-white/[0.02]">
            <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Expiring Soon (30d)</div>
            <div class="mt-2 text-2xl font-display text-white">{{ summary.expiring_soon_count }}</div>
          </div>
          <div class="p-5 rounded-2xl border border-white/5 bg-white/[0.02]">
            <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Ready</div>
            <div class="mt-2 text-2xl font-display" :style="{ color: primaryColor }">{{ summary.ready_count }}</div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
          <div class="rounded-2xl border border-white/5 bg-white/[0.02] overflow-hidden">
            <div class="px-4 py-3 border-b border-white/5 flex items-center justify-between">
              <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Workers Blocked</div>
              <div class="text-xs text-[color:var(--p-text-muted-color)]">{{ blocked.length }}</div>
            </div>
            <div class="max-h-[520px] overflow-y-auto">
              <div v-if="blocked.length === 0" class="px-4 py-4 text-sm text-[color:var(--p-text-muted-color)]">No blocked workers.</div>
              <div v-for="row in blocked" :key="row.candidate?.id" class="px-4 py-4 border-b border-white/5">
                <div class="font-semibold text-white truncate">{{ row.candidate?.name || '—' }}</div>
                <div class="mt-1 text-xs text-[color:var(--p-text-muted-color)] truncate">{{ reasonText(row) }}</div>
              </div>
            </div>
          </div>

          <div class="rounded-2xl border border-white/5 bg-white/[0.02] overflow-hidden">
            <div class="px-4 py-3 border-b border-white/5 flex items-center justify-between">
              <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Pending Verification</div>
              <div class="text-xs text-[color:var(--p-text-muted-color)]">{{ pending.length }}</div>
            </div>
            <div class="max-h-[520px] overflow-y-auto">
              <div v-if="pending.length === 0" class="px-4 py-4 text-sm text-[color:var(--p-text-muted-color)]">No pending verifications.</div>
              <div v-for="row in pending" :key="row.candidate?.id" class="px-4 py-4 border-b border-white/5">
                <div class="font-semibold text-white truncate">{{ row.candidate?.name || '—' }}</div>
                <div class="mt-1 text-xs text-[color:var(--p-text-muted-color)] truncate">{{ reasonText(row) }}</div>
              </div>
            </div>
          </div>

          <div class="rounded-2xl border border-white/5 bg-white/[0.02] overflow-hidden">
            <div class="px-4 py-3 border-b border-white/5 flex items-center justify-between">
              <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Expiring Soon</div>
              <div class="text-xs text-[color:var(--p-text-muted-color)]">{{ expiringSoon.length }}</div>
            </div>
            <div class="max-h-[520px] overflow-y-auto">
              <div v-if="expiringSoon.length === 0" class="px-4 py-4 text-sm text-[color:var(--p-text-muted-color)]">No upcoming expirations.</div>
              <div v-for="row in expiringSoon" :key="row.candidate?.id" class="px-4 py-4 border-b border-white/5">
                <div class="font-semibold text-white truncate">{{ row.candidate?.name || '—' }}</div>
                <div class="mt-1 text-xs text-[color:var(--p-text-muted-color)] truncate">
                  {{ (row.expiring_soon && row.expiring_soon[0] && (row.expiring_soon[0].name || row.expiring_soon[0].category)) ? 'Expiring: ' + (row.expiring_soon[0].name || row.expiring_soon[0].category) : 'Credential expiring soon' }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="rounded-2xl border border-white/5 bg-white/[0.02] p-4">
          <div class="text-xs text-[color:var(--p-text-muted-color)]">
            Verification work is managed in the Compliance Queue.
          </div>
          <div class="mt-3">
            <router-link
              class="inline-flex px-4 py-2 rounded-2xl text-xs font-black tracking-widest uppercase border transition-colors"
              :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
              :to="{ name: 'dashboard.compliance_queue' }"
            >
              Open Compliance Queue
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { apiGet } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';

const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

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
