<template>
  <div class="space-y-6">
    <div class="glass-dark rounded-[32px] p-8 border border-white/5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="font-display text-2xl text-white">Job Sources</h2>
          <p class="text-sm text-[color:var(--p-text-muted-color)] mt-1">Manage external connectors and run job sync.</p>
        </div>

        <div class="flex items-center gap-2">
          <Button type="button" label="Refresh" size="small" outlined :loading="loading" @click="refresh" />
          <Button type="button" label="New Source" size="small" @click="openCreate" />
        </div>
      </div>

      <div v-if="error" class="mt-4">
        <Message severity="error">{{ error }}</Message>
      </div>

      <div class="mt-6">
        <DataTable :value="rows" :loading="loading" paginator :rows="10" responsiveLayout="scroll">
          <Column field="enabled" header="Enabled" style="width: 120px">
            <template #body="{ data }">
              <Button
                type="button"
                size="small"
                :label="data.enabled ? 'On' : 'Off'"
                :severity="data.enabled ? 'success' : 'secondary'"
                outlined
                @click="toggleEnabled(data)"
              />
            </template>
          </Column>

          <Column field="source_key" header="Key" style="width: 180px" />
          <Column field="name" header="Name" />
          <Column field="type" header="Type" style="width: 110px" />

          <Column field="last_synced_at" header="Last Sync" style="width: 170px">
            <template #body="{ data }">{{ dateTime(data.last_synced_at) }}</template>
          </Column>

          <Column field="last_run_upserts" header="Upserts" style="width: 110px">
            <template #body="{ data }">{{ data.last_run_upserts ?? '—' }}</template>
          </Column>

          <Column field="archive_missing" header="Archive" style="width: 120px">
            <template #body="{ data }">
              <span class="text-sm text-[color:var(--p-text-muted-color)]">{{ data.archive_missing ? 'Yes' : 'No' }}</span>
            </template>
          </Column>

          <Column header="Actions" style="width: 190px">
            <template #body="{ data }">
              <div class="flex items-center gap-2 justify-end">
                <Button type="button" icon="pi pi-play" size="small" severity="success" text rounded :loading="runningId === data.id" @click="runSync(data)" />
                <Button type="button" icon="pi pi-pencil" size="small" severity="secondary" text rounded @click="openEdit(data)" />
                <Button type="button" icon="pi pi-trash" size="small" severity="danger" text rounded @click="confirmDelete(data)" />
              </div>
            </template>
          </Column>

          <template #empty>
            <div class="py-6 text-[color:var(--p-text-muted-color)]">No job sources yet.</div>
          </template>
        </DataTable>
      </div>

      <div v-if="selectedError" class="mt-4">
        <Message severity="warn">{{ selectedError }}</Message>
      </div>
    </div>

    <Dialog v-model:visible="modalOpen" modal :header="editingId ? 'Edit Job Source' : 'Create Job Source'" :style="{ width: 'min(760px, 96vw)' }">
      <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Source Key</div>
            <InputText v-model="form.source_key" class="w-full mt-1" :disabled="Boolean(editingId)" />
          </div>

          <div>
            <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Name</div>
            <InputText v-model="form.name" class="w-full mt-1" />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Type</div>
            <Dropdown v-model="form.type" :options="typeOptions" class="w-full mt-1" />
          </div>

          <div class="flex items-end gap-2">
            <div class="flex-1">
              <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Enabled</div>
              <Dropdown v-model="form.enabled" :options="enabledOptions" optionLabel="label" optionValue="value" class="w-full mt-1" />
            </div>

            <div class="flex-1">
              <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Archive Missing</div>
              <Dropdown v-model="form.archive_missing" :options="enabledOptions" optionLabel="label" optionValue="value" class="w-full mt-1" />
            </div>
          </div>
        </div>

        <div>
          <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">URL</div>
          <InputText v-model="form.url" class="w-full mt-1" />
        </div>

        <div>
          <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Mapping (JSON)</div>
          <InputText v-model="form.mapping_json" class="w-full mt-1" placeholder='{"title":"title","external_id":"id","facility_name":"facility"}' />
          <div class="text-xs text-[color:var(--p-text-muted-color)] mt-2">
            Keys should match: external_id,title,facility_name,specialty,pay_rate,bill_rate,stipend_weekly,work_mode,start_date
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
          <Button type="button" label="Cancel" severity="secondary" outlined size="small" @click="closeModal" />
          <Button type="button" label="Save" size="small" :loading="saving" @click="save" />
        </div>

        <div v-if="formError" class="text-sm text-red-400">{{ formError }}</div>
      </div>
    </Dialog>

    <Dialog v-model:visible="deleteOpen" modal header="Delete Source" :style="{ width: 'min(520px, 96vw)' }">
      <div class="space-y-4">
        <div class="text-sm text-[color:var(--p-text-muted-color)]">
          This will permanently delete <span class="font-semibold text-white">{{ deleteTarget?.name || 'this source' }}</span>.
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
const selectedError = ref('');

