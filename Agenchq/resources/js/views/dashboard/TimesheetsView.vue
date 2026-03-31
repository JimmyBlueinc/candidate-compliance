<template>
  <div class="space-y-6">
    <UiPageHeader 
      title="Timesheet Approvals" 
      subtitle="Review facility-approved timesheets and approve for invoicing"
    >
      <template #actions>
        <Button 
          label="Refresh" 
          icon="pi pi-refresh" 
          size="small"
          variant="outline"
          :loading="loading"
          @click="refresh" 
        />
      </template>
    </UiPageHeader>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <UiStatCard 
        label="Pending" 
        :value="metrics.pending" 
        :icon="Clock"
      />
      <UiStatCard 
        label="Facility Approved" 
        :value="metrics.facilityApproved" 
        :icon="Building"
        class="text-amber-400"
      />
      <UiStatCard 
        label="Ready for Invoicing" 
        :value="metrics.readyForInvoicing" 
        :icon="ReceiptText"
        variant="primary"
      />
      <UiStatCard 
        label="Total Hours" 
        :value="metrics.totalHours" 
        :icon="Timer"
      />
    </div>

    <UiCard>
      <DataTable 
        :value="timesheets" 
        :loading="loading" 
        dataKey="id"
        class="p-datatable-sm"
        stripedRows
        responsiveLayout="scroll"
      >
        <Column field="candidate.name" header="Candidate" sortable>
          <template #body="{ data }">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-full bg-white/5 border border-white/10 flex items-center justify-center overflow-hidden">
                <User class="w-4 h-4 text-slate-400" />
              </div>
              <div class="flex flex-col min-w-0">
                <span class="text-white text-sm font-medium truncate">{{ data.candidate?.name || 'Unknown' }}</span>
                <span class="text-[10px] text-slate-500 truncate">{{ data.candidate?.specialty }}</span>
              </div>
            </div>
          </template>
        </Column>
        <Column field="facility" header="Facility" sortable>
          <template #body="{ data }">
            <div class="flex items-center gap-1.5 text-slate-300 text-sm">
              <Building class="w-3.5 h-3.5 opacity-50" />
              <span>{{ data.facility }}</span>
            </div>
          </template>
        </Column>
        <Column field="week_start" header="Week Starting" sortable>
          <template #body="{ data }">
            <span class="text-slate-400 text-xs">{{ data.week_start }}</span>
          </template>
        </Column>
        <Column field="total_hours" header="Total Hours">
          <template #body="{ data }">
            <div class="flex items-center gap-1">
              <span class="font-bold text-white">{{ data.total_hours }}</span>
              <span class="text-[10px] text-slate-500 font-medium uppercase">hrs</span>
            </div>
          </template>
        </Column>
        <Column field="status" header="Status">
          <template #body="{ data }">
            <UiBadge :variant="getBadgeVariant(data.status)">{{ data.status.replace('_', ' ') }}</UiBadge>
          </template>
        </Column>
        <Column header="Actions" class="text-right">
          <template #body="{ data }">
            <div class="flex items-center justify-end gap-1">
              <Button 
                v-if="data.candidate?.user_id"
                icon="pi pi-comments" 
                severity="secondary" 
                text 
                rounded 
                size="small"
                v-tooltip.top="'Message Candidate'"
                @click="messageCandidate(data)"
              />
              <Button 
                icon="pi pi-eye" 
                severity="secondary" 
                text 
                rounded 
                size="small"
                v-tooltip.top="'View Details'"
                @click="viewDetails(data)"
              />
              <template v-if="data.status === 'facility_approved'">
                <Button 
                  icon="pi pi-check" 
                  severity="success" 
                  text 
                  rounded 
                  size="small"
                  v-tooltip.top="'Approve'"
                  :loading="actingId === data.id"
                  @click="approveTimesheet(data)"
                />
                <Button 
                  icon="pi pi-times" 
                  severity="danger" 
                  text 
                  rounded 
                  size="small"
                  v-tooltip.top="'Reject'"
                  :loading="actingId === data.id"
                  @click="rejectTimesheet(data)"
                />
              </template>
            </div>
          </template>
        </Column>
      </DataTable>
    </UiCard>

    <Dialog v-model:visible="detailsVisible" modal header="Timesheet Details" :style="{ width: 'min(500px, 95vw)' }">
      <div v-if="selectedTimesheet" class="space-y-6">
        <div class="grid grid-cols-2 gap-6">
          <div class="space-y-1">
            <div class="text-[10px] uppercase tracking-widest text-slate-500 font-black">Candidate</div>
            <div class="flex items-center gap-2">
              <User class="w-4 h-4 text-slate-400" />
              <div class="text-white font-bold">{{ selectedTimesheet.candidate?.name }}</div>
            </div>
          </div>
          <div class="space-y-1">
            <div class="text-[10px] uppercase tracking-widest text-slate-500 font-black">Facility</div>
            <div class="flex items-center gap-2">
              <Building class="w-4 h-4 text-slate-400" />
              <div class="text-white font-bold">{{ selectedTimesheet.facility }}</div>
            </div>
          </div>
        </div>

        <div class="bg-white/5 border border-white/10 rounded-2xl p-5">
          <div class="flex items-center gap-2 mb-4">
            <Timer class="w-4 h-4 text-slate-400" />
            <span class="text-[10px] uppercase tracking-widest text-slate-500 font-black">Daily Breakdown</span>
          </div>
          <div class="grid grid-cols-7 gap-2 text-center">
            <div v-for="(day, idx) in ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']" :key="idx" class="space-y-2">
              <div class="text-[10px] text-slate-500 font-medium">{{ day }}</div>
              <div class="py-2 bg-white/[0.03] rounded-lg text-sm font-bold text-white border border-white/5">
                {{ selectedTimesheet.daily_hours?.[idx] || 0 }}
              </div>
            </div>
          </div>
        </div>

        <div v-if="selectedTimesheet.notes" class="space-y-2">
          <div class="text-[10px] uppercase tracking-widest text-slate-500 font-black">Notes</div>
          <div class="p-4 rounded-xl bg-white/5 border border-white/5 text-sm text-slate-300 italic leading-relaxed">
            "{{ selectedTimesheet.notes }}"
          </div>
        </div>

        <div class="flex justify-end pt-2">
          <Button label="Close" severity="secondary" text @click="detailsVisible = false" />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { apiGet, apiPost, normalizeApiList } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';
