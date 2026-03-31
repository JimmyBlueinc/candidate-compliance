<template>
  <div class="space-y-6">
    <div class="glass-dark rounded-[32px] p-8 border border-white/5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="font-display text-2xl text-white">Intake Feed</h2>
          <p class="text-sm text-[color:var(--p-text-muted-color)] mt-1">Live stream of incoming leads from your marketing website.</p>
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

      <div class="mt-6 rounded-2xl bg-white/[0.03] border border-white/5 p-6">
        <div class="flex items-center justify-between gap-4">
          <div>
            <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Incoming Leads</div>
            <div class="mt-1 text-sm text-[color:var(--p-text-muted-color)]">Most recent candidates who applied via the intake form</div>
          </div>
          <div class="text-xs text-[color:var(--p-text-muted-color)]">{{ items.length }} recent</div>
        </div>

        <div v-if="loading" class="mt-4 text-sm text-[color:var(--p-text-muted-color)]">Loading…</div>

        <div v-else class="mt-4 space-y-3">
          <div v-if="error" class="text-sm text-red-400">{{ error }}</div>

          <div v-if="items.length === 0" class="text-sm text-[color:var(--p-text-muted-color)]">No leads found.</div>

          <div
            v-for="row in items"
            :key="row.id"
            class="p-4 rounded-2xl bg-white/[0.02] border border-white/5 hover:bg-white/[0.04] transition-colors"
          >
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <div class="text-white font-semibold truncate">{{ displayName(row) }}</div>
                <div class="mt-1 text-xs text-[color:var(--p-text-muted-color)] truncate">
                  {{ row.email || '—' }}{{ row.phone ? ` • ${row.phone}` : '' }}
                </div>
                <div class="mt-2 text-xs text-slate-300 truncate">
                  {{ row.specialty || '—' }}
                  <span class="opacity-40">•</span>
                  {{ formatDate(row.last_applied_at || row.created_at || row.createdAt) }}
                </div>
              </div>

              <div class="shrink-0">
                <span
                  class="px-2 py-1 rounded-full text-[10px] font-black tracking-widest uppercase border"
                  :style="{ borderColor: primarySoftBorder, backgroundColor: primarySoftBg, color: primaryColor }"
                >
                  Web Lead
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { apiGet, normalizeApiList } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';

const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const loading = ref(false);
const error = ref('');
const items = ref([]);

function displayName(row) {
    return row?.name || `${row?.first_name || ''} ${row?.last_name || ''}`.trim() || 'Lead';
}

function formatDate(v) {
    if (!v) return '—';
    try {
        const d = new Date(v);
        if (Number.isNaN(d.getTime())) return String(v);
        return d.toLocaleString();
    } catch {
        return String(v);
    }
}

async function refresh() {
    loading.value = true;
    error.value = '';
    try {
        const res = await apiGet('/v1/intake/recent');
        items.value = normalizeApiList(res);
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Failed to load intake feed.';
        items.value = [];
    } finally {
        loading.value = false;
    }
}

refresh();
</script>
