<template>
  <div class="space-y-6">
    <UiPageHeader 
      title="Shift Management" 
      subtitle="Manage facility shifts and candidate requests"
    >
      <template #actions>
        <div class="flex items-center gap-2">
          <Button 
            label="Create Shift" 
            icon="pi pi-plus" 
            size="small"
            @click="openCreateModal" 
          />
          <Button 
            label="Refresh" 
            icon="pi pi-refresh" 
            size="small"
            variant="outline"
            :loading="loading"
            @click="refresh" 
          />
        </div>
      </template>
    </UiPageHeader>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <UiStatCard 
        label="Shifts Today" 
        :value="metrics.today" 
        :icon="Calendar"
      />
      <UiStatCard 
        label="Open" 
        :value="metrics.open" 
        :icon="DoorOpen"
        variant="primary"
      />
      <UiStatCard 
        label="Requested" 
        :value="metrics.requested" 
        :icon="Bell"
        class="text-amber-400"
      />
      <UiStatCard 
        label="Assigned" 
        :value="metrics.assigned" 
        :icon="UserCheck"
        class="text-emerald-400"
      />
    </div>

    <UiCard>
      <div v-if="error" class="mb-4">
        <Message severity="error" :closable="false">{{ error }}</Message>
      </div>
      
      <DataTable 
        :value="shifts" 
        :loading="loading" 
        dataKey="id"
        class="p-datatable-sm"
        stripedRows
        responsiveLayout="scroll"
      >
        <Column field="facility" header="Facility" sortable>
          <template #body="{ data }">
            <span class="font-semibold text-white">{{ data.facility }}</span>
          </template>
        </Column>
        <Column field="date" header="Date" sortable>
          <template #body="{ data }">
            <span class="text-slate-300 text-sm">{{ data.date }}</span>
          </template>
        </Column>
        <Column header="Time">
          <template #body="{ data }">
            <div class="flex items-center gap-1.5 text-slate-400 text-xs">
              <Clock class="w-3 h-3" />
              <span>{{ data.start_time }} - {{ data.end_time }}</span>
            </div>
          </template>
        </Column>
        <Column field="status" header="Status">
          <template #body="{ data }">
            <UiBadge :variant="getBadgeVariant(data.status)">{{ data.status }}</UiBadge>
          </template>
        </Column>
        <Column header="Candidate">
          <template #body="{ data }">
            <div v-if="data.assigned_candidate || data.request_candidate" class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-full bg-white/5 border border-white/10 flex items-center justify-center overflow-hidden">
                <User class="w-4 h-4 text-slate-400" />
              </div>
              <div class="flex flex-col min-w-0">
                <span class="text-white text-sm font-medium truncate">{{ resolveCandidate(data)?.name }}</span>
                <span class="text-[10px] text-slate-500 truncate">{{ resolveCandidate(data)?.specialty }}</span>
              </div>
            </div>
            <span v-else class="text-xs italic text-slate-500">Unassigned</span>
          </template>
        </Column>
        <Column header="Actions" class="text-right">
          <template #body="{ data }">
            <div class="flex items-center justify-end gap-1">
              <Button
                v-if="resolveCandidate(data)?.user_id"
                icon="pi pi-comments"
                severity="secondary"
                text
                rounded
                size="small"
                v-tooltip.top="'Message Candidate'"
                @click="messageShiftCandidate(data)"
              />
              <template v-if="data.status === 'requested'">
                <Button 
                  icon="pi pi-check" 
                  severity="success" 
                  text 
                  rounded 
                  size="small"
                  v-tooltip.top="'Approve'"
                  @click="approveRequest(data)"
                />
                <Button 
                  icon="pi pi-times" 
                  severity="danger" 
                  text 
                  rounded 
                  size="small"
                  v-tooltip.top="'Reject'"
                  @click="rejectRequest(data)"
                />
              </template>
              <Button 
                v-if="['open', 'requested', 'assigned'].includes(data.status)"
                icon="pi pi-ban" 
                severity="secondary" 
                text 
                rounded 
                size="small"
                v-tooltip.top="'Cancel'"
                @click="cancelShift(data)"
              />
              <Button 
                v-if="data.status === 'assigned'"
                icon="pi pi-check-circle" 
                severity="info" 
                text 
                rounded 
                size="small"
                v-tooltip.top="'Complete'"
                @click="completeShift(data)"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </UiCard>

    <ShiftFormModal 
      :show="showCreateModal" 
      :loading="acting"
      :assignments="activeAssignments"
      :templates="shiftTemplates"
      :primary-color="primaryColor"
      @close="showCreateModal = false"
      @submit="handleCreateShift"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { apiGet, apiPost, normalizeApiList } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';
