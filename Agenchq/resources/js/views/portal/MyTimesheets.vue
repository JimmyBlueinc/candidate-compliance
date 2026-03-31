<template>
  <div class="space-y-8">
    <UiPageHeader
      title="My Timesheets"
      subtitle="Log your hours and submit for approval."
    >
      <template #actions>
        <button
          type="button"
          class="px-4 py-2 rounded-full text-xs font-black tracking-widest uppercase border transition-colors"
          :style="{ backgroundColor: primaryColor, borderColor: primaryColor, color: '#fff' }"
          @click="showCreateModal = true"
        >
          Create Timesheet
        </button>
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
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/5">
          <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Draft</div>
          <div class="mt-2 text-2xl font-display text-white">{{ metrics.draft }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/5">
          <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Submitted</div>
          <div class="mt-2 text-2xl font-display text-amber-300">{{ metrics.submitted }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/5">
          <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Approved</div>
          <div class="mt-2 text-2xl font-display" :style="{ color: primaryColor }">{{ metrics.approved }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/5">
          <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Total Hours</div>
          <div class="mt-2 text-2xl font-display text-white">{{ metrics.totalHours }}</div>
        </div>
      </div>

      <div v-if="loading" class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div v-for="i in 2" :key="i" class="h-40 rounded-2xl bg-white/5 animate-pulse"></div>
      </div>

      <div v-else class="mt-8 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <div
          v-for="(ts, idx) in timesheets"
          :key="ts.id"
          v-motion
          :initial="{ opacity: 0, y: 8 }"
          :enter="{ opacity: 1, y: 0, transition: { delay: 0.02 + idx * 0.02, duration: 0.3 } }"
          class="p-6 rounded-2xl bg-white/[0.03] border border-white/5 hover:bg-white/[0.06] hover:border-white/10 transition-all"
        >
          <div class="flex items-start justify-between">
            <div>
              <div class="font-display text-lg text-white">{{ ts.facility }}</div>
              <div class="text-xs text-[color:var(--p-text-muted-color)] mt-1">Week of {{ ts.week_start }}</div>
            </div>
            <Tag :value="ts.status" :severity="getStatusSeverity(ts.status)" />
          </div>

          <div class="mt-6 flex items-end justify-between">
            <div class="space-y-1">
              <div class="text-[10px] uppercase tracking-widest text-slate-500 font-black">Total Hours</div>
              <div class="text-2xl font-bold text-white">{{ ts.total_hours }}</div>
            </div>
            <div class="flex items-center gap-1 overflow-x-auto pb-1 max-w-[150px]">
              <div v-for="(h, idx) in ts.daily_hours" :key="idx" class="flex flex-col items-center">
                <div class="text-[8px] text-slate-600 uppercase">{{ days[idx].charAt(0) }}</div>
                <div class="text-[10px] font-bold text-slate-400">{{ h }}</div>
              </div>
            </div>
          </div>

          <div v-if="ts.status === 'rejected' && ts.rejection_reason" class="mt-4 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-xs text-red-400 italic">
            Rejection reason: "{{ ts.rejection_reason }}"
          </div>
          <div v-if="ts.status === 'draft' || ts.status === 'rejected'" class="mt-6 flex gap-2">
            <button 
              class="flex-1 px-4 py-2 rounded-xl text-xs font-bold transition-colors"
              :style="{ backgroundColor: primaryColor, color: '#fff' }"
              :disabled="actingId === ts.id"
              @click="submitTimesheet(ts)"
            >
              {{ actingId === ts.id ? 'Submitting...' : 'Submit for Facility Approval' }}
            </button>
          </div>
        </div>

        <div v-if="timesheets.length === 0" class="col-span-full py-20 text-center text-slate-400">
          <i class="pi pi-clock text-4xl mb-4 opacity-20"></i>
          <p>No timesheets found. Log your first one to get paid.</p>
        </div>
      </div>
    </UiCard>
  </div>

  <TimesheetFormModal 
    :show="showCreateModal" 
    :loading="acting"
    :primary-color="primaryColor"
    @close="showCreateModal = false"
    @submit="handleCreateTimesheet"
  />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { apiGet, apiPost, normalizeApiList } from '../../lib/api';
import UiCard from '../../components/ui/UiCard.vue';
import UiPageHeader from '../../components/ui/UiPageHeader.vue';
import { useBrandStore } from '../../stores/brand';
import Tag from 'primevue/tag';
import TimesheetFormModal from '../../components/timesheets/TimesheetFormModal.vue';

const brand = useBrandStore();
const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const timesheets = ref([]);
const loading = ref(false);
const acting = ref(false);
const actingId = ref(null);
const showCreateModal = ref(false);
const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

const metrics = computed(() => {
  const list = Array.isArray(timesheets.value) ? timesheets.value : [];
  let draft = 0;
  let submitted = 0;
  let approved = 0;
  let totalHoursNum = 0;

  for (const t of list) {
    const s = String(t?.status || '').toLowerCase();
    if (s === 'draft') draft += 1;
    if (s === 'submitted') submitted += 1;
    if (s === 'facility_approved' || s === 'agency_approved') approved += 1;
    totalHoursNum += Number(t?.total_hours || 0) || 0;
  }

  return {
    draft,
    submitted,
    approved,
    totalHours: totalHoursNum.toFixed(1),
  };
});

async function refresh() {
  loading.value = true;
  try {
    const res = await apiGet('/v1/candidate/timesheets');
    timesheets.value = normalizeApiList(res);
  } finally {
    loading.value = false;
  }
}

async function handleCreateTimesheet(formData) {
  acting.value = true;
  try {
    await apiPost('/v1/candidate/timesheets', {
      ...formData,
      daily_hours: formData.hours // Map UI hours array to API expected field
    });
    showCreateModal.value = false;
    await refresh();
  } finally {
    acting.value = false;
  }
}

async function submitTimesheet(ts) {
  actingId.value = ts.id;
  try {
    await apiPost(`/v1/timesheets/${ts.id}/submit`);
    await refresh();
  } finally {
    actingId.value = null;
  }
}

function getStatusSeverity(status) {
  switch (status) {
    case 'draft': return 'info';
    case 'submitted': return 'warn';
    case 'facility_approved': return 'info';
    case 'agency_approved': return 'success';
    case 'rejected': return 'danger';
    default: return 'secondary';
  }
}

onMounted(refresh);
</script>