import { useRouter } from 'vue-router';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import UiPageHeader from '../../components/ui/UiPageHeader.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiStatCard from '../../components/ui/UiStatCard.vue';
import UiBadge from '../../components/ui/UiBadge.vue';
import { 
  Clock, 
  Building, 
  Timer, 
  ReceiptText, 
  User,
  FileText
} from 'lucide-vue-next';

const brand = useBrandStore();
const router = useRouter();
const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');

const timesheets = ref([]);
const loading = ref(false);
const actingId = ref(null);
const detailsVisible = ref(false);
const selectedTimesheet = ref(null);

const metrics = computed(() => {
  const list = Array.isArray(timesheets.value) ? timesheets.value : [];
  let pending = 0;
  let facilityApproved = 0;
  let readyForInvoicing = 0;
  let totalHoursNum = 0;

  for (const t of list) {
    const s = String(t?.status || '').toLowerCase();
    if (s === 'facility_approved') facilityApproved += 1;
    if (s === 'facility_approved') readyForInvoicing += 1;
    if (s !== 'agency_approved' && s !== 'rejected') pending += 1;
    totalHoursNum += Number(t?.total_hours || 0) || 0;
  }

  return {
    pending,
    facilityApproved,
    readyForInvoicing,
    totalHours: totalHoursNum.toFixed(1),
  };
});

async function refresh() {
  loading.value = true;
  try {
    const res = await apiGet('/v1/timesheets/pending');
    timesheets.value = normalizeApiList(res);
  } finally {
    loading.value = false;
  }
}

function viewDetails(ts) {
  selectedTimesheet.value = ts;
  detailsVisible.value = true;
}

function messageCandidate(ts) {
  const recipientId = Number(ts?.candidate?.user_id || 0);
  if (!recipientId) return;
  router.push({ name: 'dashboard.messages', query: { recipient_id: String(recipientId) } });
}

async function approveTimesheet(ts) {
  actingId.value = ts.id;
  try {
    await apiPost(`/v1/timesheets/${ts.id}/approve`);
    await refresh();
  } finally {
    actingId.value = null;
  }
}

async function rejectTimesheet(ts) {
  const reason = prompt('Please enter a rejection reason:');
  if (reason === null) return;
  
  actingId.value = ts.id;
  try {
    await apiPost(`/v1/timesheets/${ts.id}/reject`, { reason });
    await refresh();
  } finally {
    actingId.value = null;
  }
}

function getBadgeVariant(status) {
  switch (status) {
    case 'draft': return 'outline';
    case 'submitted': return 'warning';
    case 'facility_approved': return 'info';
    case 'agency_approved': return 'success';
    case 'rejected': return 'danger';
    default: return 'outline';
  }
}

onMounted(refresh);
</script>
