<template>
  <div class="glass-dark rounded-[32px] p-8 flex flex-col border border-white/5">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="font-display text-xl text-white">Recent Website Intakes</h3>
        <p class="text-xs text-[color:var(--p-text-muted-color)] font-medium">Latest 5 Web Leads</p>
      </div>
      <button
        type="button"
        class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
        :style="{
          backgroundColor: primarySoftBg,
          borderColor: primarySoftBorder,
          color: primaryColor,
        }"
        @click="refresh"
      >
        Refresh
      </button>
    </div>

    <div v-if="loading" class="text-sm text-[color:var(--p-text-muted-color)]">Loading...</div>

    <div v-else-if="items.length === 0" class="text-sm text-[color:var(--p-text-muted-color)]">No recent intakes yet.</div>

    <div v-else class="space-y-3">
      <div
        v-for="c in items"
        :key="c.id"
        class="p-4 rounded-2xl bg-white/[0.03] border border-white/5 hover:bg-white/[0.06] hover:border-white/10 transition-all"
      >
        <div class="flex items-start justify-between gap-4">
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <div class="font-semibold text-white truncate">
                {{ c.name || `${c.first_name || ''} ${c.last_name || ''}`.trim() || 'Unknown' }}
              </div>
              <span
                v-if="hasTag(c.tags, 'New')"
                class="text-[10px] font-black tracking-widest uppercase px-2 py-0.5 rounded-md border"
                :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
              >
                New
              </span>
            </div>

            <div class="mt-1 text-xs text-[color:var(--p-text-muted-color)] truncate">
              {{ c.email || '—' }}
              <span class="opacity-40">•</span>
              {{ c.phone || '—' }}
            </div>

            <div class="mt-2 flex flex-wrap gap-2">
              <span
                v-if="c.specialty"
                class="text-[10px] font-black tracking-widest uppercase px-2 py-0.5 rounded-md border"
                :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
              >
                {{ c.specialty }}
              </span>

              <span
                v-for="tag in normalizedTags(c.tags)"
                :key="`${c.id}-${tag}`"
                class="text-[10px] font-black tracking-widest uppercase px-2 py-0.5 rounded-md bg-slate-500/10 text-slate-400 border border-white/5"
              >
                {{ tag }}
              </span>
            </div>
          </div>

          <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)] whitespace-nowrap">
            {{ formatAppliedAt(c.last_applied_at) }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
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
        const res = await apiGet('/v1/intake/recent');
        items.value = normalizeApiList(res);
    } finally {
        loading.value = false;
    }
}

function formatAppliedAt(value) {
    if (!value) return '—';
    const d = new Date(String(value));
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleString();
}

function hasTag(tags, match) {
    const list = Array.isArray(tags) ? tags : [];
    const target = String(match || '').trim().toLowerCase();
    return list.some((t) => String(t || '').trim().toLowerCase() === target);
}

function normalizedTags(tags) {
    const list = Array.isArray(tags) ? tags : [];
    return list
        .map((t) => String(t || '').trim())
        .filter((t) => t && !['web-lead', 'new'].includes(t.toLowerCase()));
}

let timer = null;

onMounted(async () => {
    await refresh();
    timer = window.setInterval(refresh, 15000);
});

onBeforeUnmount(() => {
    if (timer) window.clearInterval(timer);
});
</script>
