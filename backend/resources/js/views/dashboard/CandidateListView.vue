<template>
  <div class="space-y-8">
    <UiPageHeader title="Candidates" subtitle="Manage and search your candidate database.">
      <template #actions>
        <Button label="Refresh" icon="pi pi-refresh" severity="secondary" outlined size="small" @click="refresh" />
        <Button label="Save Search" icon="pi pi-save" severity="secondary" outlined size="small" @click="openSaveSearchDialog" />
        <Button v-if="canCreateCandidate" label="Create Candidate" icon="pi pi-plus" size="small" @click="showCreateDialog = true" />
      </template>
    </UiPageHeader>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <UiStatCard title="Total Candidates" :value="rows.length">
        <template #icon><Search class="w-3.5 h-3.5" /></template>
      </UiStatCard>
      <!-- ... other stats ... -->
    </div>

    <UiCard title="Candidate Search">
      <div class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
          <InputText v-model="searchQuery" class="w-full" placeholder="Search by name, email, or phone..." @keyup.enter="runSearch" />
        </div>
        <div class="flex gap-2">
          <Button label="Search" icon="pi pi-search" @click="runSearch" :loading="loading" />
          <Button label="Clear" severity="secondary" outlined @click="clearSearch" />
        </div>
      </div>
    </UiCard>

    <UiCard>
      <DataTable :value="rows" :loading="loading" dataKey="id" stripedRows responsiveLayout="scroll" size="small">
        <Column field="name" header="Name">
          <template #body="{ data }">
            <button type="button" class="text-left group" @click="viewProfile(data.id)">
              <div class="font-bold text-primary group-hover:underline">{{ data.name || (data.first_name + ' ' + data.last_name) }}</div>
              <div class="text-xs text-[color:var(--aq-muted)]">{{ data.email }}</div>
            </button>
          </template>
        </Column>
        <Column field="phone" header="Phone" />
        <Column field="specialty" header="Specialty" />
        <Column header="Tags">
          <template #body="{ data }">
            <div class="flex flex-wrap gap-1">
              <UiBadge v-for="tag in (data.tags || [])" :key="tag" variant="outline">{{ tag }}</UiBadge>
            </div>
          </template>
        </Column>
        <Column header="Actions" class="text-right">
          <template #body="{ data }">
            <div class="inline-flex items-center gap-1">
              <Button icon="pi pi-user" label="Profile" size="small" text @click="viewProfile(data.id)" />
              <Button v-if="canManageCandidate" icon="pi pi-pencil" label="Edit" size="small" text @click="openEditCandidate(data)" />
              <Button v-if="canManageCandidate" icon="pi pi-trash" label="Delete" size="small" text severity="danger" @click="openDeleteCandidate(data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </UiCard>

    <Dialog v-model:visible="importOpen" modal header="Import Candidates" :style="{ width: 'min(1100px, 96vw)' }">
      <div class="space-y-4">
        <Message v-if="importError" severity="error" :closable="false">{{ importError }}</Message>
        <Message v-if="importSuccess" severity="success" :closable="false">{{ importSuccess }}</Message>

        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="text-xs text-[color:var(--aq-muted)]">
            Step <span class="font-bold text-[color:var(--aq-fg)]">{{ importStep }}</span> of <span class="font-bold text-[color:var(--aq-fg)]">4</span>
          </div>
          <div class="flex gap-2">
            <Button v-if="importStep > 1 && importStep < 4" type="button" label="Back" severity="secondary" outlined size="small" @click="prevStep" />
            <Button type="button" label="Close" severity="secondary" outlined size="small" @click="closeImport" />
          </div>
        </div>

        <div v-if="importStep === 1" class="space-y-3">
          <div class="text-sm text-[color:var(--aq-muted)]">
            Upload a <span class="font-semibold text-[color:var(--aq-fg)]">.csv</span> or <span class="font-semibold text-[color:var(--aq-fg)]">.xlsx</span> file.
          </div>

          <div class="rounded-2xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] p-4">
            <input type="file" accept=".csv,.xlsx" @change="onPickFile" />
            <div v-if="importFileName" class="mt-2 text-xs text-[color:var(--aq-muted)]">
              Selected: <span class="font-semibold text-[color:var(--aq-fg)]">{{ importFileName }}</span>
            </div>
          </div>

          <div class="flex justify-end">
            <Button type="button" label="Parse File" size="small" :loading="importBusy" :disabled="!importFile" @click="uploadAndParse" />
          </div>
        </div>

        <div v-else-if="importStep === 2" class="space-y-3">
          <div class="text-sm text-[color:var(--aq-muted)]">
            Map your spreadsheet columns to AgencHQ candidate fields.
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="f in importFields" :key="f.key" class="space-y-2">
              <div class="text-xs font-bold uppercase tracking-widest text-[color:var(--aq-muted)]">{{ f.label }}</div>
              <Dropdown
                v-model="importMapping[f.key]"
                :options="importHeaderOptions"
                optionLabel="label"
                optionValue="value"
                class="w-full"
                size="small"
                placeholder="Ignore"
              />
            </div>
          </div>

          <div class="flex flex-wrap items-center justify-between gap-2 pt-2">
            <div class="text-xs text-[color:var(--aq-muted)]">
              Required: name (first name or full name) + at least one contact (email or phone)
            </div>
            <div class="flex gap-2">
              <Button type="button" label="Preview" size="small" :loading="importBusy" @click="previewImport" />
            </div>
          </div>
        </div>

        <div v-else-if="importStep === 3" class="space-y-3">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm text-[color:var(--aq-muted)]">
              Preview shows up to <span class="font-semibold text-[color:var(--aq-fg)]">200</span> rows.
            </div>
            <div class="text-xs text-[color:var(--aq-muted)]">
              Total rows: <span class="font-semibold text-[color:var(--aq-fg)]">{{ importTotalRows }}</span>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="rounded-2xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] p-3">
              <div class="text-[10px] uppercase tracking-widest text-[color:var(--aq-muted)] font-black">Valid</div>
              <div class="mt-1 text-[color:var(--aq-fg)] font-semibold">{{ importPreviewSummary?.valid || 0 }}</div>
            </div>
            <div class="rounded-2xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] p-3">
              <div class="text-[10px] uppercase tracking-widest text-[color:var(--aq-muted)] font-black">Duplicates</div>
              <div class="mt-1 text-[color:var(--aq-fg)] font-semibold">{{ importPreviewSummary?.duplicates || 0 }}</div>
            </div>
            <div class="rounded-2xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] p-3">
              <div class="text-[10px] uppercase tracking-widest text-[color:var(--aq-muted)] font-black">Invalid</div>
              <div class="mt-1 text-[color:var(--aq-fg)] font-semibold">{{ importPreviewSummary?.invalid || 0 }}</div>
            </div>
          </div>

          <div class="overflow-x-auto rounded-2xl border border-[color:var(--aq-border)]">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="text-left text-[10px] font-black tracking-widest uppercase text-[color:var(--aq-muted)]">
                  <th class="py-3 px-3">Row</th>
                  <th class="py-3 px-3">Status</th>
                  <th class="py-3 px-3">Name</th>
                  <th class="py-3 px-3">Email</th>
                  <th class="py-3 px-3">Phone</th>
                  <th class="py-3 px-3">Specialty</th>
                  <th class="py-3 px-3">Reasons</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="r in importPreviewRows" :key="r.row_number" class="border-t border-[color:var(--aq-border)]">
                  <td class="py-3 px-3 text-[color:var(--aq-fg)]">{{ r.row_number }}</td>
                  <td class="py-3 px-3">
                    <span v-if="r.status === 'valid'" class="px-2 py-0.5 rounded-full text-[10px] font-bold border border-emerald-500/30 bg-emerald-500/10 text-emerald-200">Valid</span>
                    <span v-else-if="r.status === 'duplicate'" class="px-2 py-0.5 rounded-full text-[10px] font-bold border border-amber-500/30 bg-amber-500/10 text-amber-200">Duplicate</span>
                    <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-bold border border-red-500/30 bg-red-500/10 text-red-200">Invalid</span>
                  </td>
                  <td class="py-3 px-3 text-[color:var(--aq-fg)] font-semibold">{{ r.data?.name || '—' }}</td>
                  <td class="py-3 px-3 text-[color:var(--aq-muted)]">{{ r.data?.email || '—' }}</td>
                  <td class="py-3 px-3 text-[color:var(--aq-muted)]">{{ r.data?.phone || '—' }}</td>
                  <td class="py-3 px-3 text-[color:var(--aq-muted)]">{{ r.data?.specialty || '—' }}</td>
                  <td class="py-3 px-3 text-xs text-[color:var(--aq-muted)]">
                    {{ Array.isArray(r.reasons) && r.reasons.length ? r.reasons.join(' ') : '—' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
            <div class="text-xs text-[color:var(--aq-muted)]">
              Duplicates will be skipped. Invalid rows will fail.
            </div>
            <div class="flex gap-2">
              <Button type="button" label="Import" size="small" :loading="importBusy" @click="commitImport" />
            </div>
          </div>
        </div>

        <div v-else class="space-y-3">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="rounded-2xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] p-3">
              <div class="text-[10px] uppercase tracking-widest text-[color:var(--aq-muted)] font-black">Imported</div>
              <div class="mt-1 text-[color:var(--aq-fg)] font-semibold">{{ importResults?.imported || 0 }}</div>
            </div>
            <div class="rounded-2xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] p-3">
              <div class="text-[10px] uppercase tracking-widest text-[color:var(--aq-muted)] font-black">Skipped Duplicates</div>
              <div class="mt-1 text-[color:var(--aq-fg)] font-semibold">{{ importResults?.skipped_duplicates || 0 }}</div>
            </div>
            <div class="rounded-2xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] p-3">
              <div class="text-[10px] uppercase tracking-widest text-[color:var(--aq-muted)] font-black">Failed</div>
              <div class="mt-1 text-[color:var(--aq-fg)] font-semibold">{{ importResults?.failed || 0 }}</div>
            </div>
          </div>

          <div class="text-sm text-[color:var(--aq-muted)]">
            Import complete. Refresh the list to see new candidates.
          </div>

          <div class="flex justify-end">
            <Button type="button" label="Done" size="small" @click="closeImport" />
          </div>
        </div>
      </div>
    </Dialog>

    <!-- Create Candidate Dialog -->
    <Dialog v-model:visible="showCreateDialog" modal header="Create Candidate" :style="{ width: 'min(600px, 95vw)' }">
      <form @submit.prevent="createCandidate" class="space-y-4 pt-2">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--aq-muted)]">First Name</label>
            <InputText v-model="createFirstName" class="w-full" required size="small" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--aq-muted)]">Last Name</label>
            <InputText v-model="createLastName" class="w-full" required size="small" />
          </div>
        </div>
        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--aq-muted)]">Email</label>
          <InputText v-model="createEmail" type="email" class="w-full" required size="small" />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--aq-muted)]">Phone</label>
          <InputText v-model="createPhone" class="w-full" size="small" />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--aq-muted)]">Specialty</label>
          <InputText v-model="createSpecialty" class="w-full" size="small" />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--aq-muted)]">Tags (comma separated)</label>
          <InputText v-model="createTags" class="w-full" size="small" />
        </div>

        <Message v-if="createError" severity="error" :closable="false">{{ createError }}</Message>
        <Message v-if="createSuccess" severity="success" :closable="false">{{ createSuccess }}</Message>

        <div class="flex justify-end pt-2">
          <Button type="submit" label="Create Candidate" :loading="creating" />
        </div>
      </form>
    </Dialog>

    <Dialog v-model:visible="showEditDialog" modal header="Edit Candidate" :style="{ width: 'min(600px, 95vw)' }">
      <form @submit.prevent="updateCandidate" class="space-y-4 pt-2">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--aq-muted)]">First Name</label>
            <InputText v-model="editCandidate.first_name" class="w-full" required size="small" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--aq-muted)]">Last Name</label>
            <InputText v-model="editCandidate.last_name" class="w-full" required size="small" />
          </div>
        </div>
        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--aq-muted)]">Email</label>
          <InputText v-model="editCandidate.email" type="email" class="w-full" required size="small" />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--aq-muted)]">Phone</label>
          <InputText v-model="editCandidate.phone" class="w-full" size="small" />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--aq-muted)]">Specialty</label>
          <InputText v-model="editCandidate.specialty" class="w-full" size="small" />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--aq-muted)]">Tags (comma separated)</label>
          <InputText v-model="editCandidate.tagsText" class="w-full" size="small" />
        </div>
        <Message v-if="editError" severity="error" :closable="false">{{ editError }}</Message>
        <div class="flex justify-end pt-2">
          <Button type="submit" label="Save Changes" :loading="editing" />
        </div>
      </form>
    </Dialog>

    <Dialog v-model:visible="showDeleteDialog" modal header="Delete Candidate" :style="{ width: 'min(520px, 95vw)' }">
      <div class="space-y-4">
        <p class="text-sm text-[color:var(--aq-muted)]">
          Type <span class="font-semibold text-[color:var(--aq-fg)]">{{ deleteCandidate?.name || deleteCandidate?.email }}</span> to confirm permanent deletion.
        </p>
        <InputText v-model="deleteConfirmText" class="w-full" :placeholder="deleteCandidate?.name || deleteCandidate?.email || ''" />
        <Message v-if="deleteError" severity="error" :closable="false">{{ deleteError }}</Message>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button label="Cancel" severity="secondary" outlined @click="showDeleteDialog = false" />
          <Button
            label="Delete Candidate"
            severity="danger"
            :disabled="!canConfirmDelete"
            :loading="deleting"
            @click="removeCandidate"
          />
        </div>
      </template>
    </Dialog>

    <Dialog v-model:visible="showSaveSearchDialog" modal header="Save Search" :style="{ width: 'min(520px, 95vw)' }">
      <div class="space-y-4">
        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--aq-muted)]">Name</label>
          <InputText v-model="saveSearchName" class="w-full" placeholder="e.g. Night shift candidates" />
        </div>
        <Message v-if="saveSearchError" severity="error" :closable="false">{{ saveSearchError }}</Message>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button label="Cancel" severity="secondary" outlined @click="showSaveSearchDialog = false" />
          <Button label="Save" :loading="saveSearchLoading" @click="saveCurrentSearch" />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { apiDelete, apiGet, apiPost, apiPut } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';
