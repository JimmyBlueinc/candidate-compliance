<template>
  <div class="space-y-6">
    <Card>
      <template #content>
        <div class="flex items-center justify-between">
          <div>
            <h2 class="font-display text-2xl">Work Authorizations</h2>
            <p class="text-sm text-[color:var(--p-text-muted-color)]">Review and manage work authorization records.</p>
          </div>
          <Button label="Add Record" icon="pi pi-plus" @click="openCreate" />
        </div>
      </template>
    </Card>

    <Card v-if="admins.length > 0">
      <template #content>
        <div class="flex flex-col md:flex-row gap-3 md:items-center md:justify-between">
          <div class="text-sm text-[color:var(--p-text-muted-color)]">Viewing records for admin (recruiter)</div>
          <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
            <InputText v-model="adminSearch" placeholder="Search admin..." class="w-full sm:w-64" />
            <Dropdown
              v-model="selectedAdminId"
              :options="filteredAdmins"
              optionLabel="name"
              optionValue="id"
              class="w-full sm:w-auto"
              filter
              placeholder="Select admin"
            >
              <template #option="slotProps">
                <div class="flex flex-col">
                  <span class="font-medium">{{ slotProps.option.name }}</span>
                  <span class="text-xs text-[color:var(--p-text-muted-color)]">{{ slotProps.option.email }}</span>
                </div>
              </template>
            </Dropdown>
          </div>
        </div>
      </template>
    </Card>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <Card>
      <template #content>
        <DataTable :value="rows" :loading="loading" dataKey="id" stripedRows responsiveLayout="scroll">
          <Column field="candidate_name" header="Admin">
            <template #body="{ data }">
              <span class="font-medium">{{ data.candidate_name || '—' }}</span>
            </template>
          </Column>
          <Column field="authorization_type" header="Type">
            <template #body="{ data }">
              <span>{{ data.authorization_type || '—' }}</span>
            </template>
          </Column>
          <Column field="status" header="Status">
            <template #body="{ data }">
              <span>{{ data.status || '—' }}</span>
            </template>
          </Column>
          <Column field="expiry_date" header="Expiry">
            <template #body="{ data }">
              <span>{{ data.expiry_date || '—' }}</span>
            </template>
          </Column>
          <Column header="Actions" style="width: 1%; white-space: nowrap">
            <template #body="{ data }">
              <div class="flex items-center justify-end gap-2">
                <Button icon="pi pi-pencil" severity="secondary" text rounded @click="openEdit(data)" />
                <Button icon="pi pi-trash" severity="danger" text rounded @click="handleDelete(data.id)" />
              </div>
            </template>
          </Column>

          <template #empty>
            <div class="py-6 text-[color:var(--p-text-muted-color)]">No records</div>
          </template>
        </DataTable>
      </template>
    </Card>

    <WorkAuthorizationFormModal
      :isOpen="isModalOpen"
      :initialData="editingRow"
      :selectedAdminId="selectedAdminId"
      :attachUserId="Boolean(selectedAdminId) && ['org_super_admin','admin'].includes(auth.user?.role)"
      @close="closeModal"
      @success="fetchRecords"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { apiDelete, apiGet, normalizeApiList } from '../../lib/api';
import { useAuthStore } from '../../stores/auth';
import WorkAuthorizationFormModal from '../../components/dashboard/WorkAuthorizationFormModal.vue';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Button from 'primevue/button';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';

const route = useRoute();
const auth = useAuthStore();

const rows = ref([]);
const loading = ref(false);
const error = ref('');

const admins = ref([]);
const selectedAdminId = ref('');
const adminSearch = ref('');

const isModalOpen = ref(false);
const editingRow = ref(null);

const filteredAdmins = computed(() => {
    const q = adminSearch.value.trim().toLowerCase();
    if (!q) return admins.value;
    return admins.value.filter((a) => {
        const n = String(a?.name || '').toLowerCase();
        const e = String(a?.email || '').toLowerCase();
        return n.includes(q) || e.includes(q);
    });
});

const queryString = computed(() => {
    const params = new URLSearchParams();
    if (selectedAdminId.value) params.set('user_id', selectedAdminId.value);
    return params.toString();
});

async function loadAdminsIfApplicable() {
    if (!auth.user || !['org_super_admin', 'admin'].includes(auth.user.role)) return;

    try {
        const res = await apiGet('/admin/users');
        const list = (res?.users || []).filter((u) => u.role === 'admin');
        admins.value = list;

        const urlUserId = String(route.query.user_id || '');
        if (urlUserId && !selectedAdminId.value) {
            selectedAdminId.value = urlUserId;
        }

        if (!selectedAdminId.value && list.length) {
            selectedAdminId.value = String(list[0].id);
        }
    } catch {
        admins.value = [];
    }
}

async function fetchRecords() {
    try {
        loading.value = true;
        error.value = '';
        const path = `/work-authorizations${queryString.value ? `?${queryString.value}` : ''}`;
        const res = await apiGet(path);
        rows.value = normalizeApiList(res);
    } catch (e) {
        rows.value = [];
        error.value = e?.response?.data?.message || e?.message || 'Failed to load work authorizations';
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

async function handleDelete(id) {
    if (!window.confirm('Are you sure you want to delete this record?')) return;

    try {
        await apiDelete(`/work-authorizations/${encodeURIComponent(String(id))}`);
        await fetchRecords();
    } catch (e) {
        window.alert(e?.response?.data?.message || e?.message || 'Failed to delete record');
    }
}

watch(
    () => route.query.user_id,
    (v) => {
        const userId = String(v || '');
        if (userId && userId !== selectedAdminId.value) {
            selectedAdminId.value = userId;
        }
    }
);

watch(selectedAdminId, fetchRecords);

onMounted(async () => {
    await loadAdminsIfApplicable();
    await fetchRecords();
});
</script>
