<template>
  <div class="space-y-6">
    <div class="glass-dark rounded-[32px] p-8 border border-white/5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="font-display text-2xl text-white">Job Orders</h2>
          <p class="text-sm text-[color:var(--p-text-muted-color)] mt-1">Create, publish, and manage job orders.</p>
        </div>

        <div class="flex items-center gap-2">
          <Button type="button" label="Refresh" size="small" outlined :loading="loading" @click="refresh" />
          <Button type="button" label="New Job" size="small" @click="openCreate" />
        </div>
      </div>

      <div v-if="error" class="mt-4">
        <Message severity="error">{{ error }}</Message>
      </div>

      <div class="mt-6">
        <DataTable :value="rows" :loading="loading" paginator :rows="12" responsiveLayout="scroll">
          <Column field="published" header="Published" style="width: 130px">
            <template #body="{ data }">
              <Button
                type="button"
                size="small"
                :label="data.published ? 'Published' : 'Draft'"
                :severity="data.published ? 'success' : 'secondary'"
                outlined
                @click="togglePublished(data)"
              />
            </template>
          </Column>

          <Column field="facility_name" header="Facility" />
          <Column field="specialty" header="Specialty" style="width: 140px" />

          <Column field="bill_rate" header="Bill" style="width: 110px">
            <template #body="{ data }">{{ money(data.bill_rate) }}</template>
          </Column>

          <Column field="pay_rate" header="Pay" style="width: 110px">
            <template #body="{ data }">{{ money(data.pay_rate) }}</template>
          </Column>

          <Column field="start_date" header="Start" style="width: 130px">
            <template #body="{ data }">{{ dateText(data.start_date) }}</template>
          </Column>

          <Column field="work_mode" header="Mode" style="width: 120px">
            <template #body="{ data }">{{ formatWorkMode(data.work_mode) }}</template>
          </Column>

          <Column field="status" header="Status" style="width: 120px" />

          <Column header="Actions" style="width: 140px">
            <template #body="{ data }">
              <div class="flex items-center gap-2 justify-end">
                <Button type="button" icon="pi pi-pencil" size="small" severity="secondary" text rounded @click="openEdit(data)" />
                <Button type="button" icon="pi pi-trash" size="small" severity="danger" text rounded @click="confirmDelete(data)" />
              </div>
            </template>
          </Column>

          <template #empty>
            <div class="py-6 text-[color:var(--p-text-muted-color)]">No job orders yet.</div>
          </template>
        </DataTable>
      </div>
    </div>

    <Dialog v-model:visible="modalOpen" modal :header="editingId ? 'Edit Job Order' : 'Create Job Order'" :style="{ width: 'min(760px, 96vw)' }">
      <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Title</div>
            <InputText v-model="form.title" class="w-full mt-1" />
          </div>

          <div>
            <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Facility Name</div>
            <InputText v-model="form.facility_name" class="w-full mt-1" />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Specialty</div>
            <Dropdown v-model="form.specialty" :options="specialtyOptions" class="w-full mt-1" placeholder="Select" />
          </div>

          <div>
            <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Work Mode</div>
            <Dropdown v-model="form.work_mode" :options="workModeOptions" optionLabel="label" optionValue="value" class="w-full mt-1" placeholder="Select" />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div>
            <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Bill Rate</div>
            <InputText v-model="form.bill_rate" class="w-full mt-1" />
          </div>

          <div>
            <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Pay Rate</div>
            <InputText v-model="form.pay_rate" class="w-full mt-1" />
          </div>

          <div>
            <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Stipend (Weekly)</div>
            <InputText v-model="form.stipend_weekly" class="w-full mt-1" />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Start Date</div>
            <InputText v-model="form.start_date" class="w-full mt-1" placeholder="YYYY-MM-DD" />
          </div>

          <div>
            <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Status</div>
            <Dropdown v-model="form.status" :options="statusOptions" class="w-full mt-1" />
          </div>
        </div>

        <div class="flex items-center justify-between gap-3 pt-2">
          <Button type="button" :label="form.published ? 'Published' : 'Draft'" :severity="form.published ? 'success' : 'secondary'" outlined size="small" @click="form.published = !form.published" />

          <div class="flex items-center gap-2">
            <Button type="button" label="Cancel" severity="secondary" outlined size="small" @click="closeModal" />
            <Button type="button" label="Save" size="small" :loading="saving" @click="save" />
          </div>
        </div>

        <div v-if="formError" class="text-sm text-red-400">{{ formError }}</div>
      </div>
    </Dialog>

    <Dialog v-model:visible="deleteOpen" modal header="Delete Job" :style="{ width: 'min(520px, 96vw)' }">
      <div class="space-y-4">
        <div class="text-sm text-[color:var(--p-text-muted-color)]">
          This will permanently delete <span class="font-semibold text-white">{{ deleteTarget?.title || 'this job' }}</span>.
        </div>

        <div class="flex items-center justify-end gap-2">
          <Button type="button" label="Cancel" severity="secondary" outlined size="small" @click="deleteOpen = false" />
          <Button type="button" label="Delete" severity="danger" size="small" :loading="deleting" @click="doDelete" />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { apiDelete, apiGet, apiPost, apiPut, normalizeApiList } from '../../lib/api';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';