import { useAuthStore } from '../../stores/auth';
import axios from 'axios';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import UiPageHeader from '../../components/ui/UiPageHeader.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiStatCard from '../../components/ui/UiStatCard.vue';
import UiBadge from '../../components/ui/UiBadge.vue';
import { Search } from 'lucide-vue-next';

const router = useRouter();
const brand = useBrandStore();
const auth = useAuthStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const loading = ref(false);
const error = ref('');
const rows = ref([]);
const exporting = ref(false);

const searchQuery = ref('');

const createFirstName = ref('');
const createLastName = ref('');
const createEmail = ref('');
const createPhone = ref('');
const createSpecialty = ref('');
const createTags = ref('');
const creating = ref(false);
const createError = ref('');
const createSuccess = ref('');

const showCreateDialog = ref(false);
const showSaveSearchDialog = ref(false);
const saveSearchName = ref('');
const saveSearchLoading = ref(false);
const saveSearchError = ref('');
const showEditDialog = ref(false);
const showDeleteDialog = ref(false);
const editError = ref('');
const editing = ref(false);
const deleting = ref(false);
const deleteError = ref('');
const deleteConfirmText = ref('');
const deleteCandidate = ref(null);
const editCandidate = ref({
    id: null,
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    specialty: '',
    tagsText: '',
});

