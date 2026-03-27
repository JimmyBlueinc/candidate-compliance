<template>
  <div class="space-y-8">
    <UiPageHeader
      title="Facility Dashboard"
      subtitle="Review timesheets and track invoices for your facility."
    >
      <template #actions>
        <button
          type="button"
          class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
          :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
          @click="refresh"
        >
          Refresh
        </button>
      </template>
    </UiPageHeader>

    <UiCard
      v-motion
      :initial="{ opacity: 0, y: 10 }"
      :enter="{ opacity: 1, y: 0, transition: { duration: 0.35 } }"
      class="p-8"
    >
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-5 rounded-2xl bg-white/[0.03] border border-white/5">
          <div class="text-[10px] uppercase tracking-widest text-slate-500 font-black">Active Assignments</div>
          <div class="mt-2 text-3xl font-black text-white">{{ summary.active_assignments ?? '—' }}</div>
          <div class="mt-3">
            <button
              type="button"
              class="px-4 py-2 rounded-xl text-xs font-bold border transition-colors"
              :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
              @click="router.push({ name: 'facility.workers' })"
            >
              View Workers
            </button>
          </div>
        </div>
        <div class="p-5 rounded-2xl bg-white/[0.03] border border-white/5">
          <div class="text-[10px] uppercase tracking-widest text-slate-500 font-black">Pending Timesheets</div>
          <div class="mt-2 text-3xl font-black text-white">{{ summary.pending_timesheets ?? '—' }}</div>
          <div class="mt-3">
            <button
              type="button"
              class="px-4 py-2 rounded-xl text-xs font-bold border transition-colors"
              :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
              @click="router.push({ name: 'facility.timesheets' })"
            >
              Review Timesheets
            </button>
          </div>
        </div>
        <div class="p-5 rounded-2xl bg-white/[0.03] border border-white/5">
          <div class="text-[10px] uppercase tracking-widest text-slate-500 font-black">Outstanding Balance</div>
          <div class="mt-2 text-3xl font-black text-white">{{ formatCurrency(summary.outstanding_balance) }}</div>
          <div class="mt-3">
            <button
              type="button"
              class="px-4 py-2 rounded-xl text-xs font-bold border transition-colors"
              :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
              @click="router.push({ name: 'facility.invoices' })"
            >
              View Invoices
            </button>
          </div>
        </div>
      </div>
    </UiCard>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <UiCard
        v-motion
        :initial="{ opacity: 0, y: 10 }"
        :enter="{ opacity: 1, y: 0, transition: { delay: 0.04, duration: 0.35 } }"
        class="p-6"
      >
        <div class="flex items-center justify-between">
          <div class="text-[10px] uppercase tracking-widest text-slate-500 font-black">Active Workers</div>
          <button
            type="button"
            class="px-3 py-1.5 rounded-full text-[10px] font-black border transition-colors"
            :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
            @click="router.push({ name: 'facility.workers' })"
          >
            See All
          </button>
        </div>

        <div class="mt-4 space-y-3">
          <div v-for="w in (summary.active_workers || []).slice(0, 6)" :key="w.assignment_id" class="flex items-center justify-between gap-4">
            <div class="min-w-0">
              <div class="text-white font-semibold truncate">{{ w.candidate?.name || 'Unknown' }}</div>
              <div class="text-xs text-[color:var(--p-text-muted-color)] truncate">{{ w.role || w.candidate?.specialty || '—' }}</div>
            </div>
            <div class="text-xs text-slate-400">{{ w.start_date || '—' }}</div>
          </div>
          <div v-if="!summary.active_workers || summary.active_workers.length === 0" class="text-sm text-slate-500">No active workers.</div>
        </div>
      </UiCard>

      <UiCard
        v-motion
        :initial="{ opacity: 0, y: 10 }"
        :enter="{ opacity: 1, y: 0, transition: { delay: 0.08, duration: 0.35 } }"
        class="p-6"
      >
        <div class="flex items-center justify-between">
          <div class="text-[10px] uppercase tracking-widest text-slate-500 font-black">Upcoming Shifts</div>
          <button
            type="button"
            class="px-3 py-1.5 rounded-full text-[10px] font-black border transition-colors"
            :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
            @click="router.push({ name: 'facility.shifts' })"
          >
            See All
          </button>
        </div>

        <div class="mt-4 space-y-3">
          <div v-for="s in (summary.upcoming_shifts || []).slice(0, 6)" :key="s.id" class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <div class="text-white font-semibold truncate">{{ s.candidate?.name || 'Unassigned' }}</div>
              <div class="text-xs text-[color:var(--p-text-muted-color)] truncate">
                {{ formatDateTime(s.starts_at) }} → {{ formatDateTime(s.ends_at) }}
              </div>
            </div>
            <div class="text-xs text-slate-400">{{ String(s.status || '') }}</div>
          </div>
          <div v-if="!summary.upcoming_shifts || summary.upcoming_shifts.length === 0" class="text-sm text-slate-500">No upcoming shifts.</div>
        </div>
      </UiCard>

      <UiCard
        v-motion
        :initial="{ opacity: 0, y: 10 }"
        :enter="{ opacity: 1, y: 0, transition: { delay: 0.12, duration: 0.35 } }"
        class="p-6"
      >
        <div class="flex items-center justify-between">
          <div class="text-[10px] uppercase tracking-widest text-slate-500 font-black">Pending Timesheets</div>
          <button
            type="button"
            class="px-3 py-1.5 rounded-full text-[10px] font-black border transition-colors"
            :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
            @click="router.push({ name: 'facility.timesheets' })"
          >
            Review
          </button>
        </div>

        <div class="mt-4 space-y-3">
          <div v-for="t in (summary.pending_timesheet_items || []).slice(0, 6)" :key="t.id" class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <div class="text-white font-semibold truncate">{{ t.candidate?.name || 'Unknown' }}</div>
              <div class="text-xs text-[color:var(--p-text-muted-color)] truncate">Week of {{ t.week_start || '—' }}</div>
            </div>
            <div class="text-xs text-slate-400">{{ Number(t.total_hours || 0).toFixed(2) }} hrs</div>
          </div>
          <div v-if="!summary.pending_timesheet_items || summary.pending_timesheet_items.length === 0" class="text-sm text-slate-500">No pending timesheets.</div>
        </div>
      </UiCard>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { apiGet } from '../../lib/api';
import UiCard from '../../components/ui/UiCard.vue';
import UiPageHeader from '../../components/ui/UiPageHeader.vue';
import { useBrandStore } from '../../stores/brand';

const router = useRouter();
const brand = useBrandStore();
const summary = ref({});

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

function formatCurrency(value) {
  const num = Number(value);
  if (!Number.isFinite(num)) return '—';
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(num);
}

function formatDateTime(value) {
  if (!value) return '—';
  try {
    const d = new Date(String(value));
    if (Number.isNaN(d.getTime())) return String(value);
    return d.toLocaleString();
  } catch {
    return String(value);
  }
}

async function refresh() {
  try {
    summary.value = await apiGet('/v1/facility/dashboard');
  } catch {
    summary.value = {};
  }
}

onMounted(refresh);
</script>
