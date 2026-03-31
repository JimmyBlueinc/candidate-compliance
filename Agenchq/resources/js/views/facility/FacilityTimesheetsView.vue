<template>
  <div class="space-y-8">
    <UiPageHeader
      title="Timesheet Approvals"
      subtitle="Review submitted timesheets for your facility and approve hours."
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
      class="p-6"
    >
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/5">
          <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Pending</div>
          <div class="mt-2 text-2xl font-display text-white">{{ metrics.pending }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/5">
          <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Submitted</div>
          <div class="mt-2 text-2xl font-display text-amber-300">{{ metrics.submitted }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/5">
          <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Facility Approved</div>
          <div class="mt-2 text-2xl font-display" :style="{ color: primaryColor }">{{ metrics.facilityApproved }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/5">
          <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Total Hours</div>
          <div class="mt-2 text-2xl font-display text-white">{{ metrics.totalHours }}</div>
        </div>
      </div>

      <div class="mt-6">
        <DataTable
          :value="timesheets"
          :loading="loading"
          dataKey="id"
          class="p-datatable-sm"
          responsiveLayout="stack"
          breakpoint="960px"
        >
          <Column field="candidate.name" header="Candidate" sortable>
            <template #body="{ data }">
              <div class="flex flex-col">
                <span class="font-semibold text-white">{{ data.candidate?.name || 'Unknown' }}</span>
                <span class="text-xs text-[color:var(--p-text-muted-color)]">{{ data.candidate?.specialty }}</span>
              </div>
            </template>
          </Column>
          <Column field="week_start" header="Week Starting" sortable>
            <template #body="{ data }">
              <span class="text-slate-300 text-xs">{{ data.week_start }}</span>
            </template>
          </Column>
          <Column field="total_hours" header="Total Hours">
            <template #body="{ data }">
              <span class="font-bold text-white">{{ data.total_hours }} hrs</span>
            </template>
          </Column>
          <Column field="status" header="Status">
            <template #body="{ data }">
              <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
            </template>
          </Column>
          <Column header="Actions" class="text-right">
            <template #body="{ data }">
              <div class="flex items-center justify-end gap-2">
                <button
                  class="p-2 rounded-lg bg-white/5 text-slate-400 hover:bg-white/10 transition-colors"
                  title="View Details"
                  @click="viewDetails(data)"
                >
                  <i class="pi pi-eye text-xs"></i>
                </button>
                <template v-if="data.status === 'submitted'">
                  <button
                    class="p-2 rounded-lg bg-green-500/10 text-green-500 hover:bg-green-500/20 transition-colors"
                    title="Approve"
                    :disabled="actingId === data.id"
                    @click="approveTimesheet(data)"
                  >
                    <i class="pi pi-check text-xs"></i>
                  </button>
                  <button
                    class="p-2 rounded-lg bg-red-500/10 text-red-500 hover:bg-red-500/20 transition-colors"
                    title="Reject"
                    :disabled="actingId === data.id"
                    @click="rejectTimesheet(data)"
                  >
                    <i class="pi pi-times text-xs"></i>
                  </button>
                </template>
              </div>
            </template>
          </Column>
        </DataTable>
      </div>
    </UiCard>
  </div>

  <Dialog v-model:visible="detailsVisible" modal header="Timesheet Details" :style="{ width: '500px' }">
    <div v-if="selectedTimesheet" class="space-y-6 pt-2">
      <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
          <div class="text-[10px] uppercase tracking-widest text-slate-500 font-black">Candidate</div>
          <div class="text-white font-bold">{{ selectedTimesheet.candidate?.name }}</div>
        </div>
        <div>
          <div class="text-[10px] uppercase tracking-widest text-slate-500 font-black">Week Starting</div>
          <div class="text-white font-bold">{{ selectedTimesheet.week_start }}</div>
        </div>
      </div>

      <div class="border border-white/5 rounded-2xl p-4 bg-white/5">
        <div class="text-[10px] uppercase tracking-widest text-slate-500 font-black mb-3">Daily Breakdown</div>
        <div class="grid grid-cols-7 gap-1 text-center">
          <div v-for="(day, idx) in ['M','T','W','T','F','S','S']" :key="idx" class="space-y-1">
            <div class="text-[10px] text-slate-500">{{ day }}</div>
            <div class="text-xs font-bold text-white">{{ selectedTimesheet.daily_hours?.[idx] || 0 }}</div>
          </div>
        </div>
      </div>

      <div v-if="selectedTimesheet.notes" class="space-y-1">
        <div class="text-[10px] uppercase tracking-widest text-slate-500 font-black">Notes</div>
        <div class="text-xs text-slate-300 italic">"{{ selectedTimesheet.notes }}"</div>
      </div>

      <div class="flex justify-end pt-4">
        <button
          class="px-4 py-2 rounded-xl text-xs font-bold bg-white/5 border border-white/10 text-slate-300 hover:bg-white/10 transition-colors"
          @click="detailsVisible = false"
        >
          Close
        </button>
      </div>
    </div>
  </Dialog>

  <Dialog v-model:visible="rejectVisible" modal header="Reject Timesheet" :style="{ width: '520px' }">
    <div class="space-y-4 pt-2">
      <div class="text-sm text-slate-300">
        Provide a rejection reason. This is required.
      </div>

      <div class="space-y-2">
        <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Reason</label>
        <Textarea v-model="rejectReason" rows="4" class="w-full" />
        <div v-if="rejectError" class="text-xs text-red-400">{{ rejectError }}</div>
      </div>

      <div class="flex justify-end gap-2 pt-2">
        <button
          type="button"
          class="px-4 py-2 rounded-xl text-xs font-bold bg-white/5 border border-white/10 text-slate-300 hover:bg-white/10 transition-colors"
          :disabled="rejecting"
          @click="closeReject"
        >
          Cancel
        </button>
        <button
          type="button"
          class="px-4 py-2 rounded-xl text-xs font-bold bg-red-500/10 border border-red-500/30 text-red-300 hover:bg-red-500/20 transition-colors"
          :disabled="rejecting"
          @click="confirmReject"
        >
          Reject
        </button>
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { apiGet, apiPost, normalizeApiList } from '../../lib/api';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import UiCard from '../../components/ui/UiCard.vue';
import UiPageHeader from '../../components/ui/UiPageHeader.vue';

import { useBrandStore } from '../../stores/brand';

const brand = useBrandStore();
const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const timesheets = ref([]);
const loading = ref(false);
const actingId = ref(null);
const detailsVisible = ref(false);
const selectedTimesheet = ref(null);
const rejectVisible = ref(false);
const rejecting = ref(false);
const rejectReason = ref('');
const rejectError = ref('');
const rejectTarget = ref(null);

const metrics = computed(() => {
  const list = Array.isArray(timesheets.value) ? timesheets.value : [];
  let pending = 0;
  let submitted = 0;
  let facilityApproved = 0;
  let totalHoursNum = 0;

  for (const t of list) {
    const s = String(t?.status || '').toLowerCase();
    if (s === 'submitted') submitted += 1;
    if (s === 'facility_approved') facilityApproved += 1;
    if (s !== 'agency_approved' && s !== 'rejected') pending += 1;
    totalHoursNum += Number(t?.total_hours || 0) || 0;
  }

  return {
    pending,
    submitted,
    facilityApproved,
    totalHours: totalHoursNum.toFixed(1),
  };
});

async function refresh() {
  loading.value = true;
  try {
    const res = await apiGet('/v1/facility/timesheets/pending');
    timesheets.value = normalizeApiList(res);
  } finally {
    loading.value = false;
  }
}

function viewDetails(ts) {
  selectedTimesheet.value = ts;
  detailsVisible.value = true;
}

async function approveTimesheet(ts) {
  actingId.value = ts.id;
  try {
    await apiPost(`/v1/facility/timesheets/${ts.id}/approve`);
    await refresh();
  } finally {
    actingId.value = null;
  }
}

async function rejectTimesheet(ts) {
  rejectTarget.value = ts;
  rejectReason.value = '';
  rejectError.value = '';
  rejectVisible.value = true;
}

function closeReject() {
  rejectVisible.value = false;
  rejectTarget.value = null;
  rejectReason.value = '';
  rejectError.value = '';
}

async function confirmReject() {
  const ts = rejectTarget.value;
  if (!ts?.id) return;

  const reason = String(rejectReason.value || '').trim();
  if (!reason) {
    rejectError.value = 'Rejection reason is required.';
    return;
  }

  rejecting.value = true;
  rejectError.value = '';
  actingId.value = ts.id;
  try {
    await apiPost(`/v1/facility/timesheets/${ts.id}/reject`, { reason });
    closeReject();
    await refresh();
  } catch (e) {
    rejectError.value = e?.response?.data?.message || e?.message || 'Failed to reject timesheet.';
  } finally {
    rejecting.value = false;
    actingId.value = null;
  }
}

function getStatusSeverity(status) {
  switch (status) {
    case 'draft':
      return 'info';
    case 'submitted':
      return 'warn';
    case 'facility_approved':
      return 'info';
    case 'agency_approved':
      return 'success';
    case 'rejected':
      return 'danger';
    default:
      return 'secondary';
  }
}

onMounted(refresh);
</script>