const canCreateCandidate = computed(() => ['recruiter', 'compliance', 'admin', 'org_super_admin', 'platform_admin'].includes(String(auth.user?.role || '')));
const canManageCandidate = computed(() => ['admin', 'org_super_admin', 'platform_admin', 'recruiter', 'compliance'].includes(String(auth.user?.role || '')));
const canConfirmDelete = computed(() => {
    const target = deleteCandidate.value;
    if (!target) return false;
    const expected = String(target.name || target.email || '').trim();
    return expected.length > 0 && deleteConfirmText.value.trim() === expected;
});

async function applySavedFilterIfPresent() {
    const savedFilterId = String(router.currentRoute.value?.query?.saved_filter_id || '').trim();
    if (!savedFilterId) return false;

    try {
        const res = await apiGet('/filters');
        const rows = Array.isArray(res?.data) ? res.data : [];
        const row = rows.find((r) => String(r?.id) === savedFilterId);
        if (!row || String(row?.filters?.context || '') !== 'candidates.list') return false;

        const q = String(row?.filters?.query || '');
        searchQuery.value = q;
        if (q) {
            await runSearch();
        } else {
            await refresh();
        }
        return true;
    } catch {
        return false;
    }
}

const importOpen = ref(false);
const importStep = ref(1);
const importBusy = ref(false);
const importError = ref('');
const importSuccess = ref('');

