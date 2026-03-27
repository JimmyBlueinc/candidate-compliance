<template>
  <div class="space-y-6">
    <Card>
      <template #content>
        <div class="flex flex-col gap-4">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="font-display text-2xl">Credentials</h2>
              <p class="text-sm text-[color:var(--p-text-muted-color)]">Search and review credentials in the system.</p>
            </div>
            <Button label="Add Credential" icon="pi pi-plus" @click="openCreate" />
          </div>

          <div class="flex flex-col sm:flex-row gap-3 w-full">
            <InputText v-model="searchName" placeholder="Search name" class="w-full" @input="page = 1" />
            <InputText v-model="searchType" placeholder="Search type" class="w-full" @input="page = 1" />
            <InputText v-model="searchEmail" placeholder="Filter email" class="w-full" @input="page = 1" />
          </div>
        </div>
      </template>
    </Card>

    <Message v-if="selectedUserId" severity="info" :closable="false">
      Managing credentials for selected admin:
      <span class="font-semibold">{{ selectedAdminEmail || `User #${selectedUserId}` }}</span>
    </Message>

    <Card v-if="admins.length > 0">
      <template #content>
        <div class="flex flex-col md:flex-row gap-3 md:items-center md:justify-between">
          <div class="text-sm text-[color:var(--p-text-muted-color)]">Viewing credentials for admin (recruiter)</div>
          <Dropdown
            v-model="selectedUserId"
            :options="admins"
            optionLabel="name"
            optionValue="id"
            filter
            class="w-full md:w-96"
          >
            <template #option="slotProps">
              <div class="flex flex-col">
                <span class="font-medium">{{ slotProps.option.name }}</span>
                <span class="text-xs text-[color:var(--p-text-muted-color)]">{{ slotProps.option.email }}</span>
              </div>
            </template>
          </Dropdown>
        </div>
      </template>
    </Card>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <Card>
      <template #content>
        <DataTable :value="rows" :loading="loading" dataKey="id" stripedRows responsiveLayout="scroll">
          <Column field="candidate_name" header="Candidate">
            <template #body="{ data }">
              <span class="font-medium">{{ data.candidate_name || '—' }}</span>
            </template>
          </Column>
          <Column field="credential_type" header="Type" />
          <Column field="position" header="Position" />
          <Column field="expiry_date" header="Expiry" />
          <Column header="Status" style="width: 1%; white-space: nowrap">
            <template #body="{ data }">
              <Tag severity="secondary" :value="data.status || '—'" />
            </template>
          </Column>
          <Column header="Actions" style="width: 1%; white-space: nowrap">
            <template #body="{ data }">
              <div class="flex items-center justify-end gap-2">
                <Button icon="pi pi-pencil" severity="secondary" text rounded @click="openEdit(data)" />
                <Button icon="pi pi-trash" severity="danger" text rounded @click="deleteCredential(data.id)" />
              </div>
            </template>
          </Column>

          <template #empty>
            <div class="py-6 text-[color:var(--p-text-muted-color)]">No credentials found.</div>
          </template>
        </DataTable>

        <div v-if="meta" class="mt-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          <div class="text-sm text-[color:var(--p-text-muted-color)]">Page {{ meta.current_page }} of {{ meta.last_page }} • {{ meta.total }} total</div>
          <Paginator
            :rows="meta.per_page || 10"
            :totalRecords="meta.total"
            :first="(meta.current_page - 1) * (meta.per_page || 10)"
            @page="onPage"
          />
        </div>
      </template>
    </Card>

    <CredentialFormModal
      :isOpen="isModalOpen"
      :initialData="editingRow"
      :targetUserId="selectedUserId || null"
      :targetEmail="searchEmail || ''"
      @close="closeModal"
      @success="load"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { apiDelete, apiGet, normalizeApiList } from '../../lib/api';
import { useAuthStore } from '../../stores/auth';
import CredentialFormModal from '../../components/dashboard/CredentialFormModal.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Paginator from 'primevue/paginator';
import Tag from 'primevue/tag';

const auth = useAuthStore();

const rows = ref([]);
const loading = ref(false);
const error = ref('');
const page = ref(1);
const searchName = ref('');
const searchType = ref('');
const searchEmail = ref('');
const meta = ref(null);

const admins = ref([]);
const selectedUserId = ref('');
const selectedAdminEmail = ref('');

const isModalOpen = ref(false);
const editingRow = ref(null);

const query = computed(() => {
    const params = new URLSearchParams();
    params.set('per_page', '10');
    params.set('page', String(page.value));
    if (searchName.value.trim()) params.set('name', searchName.value.trim());
    if (searchType.value.trim()) params.set('type', searchType.value.trim());
    if (searchEmail.value.trim()) params.set('email', searchEmail.value.trim());
    if (selectedUserId.value.trim()) params.set('user_id', selectedUserId.value.trim());
    return params.toString();
});

async function load() {
    loading.value = true;
    error.value = '';
    try {
        const response = await apiGet(`/credentials?${query.value}`);
        rows.value = normalizeApiList(response);
        meta.value = response?.meta || null;
    } catch (e) {
        rows.value = [];
        meta.value = null;
        error.value = e?.response?.data?.message || e?.message || 'Failed to load credentials';
    } finally {
        loading.value = false;
    }
}

function openCreate() {
    editingRow.value = null;
    isModalOpen.value = true;
}

function openEdit(row) {
    editingRow.value = row;
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
}

async function deleteCredential(id) {
    if (!window.confirm('Are you sure you want to delete this credential?')) return;
    try {
        await apiDelete(`/credentials/${encodeURIComponent(String(id))}`);
        await load();
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Failed to delete credential';
    }
}

async function loadAdmins() {
    if (!auth.user || !['org_super_admin', 'admin'].includes(auth.user.role)) {
        admins.value = [];
        return;
    }

    try {
        const res = await apiGet('/admin/users');
        const list = (res?.users || []).filter((u) => u.role === 'admin');
        admins.value = list;

        if (!selectedUserId.value && list.length > 0) {
            selectedUserId.value = String(list[0].id);
        }
    } catch {
        admins.value = [];
    }
}

watch(
    () => [admins.value, selectedUserId.value],
    () => {
        if (!admins.value.length || !selectedUserId.value) {
            selectedAdminEmail.value = '';
            return;
        }
        const match = admins.value.find((a) => String(a.id) === String(selectedUserId.value));
        selectedAdminEmail.value = match?.email ? String(match.email) : '';
    },
    { immediate: true }
);

watch(query, () => {
    load();
}, { immediate: true });

function onPage(event) {
    const nextPage = Math.floor(Number(event.first || 0) / Number(event.rows || 10)) + 1;
    page.value = nextPage;
}

onMounted(loadAdmins);
</script>
