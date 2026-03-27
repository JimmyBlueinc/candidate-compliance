<template>
  <div class="space-y-6">
    <div class="glass-dark rounded-[32px] p-8 border border-white/5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="font-display text-2xl text-white">Logistics</h2>
          <p class="text-sm text-[color:var(--p-text-muted-color)] mt-1">Active placements awaiting arrival confirmation.</p>
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
      <div v-else-if="items.length === 0" class="mt-6 text-sm text-[color:var(--p-text-muted-color)]">No arrivals pending.</div>

      <div v-else class="mt-6 space-y-3">
        <div
          v-for="row in items"
          :key="row.id"
          class="p-4 rounded-2xl bg-red-500/5 border border-red-500/20 hover:bg-red-500/10 transition-all"
        >
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <div class="font-semibold text-white truncate">{{ row.candidate?.name || 'Candidate' }}</div>
              <div class="mt-1 text-xs text-[color:var(--p-text-muted-color)] truncate">{{ row.candidate?.email || '—' }}</div>
              <div class="mt-2 text-xs text-slate-300 truncate">
                {{ row.job_order?.title || 'Job Order' }}
                <span class="opacity-40">•</span>
                {{ row.job_order?.facility_name || '—' }}
              </div>
            </div>

            <div class="shrink-0 text-right">
              <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Start Date</div>
              <div class="mt-1 text-sm font-bold" :style="{ color: primaryColor }">{{ row.start_date || '—' }}</div>
              <div class="mt-2 text-[10px] font-black tracking-widest uppercase text-red-400">Arrival Not Confirmed</div>

              <RouterLink
                class="inline-block mt-3 px-3 py-1.5 rounded-full text-xs font-bold border border-white/10 bg-white/5 text-slate-300 hover:bg-white/10"
                :to="{ name: 'dashboard.logistics_detail', params: { id: row.id } }"
              >
                Manage
              </RouterLink>
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

const items = ref([]);
const loading = ref(false);

async function refresh() {
    loading.value = true;
    try {
        const res = await apiGet('/v1/logistics/needs-arrival');
        items.value = normalizeApiList(res);
    } finally {
        loading.value = false;
    }
}

refresh();
</script>