const importFile = ref(null);
const importFileName = ref('');
const importUploadId = ref('');
const importHeaders = ref([]);
const importTotalRows = ref(0);
const importMapping = ref({});
const importPreviewRows = ref([]);
const importPreviewSummary = ref(null);
const importResults = ref(null);

const importFields = [
    { key: 'first_name', label: 'First Name' },
    { key: 'last_name', label: 'Last Name' },
    { key: 'full_name', label: 'Full Name' },
    { key: 'email', label: 'Email' },
    { key: 'phone', label: 'Phone' },
    { key: 'specialty', label: 'Specialty' },
    { key: 'license_type', label: 'License Type' },
    { key: 'years_experience', label: 'Years Experience' },
    { key: 'city', label: 'City' },
    { key: 'state', label: 'State' },
    { key: 'source', label: 'Source' },
    { key: 'notes', label: 'Notes' },
];

const importHeaderOptions = computed(() => {
    const headers = Array.isArray(importHeaders.value) ? importHeaders.value : [];
    return headers.map((h) => ({ label: h, value: h }));
});

async function refresh() {
    loading.value = true;
    error.value = '';
    try {
        const res = await apiGet('/v1/candidates');
        rows.value = Array.isArray(res?.data) ? res.data : [];
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Failed to load.';
        rows.value = [];
    } finally {
        loading.value = false;
    }
}