const runningId = ref(null);

const rows = ref([]);

const modalOpen = ref(false);
const editingId = ref(null);

const deleteOpen = ref(false);
const deleteTarget = ref(null);

const typeOptions = ['rss', 'json'];
const enabledOptions = [
    { label: 'Yes', value: true },
    { label: 'No', value: false },
];

const form = reactive({
    source_key: '',
    name: '',
    type: 'json',
    url: '',
    enabled: true,
    archive_missing: false,
    mapping_json: '',
});

function dateTime(v) {
    if (!v) return '—';
    const d = new Date(v);
    if (Number.isNaN(d.getTime())) return String(v);
    return d.toLocaleString();
}

function resetForm() {
    editingId.value = null;
    form.source_key = '';
    form.name = '';
    form.type = 'json';
    form.url = '';
    form.enabled = true;
    form.archive_missing = false;
    form.mapping_json = '';
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
    form.source_key = row.source_key || '';
    form.name = row.name || '';
    form.type = row.type || 'json';
    form.url = row.url || '';
    form.enabled = Boolean(row.enabled);
    form.archive_missing = Boolean(row.archive_missing);
    form.mapping_json = row.mapping ? JSON.stringify(row.mapping) : '';
    modalOpen.value = true;
    selectedError.value = row.last_error || '';
}

function parseMapping() {
    const raw = String(form.mapping_json || '').trim();
    if (!raw) return null;
    try {
        const parsed = JSON.parse(raw);
        if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) return parsed;
        return null;
    } catch {
        return '__invalid__';
    }
}

async function refresh() {
    loading.value = true;
    error.value = '';
    selectedError.value = '';
    try {
        const res = await apiGet('/v1/job-sources');
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
        const mapping = parseMapping();
        if (mapping === '__invalid__') {
            formError.value = 'Mapping must be valid JSON.';
            return;
        }

        const payload = {
            source_key: String(form.source_key || '').trim(),
            name: String(form.name || '').trim(),
            type: form.type,
            url: String(form.url || '').trim(),
            enabled: Boolean(form.enabled),
            archive_missing: Boolean(form.archive_missing),
            mapping,
        };

        if (editingId.value) {
            delete payload.source_key;
            await apiPut(`/v1/job-sources/${editingId.value}`, payload);
        } else {
            await apiPost('/v1/job-sources', payload);
        }

        closeModal();
        await refresh();
    } catch (e) {
        formError.value = e?.response?.data?.message || e?.message || 'Failed to save.';
    } finally {
        saving.value = false;
    }
}

async function toggleEnabled(row) {
    try {
        await apiPut(`/v1/job-sources/${row.id}`, { enabled: !Boolean(row.enabled) });
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
        await apiDelete(`/v1/job-sources/${deleteTarget.value.id}`);
        deleteOpen.value = false;
        deleteTarget.value = null;
        await refresh();
    } catch (e) {
        error.value = e?.message || 'Failed to delete.';
    } finally {
        deleting.value = false;
    }
}

async function runSync(row) {
    runningId.value = row.id;
    error.value = '';
    try {
        const res = await apiPost(`/v1/job-sources/${row.id}/run`, {});
        if (!res?.ok) {
            error.value = res?.output || 'Sync failed.';
        }
        await refresh();
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Sync failed.';
    } finally {
        runningId.value = null;
    }
}

onMounted(async () => {
    await refresh();
});
</script>
