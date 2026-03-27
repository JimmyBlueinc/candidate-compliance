<template>
  <div class="space-y-8">
    <UiPageHeader
      title="Available Shifts"
      subtitle="Browse and request upcoming facility shifts."
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
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/5">
          <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Available</div>
          <div class="mt-2 text-2xl font-display text-white">{{ metrics.open }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/5">
          <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Requested</div>
          <div class="mt-2 text-2xl font-display text-amber-300">{{ metrics.requested }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/5">
          <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Assigned</div>
          <div class="mt-2 text-2xl font-display" :style="{ color: primaryColor }">{{ metrics.assigned }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/5">
          <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Today</div>
          <div class="mt-2 text-2xl font-display text-white">{{ metrics.today }}</div>
        </div>
      </div>

      <div v-if="loading" class="mt-8 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <div v-for="i in 3" :key="i" class="h-48 rounded-2xl bg-white/5 animate-pulse"></div>
      </div>

      <div v-else class="mt-8 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <div
          v-for="(shift, idx) in shifts"
          :key="shift.id"
          v-motion
          :initial="{ opacity: 0, y: 8 }"
          :enter="{ opacity: 1, y: 0, transition: { delay: 0.02 + idx * 0.02, duration: 0.3 } }"
          class="p-6 rounded-2xl bg-white/[0.03] border border-white/5 hover:bg-white/[0.06] hover:border-white/10 transition-all flex flex-col justify-between"
        >
          <div>
            <div class="flex items-start justify-between">
              <div class="font-display text-lg text-white">{{ shift.facility }}</div>
              <Tag :value="shift.status" :severity="getStatusSeverity(shift.status)" />
            </div>
            
            <div class="mt-4 space-y-2">
              <div class="flex items-center gap-2 text-sm text-slate-300">
                <i class="pi pi-calendar text-xs"></i>
                {{ shift.date }}
              </div>
              <div class="flex items-center gap-2 text-sm text-slate-300">
                <i class="pi pi-clock text-xs"></i>
                {{ shift.start_time }} - {{ shift.end_time }}
              </div>
            </div>

            <div v-if="shift.notes" class="mt-4 text-xs text-[color:var(--p-text-muted-color)] line-clamp-2">
              {{ shift.notes }}
            </div>
          </div>

          <div class="mt-6 flex items-center gap-2">
            <template v-if="shift.status === 'open'">
              <button 
                class="flex-1 px-4 py-2 rounded-xl text-xs font-bold transition-colors"
                :style="{ backgroundColor: primaryColor, color: '#fff' }"
                :disabled="actingId === shift.id"
                @click="requestShift(shift)"
              >
                {{ actingId === shift.id ? 'Requesting...' : 'Request Shift' }}
              </button>
            </template>

            <template v-else-if="shift.status === 'requested'">
              <button 
                class="flex-1 px-4 py-2 rounded-xl text-xs font-bold bg-white/5 border border-white/10 text-red-400 hover:bg-red-400/10 transition-colors"
                :disabled="actingId === shift.id"
                @click="withdrawRequest(shift)"
              >
                Withdraw Request
              </button>
            </template>

            <template v-else-if="shift.status === 'assigned'">
              <button 
                v-if="!shift.checked_in_at"
                class="flex-1 px-4 py-2 rounded-xl text-xs font-bold bg-green-500 text-white transition-colors"
                :disabled="actingId === shift.id"
                @click="checkIn(shift)"
              >
                Check In
              </button>
              <button 
                v-else-if="!shift.checked_out_at"
                class="flex-1 px-4 py-2 rounded-xl text-xs font-bold bg-orange-500 text-white transition-colors"
                :disabled="actingId === shift.id"
                @click="checkOut(shift)"
              >
                Check Out
              </button>
              <div v-else class="text-xs font-bold text-green-500 flex items-center gap-1">
                <i class="pi pi-check-circle"></i> Completed
              </div>
            </template>
          </div>
        </div>

        <div v-if="shifts.length === 0" class="col-span-full py-20 text-center text-slate-400">
          <i class="pi pi-calendar-minus text-4xl mb-4 opacity-20"></i>
          <p>No available shifts found for your specialty.</p>
        </div>
      </div>
    </UiCard>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { apiGet, apiPost, normalizeApiList } from '../../lib/api';
import UiCard from '../../components/ui/UiCard.vue';
import UiPageHeader from '../../components/ui/UiPageHeader.vue';
import { useBrandStore } from '../../stores/brand';
import Tag from 'primevue/tag';

const brand = useBrandStore();
const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const shifts = ref([]);
const loading = ref(false);
const actingId = ref(null);

const metrics = computed(() => {
  const list = Array.isArray(shifts.value) ? shifts.value : [];
  const today = new Date();
  const todayKey = new Date(today.getFullYear(), today.getMonth(), today.getDate()).toISOString().slice(0, 10);

  let open = 0;
  let requested = 0;
  let assigned = 0;
  let todayCount = 0;

  for (const s of list) {
    const status = String(s?.status || '').toLowerCase();
    if (status === 'open') open += 1;
    if (status === 'requested') requested += 1;
    if (status === 'assigned') assigned += 1;

    const dateKey = String(s?.date || '').slice(0, 10);
    if (dateKey && dateKey === todayKey) todayCount += 1;
  }

  return {
    open,
    requested,
    assigned,
    today: todayCount,
  };
});

async function refresh() {
  loading.value = true;
  try {
    const res = await apiGet('/v1/shifts/available');
    shifts.value = normalizeApiList(res);
  } finally {
    loading.value = false;
  }
}

async function requestShift(shift) {
  actingId.value = shift.id;
  try {
    await apiPost(`/v1/shifts/${shift.id}/request`);
    await refresh();
  } finally {
    actingId.value = null;
  }
}

async function withdrawRequest(shift) {
  actingId.value = shift.id;
  try {
    await apiPost(`/v1/shifts/${shift.id}/withdraw`);
    await refresh();
  } finally {
    actingId.value = null;
  }
}

async function checkIn(shift) {
  actingId.value = shift.id;
  try {
    await apiPost(`/v1/shifts/${shift.id}/check-in`);
    await refresh();
  } finally {
    actingId.value = null;
  }
}

async function checkOut(shift) {
  actingId.value = shift.id;
  try {
    await apiPost(`/v1/shifts/${shift.id}/check-out`);
    await refresh();
  } finally {
    actingId.value = null;
  }
}

function getStatusSeverity(status) {
  switch (status) {
    case 'open': return 'info';
    case 'requested': return 'warn';
    case 'assigned': return 'success';
    case 'completed': return 'success';
    default: return 'secondary';
  }
}

onMounted(refresh);
</script>