async function runSearch() {
    const q = String(searchQuery.value || '').trim();
    if (!q) {
        await refresh();
        return;
    }

    loading.value = true;
    error.value = '';
    try {
        const res = await apiGet('/v1/candidates/search', {
            params: {
                q,
                per_page: 200,
            },
        });
        rows.value = Array.isArray(res?.data) ? res.data : [];
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Failed to search.';
        rows.value = [];
    } finally {
        loading.value = false;
    }
}

async function clearSearch() {
    searchQuery.value = '';
    await refresh();
}

async function exportCandidates(format = 'xlsx') {
    try {
        exporting.value = true;
        const res = await axios.get('/api/v1/candidates/export', {
            responseType: 'blob',
            params: {
                format,
            },
        });
        const url = window.URL.createObjectURL(new Blob([res.data]));
        const link = document.createElement('a');
        link.href = url;
        const ext = format === 'csv' ? 'csv' : 'xlsx';
        link.setAttribute('download', `candidates-export-${new Date().toISOString().split('T')[0]}.${ext}`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Failed to export candidates.';
    } finally {
        exporting.value = false;
    }
}

function viewProfile(id) {
    router.push({ name: 'dashboard.candidate_profile', params: { id } });
}

async function createCandidate() {
    if (!canCreateCandidate.value) return;

    try {
        creating.value = true;
        createError.value = '';
        createSuccess.value = '';

        const tags = String(createTags.value || '')
            .split(',')
            .map((t) => t.trim())
            .filter(Boolean);

        await apiPost('/v1/candidates', {
            first_name: createFirstName.value,
            last_name: createLastName.value,
            email: createEmail.value,
            phone: createPhone.value || null,
            specialty: createSpecialty.value || null,
            tags,
        });

        createFirstName.value = '';
        createLastName.value = '';
        createEmail.value = '';
        createPhone.value = '';
        createSpecialty.value = '';
        createTags.value = '';
        createSuccess.value = 'Candidate created.';
        await refresh();
    } catch (e) {
        createError.value = e?.response?.data?.message || e?.message || 'Failed to create candidate';
    } finally {
        creating.value = false;
    }
}

function openEditCandidate(row) {
    if (!canManageCandidate.value) return;
    editCandidate.value = {
        id: Number(row?.id || 0),
        first_name: row?.first_name || '',
        last_name: row?.last_name || '',
        email: row?.email || '',
        phone: row?.phone || '',
        specialty: row?.specialty || '',
        tagsText: Array.isArray(row?.tags) ? row.tags.join(', ') : '',
    };
    editError.value = '';
    showEditDialog.value = true;
}

async function updateCandidate() {
    if (!canManageCandidate.value || !editCandidate.value.id) return;

    try {
        editing.value = true;
        editError.value = '';
        const tags = String(editCandidate.value.tagsText || '')
            .split(',')
            .map((t) => t.trim())
            .filter(Boolean);

        await apiPut(`/v1/candidates/${editCandidate.value.id}`, {
            first_name: editCandidate.value.first_name,
            last_name: editCandidate.value.last_name,
            email: editCandidate.value.email,
            phone: editCandidate.value.phone || null,
            specialty: editCandidate.value.specialty || null,
            tags,
        });

        showEditDialog.value = false;
        await refresh();
    } catch (e) {
        editError.value = e?.response?.data?.message || e?.message || 'Failed to update candidate.';
    } finally {
        editing.value = false;
    }
}

function openDeleteCandidate(row) {
    if (!canManageCandidate.value) return;
    deleteCandidate.value = row;
    deleteConfirmText.value = '';
    deleteError.value = '';
    showDeleteDialog.value = true;
}

async function removeCandidate() {
    if (!canManageCandidate.value || !deleteCandidate.value?.id || !canConfirmDelete.value) return;

    try {
        deleting.value = true;
        deleteError.value = '';
        await apiDelete(`/v1/candidates/${deleteCandidate.value.id}`);
        showDeleteDialog.value = false;
        deleteCandidate.value = null;
        deleteConfirmText.value = '';
        await refresh();
    } catch (e) {
        deleteError.value = e?.response?.data?.message || e?.message || 'Failed to delete candidate.';
    } finally {
        deleting.value = false;
    }
}

function openSaveSearchDialog() {
    saveSearchName.value = '';
    saveSearchError.value = '';
    showSaveSearchDialog.value = true;
}

async function saveCurrentSearch() {
    const name = String(saveSearchName.value || '').trim();
    if (!name) {
        saveSearchError.value = 'Please enter a filter name.';
        return;
    }

    try {
        saveSearchLoading.value = true;
        saveSearchError.value = '';
        await apiPost('/filters', {
            name,
            filters: {
                context: 'candidates.list',
                query: String(searchQuery.value || '').trim(),
            },
        });
        showSaveSearchDialog.value = false;
    } catch (e) {
        saveSearchError.value = e?.response?.data?.message || e?.message || 'Failed to save search.';
    } finally {
        saveSearchLoading.value = false;
    }
}

function openImport() {
    importOpen.value = true;
    importStep.value = 1;
    importBusy.value = false;
    importError.value = '';
    importSuccess.value = '';
    importFile.value = null;
    importFileName.value = '';
    importUploadId.value = '';
    importHeaders.value = [];
    importTotalRows.value = 0;
    importMapping.value = {};
    importPreviewRows.value = [];
    importPreviewSummary.value = null;
    importResults.value = null;
}

function closeImport() {
    importOpen.value = false;
}

function prevStep() {
    importError.value = '';
    importSuccess.value = '';
    if (importStep.value > 1) importStep.value -= 1;
}

function onPickFile(e) {
    const f = e?.target?.files?.[0] || null;
    importFile.value = f;
    importFileName.value = f?.name || '';
}

function normalizeKey(value) {
    return String(value || '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '');
}

function autoMap(headers) {
    const index = new Map();
    for (const h of headers) {
        index.set(normalizeKey(h), h);
    }

    const candidates = {
        first_name: ['firstname', 'first', 'givenname', 'given'],
        last_name: ['lastname', 'last', 'surname', 'familyname'],
        full_name: ['fullname', 'name', 'candidate'],
        email: ['email', 'emailaddress', 'e-mailaddress', 'e-mail'],
        phone: ['phone', 'mobile', 'mobilephone', 'cell', 'cellphone'],
        specialty: ['specialty', 'specialisation', 'profession'],
        license_type: ['licensetype', 'license', 'licence'],
        years_experience: ['yearsexperience', 'experienceyears', 'experience'],
        city: ['city', 'town'],
        state: ['state', 'province', 'region'],
        source: ['source', 'referrer', 'origin'],
        notes: ['notes', 'note', 'comments', 'comment'],
    };

    const out = {};
    for (const field of Object.keys(candidates)) {
        for (const alias of candidates[field]) {
            const match = index.get(normalizeKey(alias));
            if (match) {
                out[field] = match;
                break;
            }
        }
    }

    importMapping.value = { ...importMapping.value, ...out };
}

async function uploadAndParse() {
    if (!importFile.value) return;

    try {
        importBusy.value = true;
        importError.value = '';
        importSuccess.value = '';

        const form = new FormData();
        form.append('file', importFile.value);

        const res = await apiPost('/v1/candidates/import/upload', form, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        const payload = res?.data || res;
        importUploadId.value = payload?.upload_id || '';
        importHeaders.value = payload?.headers || [];
        importTotalRows.value = payload?.total_rows || 0;

        autoMap(importHeaders.value);

        if (!importUploadId.value) {
            importError.value = 'Failed to parse file.';
            return;
        }

        importStep.value = 2;
    } catch (e) {
        importError.value = e?.response?.data?.message || e?.message || 'Failed to upload file.';
    } finally {
        importBusy.value = false;
    }
}

async function previewImport() {
    if (!importUploadId.value) return;

    try {
        importBusy.value = true;
        importError.value = '';
        importSuccess.value = '';

        const res = await apiPost('/v1/candidates/import/preview', {
            upload_id: importUploadId.value,
            mapping: importMapping.value,
        });

        const payload = res?.data || res;
        importPreviewRows.value = payload?.preview_rows || [];
        importPreviewSummary.value = payload?.summary || null;
        importTotalRows.value = payload?.total_rows || importTotalRows.value;

        importStep.value = 3;
    } catch (e) {
        importError.value = e?.response?.data?.message || e?.message || 'Failed to generate preview.';
    } finally {
        importBusy.value = false;
    }
}

async function commitImport() {
    if (!importUploadId.value) return;

    try {
        importBusy.value = true;
        importError.value = '';
        importSuccess.value = '';

        const res = await apiPost('/v1/candidates/import/commit', {
            upload_id: importUploadId.value,
            mapping: importMapping.value,
        });

        const payload = res?.data || res;
        importResults.value = payload || null;
        importSuccess.value = 'Import completed.';
        importStep.value = 4;
    } catch (e) {
        importError.value = e?.response?.data?.message || e?.message || 'Failed to import.';
    } finally {
        importBusy.value = false;
    }
}

async function downloadTemplate() {
    try {
        importError.value = '';
        const res = await axios.get('/api/v1/candidates/import/template', {
            responseType: 'blob',
        });
        const blob = new Blob([res.data], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'agencyhq-candidates-template.csv';
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Failed to download template.';
    }
}

onMounted(() => {
    applySavedFilterIfPresent().then((applied) => {
        if (!applied) refresh();
    });
    pollTimer = window.setInterval(() => {
        if (loading.value) return;
        const hasQuery = String(searchQuery.value || '').trim().length > 0;
        if (hasQuery) {
            runSearch();
        } else {
            refresh();
        }
    }, 20000);
});

watch(
    () => router.currentRoute.value?.query?.saved_filter_id,
    async () => {
        await applySavedFilterIfPresent();
    }
);

let pollTimer = null;
onBeforeUnmount(() => {
    if (pollTimer) {
        window.clearInterval(pollTimer);
        pollTimer = null;
    }
});
</script>