const loading = ref(false);
const saving = ref(false);
const deleting = ref(false);
const error = ref('');
const formError = ref('');

const rows = ref([]);

const modalOpen = ref(false);
const editingId = ref(null);

const deleteOpen = ref(false);
const deleteTarget = ref(null);

const specialtyOptions = ['ICU', 'ER', 'MedSurg', 'Telemetry', 'OR', 'L&D', 'PCU', 'Stepdown', 'Home Health', 'Other'];
const statusOptions = ['open', 'filled', 'closed'];

const workModeOptions = [
    { label: 'On-site', value: 'on_site' },
    { label: 'Remote', value: 'remote' },
];

const form = reactive({
    title: '',
    facility_name: '',
    specialty: null,
    bill_rate: '',
    pay_rate: '',
    stipend_weekly: '',
    start_date: '',
    work_mode: 'on_site',
    published: false,
    status: 'open',
});

function money(v) {
    if (v === null || v === undefined || v === '') return '—';
    const n = Number(v);
    if (Number.isNaN(n)) return '—';
    return `$${n.toFixed(2)}`;
}

function dateText(v) {
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

function resetForm() {
    editingId.value = null;
    form.title = '';
    form.facility_name = '';
    form.specialty = null;
    form.bill_rate = '';
    form.pay_rate = '';
    form.stipend_weekly = '';
    form.start_date = '';
    form.work_mode = 'on_site';
    form.published = false;
    form.status = 'open';
    formError.value = '';
}

function closeModal() {
    modalOpen.value = false;
    resetForm();
}

function openCreate() {
    resetForm();
    modalOpen.value = true;
}

function openEdit(row) {
    editingId.value = row.id;
    form.title = row.title || '';
    form.facility_name = row.facility_name || '';
    form.specialty = row.specialty || null;
    form.bill_rate = row.bill_rate ?? '';
    form.pay_rate = row.pay_rate ?? '';
    form.stipend_weekly = row.stipend_weekly ?? '';
    form.start_date = row.start_date || '';
    form.work_mode = row.work_mode || 'on_site';
    form.published = Boolean(row.published);
    form.status = row.status || 'open';
    modalOpen.value = true;
}

function toPayload() {
    const payload = {
        title: String(form.title || '').trim(),
        facility_name: String(form.facility_name || '').trim(),
        specialty: form.specialty || null,
        bill_rate: form.bill_rate === '' ? null : Number(form.bill_rate),
        pay_rate: form.pay_rate === '' ? null : Number(form.pay_rate),
        stipend_weekly: form.stipend_weekly === '' ? null : Number(form.stipend_weekly),
        start_date: String(form.start_date || '').trim() || null,
        work_mode: form.work_mode || null,
        published: Boolean(form.published),
        status: form.status || 'open',
    };

    if (payload.start_date === '') payload.start_date = null;
    if (payload.start_date === null) delete payload.start_date;
    return payload;
}

async function refresh() {
    loading.value = true;
    error.value = '';
    try {
        const res = await apiGet('/v1/job-orders');
        rows.value = normalizeApiList(res);
    } catch (e) {
        error.value = e?.message || 'Failed to load.';
    } finally {
        loading.value = false;
    }
}

async function save() {
    formError.value = '';
    saving.value = true;
    try {
        const payload = toPayload();
        if (editingId.value) {
            await apiPut(`/v1/job-orders/${editingId.value}`, payload);
        } else {
            await apiPost('/v1/job-orders', payload);
        }
        closeModal();
        await refresh();
    } catch (e) {
        formError.value = e?.message || 'Failed to save.';
    } finally {
        saving.value = false;
    }
}

async function togglePublished(row) {
    try {
        await apiPut(`/v1/job-orders/${row.id}`, { published: !Boolean(row.published) });
        await refresh();
    } catch (e) {
        error.value = e?.message || 'Failed to update.';
    }
}

function confirmDelete(row) {
    deleteTarget.value = row;
    deleteOpen.value = true;
}

async function doDelete() {
    if (!deleteTarget.value?.id) return;
    deleting.value = true;
    try {
        await apiDelete(`/v1/job-orders/${deleteTarget.value.id}`);
        deleteOpen.value = false;
        deleteTarget.value = null;
        await refresh();
    } catch (e) {
        error.value = e?.message || 'Failed to delete.';
    } finally {
        deleting.value = false;
    }
}

onMounted(async () => {
    await refresh();
});
</script>