import { useRouter } from 'vue-router';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Message from 'primevue/message';
import Button from 'primevue/button';
import UiPageHeader from '../../components/ui/UiPageHeader.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiStatCard from '../../components/ui/UiStatCard.vue';
import UiBadge from '../../components/ui/UiBadge.vue';
import ShiftFormModal from '../../components/shifts/ShiftFormModal.vue';
import { 
  Calendar, 
  Clock, 
  User, 
  UserCheck, 
  Bell, 
  DoorOpen,
  CheckCircle2,
  XCircle
} from 'lucide-vue-next';

const shifts = ref([]);
const router = useRouter();
const loading = ref(false);
const acting = ref(false);
const showCreateModal = ref(false);
const error = ref('');
const shiftTemplates = ref([]);
const activeAssignments = ref([]);

const metrics = computed(() => {
  const list = Array.isArray(shifts.value) ? shifts.value : [];
  const today = new Date();
  const todayKey = new Date(today.getFullYear(), today.getMonth(), today.getDate()).toISOString().slice(0, 10);

  let todayCount = 0;
  let open = 0;
  let requested = 0;
  let assigned = 0;

  for (const s of list) {
    const status = String(s?.status || '').toLowerCase();
    if (status === 'open') open += 1;
    if (status === 'requested') requested += 1;
    if (status === 'assigned') assigned += 1;

    const dateKey = String(s?.date || '').slice(0, 10);
    if (dateKey && dateKey === todayKey) todayCount += 1;
  }

  return {
    today: todayCount,
    open,
    requested,
    assigned,
  };
});

function formatAssignmentLabel(a) {
  const facility = a?.facility?.name || a?.facility_name || '';
  const cand = a?.candidate?.name || '';
  const dates = [a?.start_date, a?.end_date].filter(Boolean).join(' → ');
  const bits = [cand, facility, dates].filter(Boolean).join(' • ');
  return bits || `Assignment #${a?.id}`;
}

async function refresh() {
  loading.value = true;
  try {
    error.value = '';
    const res = await apiGet('/v1/shifts');
    shifts.value = normalizeApiList(res);
  } finally {
    loading.value = false;
  }
}

async function loadCreateDependencies() {
  try {
    const [tRes, aRes] = await Promise.all([
      apiGet('/v1/shifts/templates'),
      apiGet('/v1/shifts/assignments/active')
    ]);

    shiftTemplates.value = normalizeApiList(tRes);
    const assignments = normalizeApiList(aRes);
    activeAssignments.value = assignments.map((a) => ({
      ...a,
      label: formatAssignmentLabel(a)
    }));
  } catch (e) {
    shiftTemplates.value = [];
    activeAssignments.value = [];
    error.value = e?.response?.data?.message || e?.message || 'Failed to load shift creation options.';
  }
}

function openCreateModal() {
  error.value = '';
  showCreateModal.value = true;
  void loadCreateDependencies();
}

