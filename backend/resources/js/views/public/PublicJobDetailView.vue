<template>
  <div class="min-h-screen bg-[var(--app-bg)] text-[var(--app-fg)] selection:bg-purple-500/30">
    <div class="max-w-7xl mx-auto px-6 sm:px-10 py-10">
      <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
          <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-white/10 bg-white/5 text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">
            Job detail
          </div>
          <h1 class="mt-5 font-display text-4xl sm:text-5xl text-white tracking-tight truncate">{{ job?.title || 'Loading…' }}</h1>
          <div v-if="job" class="mt-3 text-sm text-[color:var(--p-text-muted-color)] truncate">
            {{ job.organization_name }}
            <span class="opacity-40">•</span>
            {{ job.facility_name }}
            <span v-if="job.facility_city || job.facility_state" class="opacity-40">•</span>
            <span v-if="job.facility_city">{{ job.facility_city }}</span>
            <span v-if="job.facility_city && job.facility_state">, </span>
            <span v-if="job.facility_state">{{ job.facility_state }}</span>
          </div>

          <div v-if="job" class="mt-5 flex flex-wrap gap-2">
            <span class="px-3 py-1 rounded-full text-[11px] font-black tracking-widest uppercase border border-white/10 bg-white/5 text-slate-200">
              {{ job.specialty || 'Specialty —' }}
            </span>
            <span
              class="px-3 py-1 rounded-full text-[11px] font-black tracking-widest uppercase border"
              :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
            >
              {{ formatWorkMode(job.work_mode) }}
            </span>
            <span class="px-3 py-1 rounded-full text-[11px] font-black tracking-widest uppercase border border-white/10 bg-white/5 text-slate-200">
              Starts: {{ formatDate(job.start_date) }}
            </span>
          </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <RouterLink
            :to="{ name: 'public.jobs' }"
            class="px-4 py-2 rounded-full text-xs font-black tracking-widest uppercase border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10"
          >
            Back
          </RouterLink>
          <RouterLink
            v-if="job?.id"
            :to="{ name: 'public.jobs.apply', params: { id: job.id } }"
            class="px-4 py-2 rounded-full text-xs font-black tracking-widest uppercase border"
            :style="{ backgroundColor: primaryColor, borderColor: primaryColor, color: '#fff' }"
          >
            Apply
          </RouterLink>
        </div>
      </div>

      <div v-if="loading" class="mt-8 text-sm text-[color:var(--p-text-muted-color)]">Loading…</div>
      <div v-else-if="error" class="mt-8 text-sm text-red-400">{{ error }}</div>

      <div v-else-if="job" class="mt-8 grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-8 glass-dark rounded-[32px] p-8 border border-white/5">
          <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">About this role</div>
          <div class="mt-4 text-sm text-slate-200 whitespace-pre-wrap">{{ job.description || '—' }}</div>

          <div class="mt-7 p-5 rounded-2xl border border-white/10 bg-white/5">
            <div class="flex items-start justify-between gap-4">
              <div>
                <div class="text-[11px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Ready to apply?</div>
                <div class="mt-2 text-sm text-slate-200">Create (or verify) your candidate account and submit your application.</div>
              </div>
              <span class="material-symbols-outlined text-white/70">bolt</span>
            </div>
            <RouterLink
              :to="{ name: 'public.jobs.apply', params: { id: job.id } }"
              class="mt-4 inline-flex items-center justify-center w-full px-4 py-3 rounded-2xl text-xs font-black tracking-widest uppercase border"
              :style="{ backgroundColor: primaryColor, borderColor: primaryColor, color: '#fff' }"
            >
              Apply now
            </RouterLink>
          </div>
        </div>

        <div class="lg:col-span-4 glass-dark rounded-[32px] p-8 border border-white/5">
          <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Key details</div>

          <div class="mt-5 space-y-3 text-sm">
            <div class="flex items-center justify-between gap-2 rounded-2xl border border-white/10 bg-white/5 p-4">
              <div class="text-[color:var(--p-text-muted-color)]">Organization</div>
              <div class="text-white font-semibold truncate">{{ job.organization_name || '—' }}</div>
            </div>
            <div class="flex items-center justify-between gap-2 rounded-2xl border border-white/10 bg-white/5 p-4">
              <div class="text-[color:var(--p-text-muted-color)]">Facility</div>
              <div class="text-white font-semibold truncate">{{ job.facility_name || '—' }}</div>
            </div>
            <div class="flex items-center justify-between gap-2 rounded-2xl border border-white/10 bg-white/5 p-4">
              <div class="text-[color:var(--p-text-muted-color)]">Work mode</div>
              <div class="text-white font-semibold">{{ formatWorkMode(job.work_mode) }}</div>
            </div>
            <div class="flex items-center justify-between gap-2 rounded-2xl border border-white/10 bg-white/5 p-4">
              <div class="text-[color:var(--p-text-muted-color)]">Start date</div>
              <div class="text-white font-semibold">{{ formatDate(job.start_date) }}</div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                <div class="text-[11px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Pay rate</div>
                <div class="mt-2 text-white font-semibold">{{ money(job.pay_rate) }}/hr</div>
              </div>
              <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                <div class="text-[11px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Stipend</div>
                <div class="mt-2 text-white font-semibold">{{ money(job.stipend_weekly) }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { apiGet } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';

const route = useRoute();
const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const loading = ref(false);
const error = ref('');
const job = ref(null);

function money(v) {
  if (v === null || v === undefined || v === '') return '—';
  const n = Number(v);
  if (Number.isNaN(n)) return '—';
  return `$${n.toFixed(2)}`;
}

function formatDate(v) {
  if (!v) return '—';
  const d = new Date(v);
  if (Number.isNaN(d.getTime())) return String(v);
  return d.toLocaleDateString();
}

function formatWorkMode(v) {
  if (!v) return '—';
  if (v === 'on_site') return 'On-site';
  if (v === 'remote') return 'Remote';
  return String(v);
}

async function refresh() {
  const id = route.params.id;
  if (!id) return;

  loading.value = true;
  error.value = '';
  job.value = null;
  try {
    const res = await apiGet(`/public/job-board/${id}`);
    job.value = res?.data || null;
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to load job.';
  } finally {
    loading.value = false;
  }
}

watch(
  () => route.params.id,
  async () => {
    await refresh();
  }
);

onMounted(async () => {
  await brand.load();
  await refresh();
});
</script>