async function handleCreateShift(formData) {
  acting.value = true;
  try {
    error.value = '';

    try {
      const previewRes = await apiPost('/v1/shifts/availability/preview', {
        shift_template_id: formData.shift_template_id,
        assignment_id: formData.assignment_id,
        date: formData.date
      });
      const preview = previewRes?.data ?? previewRes;
      const status = String(preview?.status || '');
      const hard = Boolean(preview?.hard_block);

      if (hard) {
        const msg = status === 'declared_unavailable'
          ? 'Warning: The worker has declared they are unavailable for this shift window. Create the shift anyway?'
          : 'Warning: The worker has a blackout conflict for this shift window. Create the shift anyway?';
        const ok = confirm(msg);
        if (!ok) return;
      } else if (status === 'outside_declared' || status === 'no_declared') {
        const ok = confirm('Warning: This shift is outside the worker\'s declared availability (or none declared). Create the shift anyway?');
        if (!ok) return;
      }
    } catch (e) {
      // Ignore preview failures; shift create will still be validated by backend rules.
    }

    await apiPost('/v1/shifts', {
      shift_template_id: formData.shift_template_id,
      assignment_id: formData.assignment_id,
      date: formData.date
    });
    showCreateModal.value = false;
    await refresh();
  } catch (e) {
    const payload = e?.response?.data;
    const msg = payload?.message || e?.message || 'Failed to create shift.';
    const errors = payload?.errors;
    if (errors && typeof errors === 'object') {
      const first = Object.values(errors).flat()?.[0];
      error.value = first ? `${msg} ${first}` : msg;
    } else {
      error.value = msg;
    }
  } finally {
    acting.value = false;
  }
}

async function approveRequest(shift) {
  if (!shift.request_id) return;
  acting.value = true;
  try {
    error.value = '';

    try {
      const prevRes = await apiPost('/v1/shifts/availability/preview-shift', { shift_id: shift.id });
      const preview = prevRes?.data ?? prevRes;
      const status = String(preview?.status || '');
      const hard = Boolean(preview?.hard_block);

      if (hard) {
        const msg = status === 'declared_unavailable'
          ? 'Cannot approve: the worker has declared they are unavailable for this shift.'
          : 'Cannot approve: the worker has a blackout conflict for this shift.';
        error.value = msg;
        return;
      }

      if (status === 'outside_declared' || status === 'no_declared') {
        const ok = confirm('Warning: This shift is outside the worker\'s declared availability (or none declared). Approve anyway?');
        if (!ok) {
          return;
        }
      }
    } catch (e) {
      // If preview fails, fall through and let backend enforce hard blocks.
    }

    await apiPost(`/v1/shifts/requests/${shift.request_id}/approve`);
    await refresh();
  } catch (e) {
    const payload = e?.response?.data;
    error.value = payload?.message || e?.message || 'Failed to approve request.';
  } finally {
    acting.value = false;
  }
}

async function rejectRequest(shift) {
  if (!shift.request_id) return;
  const reason = window.prompt('Please provide a reason for rejecting this request:');
  if (reason === null) return;
  if (!String(reason).trim()) {
    error.value = 'Rejection reason is required.';
    return;
  }
  acting.value = true;
  try {
    error.value = '';
    await apiPost(`/v1/shifts/requests/${shift.request_id}/reject`, {
      reason: String(reason).trim(),
    });
    await refresh();
  } catch (e) {
    const payload = e?.response?.data;
    error.value = payload?.message || e?.message || 'Failed to reject request.';
  } finally {
    acting.value = false;
  }
}

async function cancelShift(shift) {
  if (!confirm('Are you sure you want to cancel this shift?')) return;
  acting.value = true;
  try {
    await apiPost(`/v1/shifts/${shift.id}/cancel`);
    await refresh();
  } finally {
    acting.value = false;
  }
}

async function completeShift(shift) {
  acting.value = true;
  try {
    await apiPost(`/v1/shifts/${shift.id}/complete`);
    await refresh();
  } finally {
    acting.value = false;
  }
}

function messageShiftCandidate(shift) {
  const recipientId = Number(resolveCandidate(shift)?.user_id || 0);
  if (!recipientId) return;
  router.push({ name: 'dashboard.messages', query: { recipient_id: String(recipientId) } });
}

function resolveCandidate(shift) {
  return shift?.assigned_candidate || shift?.request_candidate || null;
}

function getBadgeVariant(status) {
  switch (status) {
    case 'open': return 'info';
    case 'requested': return 'warning';
    case 'assigned': return 'success';
    case 'completed': return 'success';
    case 'cancelled': return 'danger';
    default: return 'outline';
  }
}

onMounted(refresh);
</script>
