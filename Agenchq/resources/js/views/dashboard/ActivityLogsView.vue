<template>
  <div class="space-y-6">
    <Card>
      <template #content>
        <h2 class="font-display text-2xl">Activity Logs</h2>
        <p class="text-sm text-[color:var(--p-text-muted-color)]">Audit trail of system actions.</p>
      </template>
    </Card>

    <Card v-if="admins.length > 0">
      <template #content>
        <div class="flex flex-col md:flex-row gap-3 md:items-center md:justify-between">
          <div class="text-sm text-[color:var(--p-text-muted-color)]">Viewing logs for admin (recruiter)</div>
          <Dropdown
            v-model="selectedAdminId"
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

    <Card>
      <template #content>
        <div class="flex flex-col lg:flex-row gap-3 lg:items-center lg:justify-between">
          <div class="text-sm text-[color:var(--p-text-muted-color)]">Filters</div>
          <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
            <InputText
              v-model="search"
              placeholder="Search..."
              class="w-full sm:w-64"
              @keydown.enter.prevent="applySearch"
            />
            <Dropdown v-model="action" :options="actionOptions" optionLabel="label" optionValue="value" class="w-full sm:w-56" />
            <Dropdown v-model="entity" :options="entityOptions" optionLabel="label" optionValue="value" class="w-full sm:w-56" />
            <Button type="button" label="Apply" @click="applySearch" />
          </div>
        </div>
      </template>
    </Card>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <Card>
      <template #content>
        <DataTable :value="rows" :loading="loading" stripedRows responsiveLayout="scroll">
          <Column field="created_at" header="When">
            <template #body="{ data }">
              <span>{{ data.created_at || '—' }}</span>
            </template>
          </Column>
          <Column header="User">
            <template #body="{ data }">
              <span>{{ data.user?.name || data.user?.email || '—' }}</span>
            </template>
          </Column>
          <Column field="action" header="Action">
            <template #body="{ data }">
              <span>{{ data.action || '—' }}</span>
            </template>
          </Column>
          <Column header="Entity">
            <template #body="{ data }">
              <span>{{ data.entity_name || data.entity || '—' }}</span>
            </template>
          </Column>
          <Column header="Description">
            <template #body="{ data }">
              <span>{{ data.description || '—' }}</span>
            </template>
          </Column>

          <template #empty>
            <div class="py-6 text-[color:var(--p-text-muted-color)]">No logs</div>
          </template>
        </DataTable>
      </template>
    </Card>

    <Card v-if="meta">
      <template #content>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          <div class="text-sm text-[color:var(--p-text-muted-color)]">Page {{ meta.current_page }} of {{ meta.last_page }} • {{ meta.total }} total</div>
          <div class="flex justify-end">
            <Paginator
              :rows="meta.per_page || 20"
              :totalRecords="meta.total"
              :first="(meta.current_page - 1) * (meta.per_page || 20)"
              @page="onPage"
            />
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { onMounted, ref, watch, computed } from 'vue';
import { useRoute } from 'vue-router';
import { apiGet, normalizeApiList } from '../../lib/api';
import { useAuthStore } from '../../stores/auth';
import { usePolling } from '../../composables/usePolling';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Paginator from 'primevue/paginator';

const route = useRoute();
const auth = useAuthStore();

const rows = ref([]);
const loading = ref(true);
const error = ref('');
const meta = ref(null);

const page = ref(1);
const search = ref('');
const action = ref('');
const entity = ref('');

const admins = ref([]);
const selectedAdminId = ref('');
const latestId = ref(0);

const actionOptions = [
    { label: 'All Actions', value: '' },
    { label: 'Created', value: 'created' },
    { label: 'Updated', value: 'updated' },
    { label: 'Deleted', value: 'deleted' },
    { label: 'Login', value: 'login' },
    { label: 'Sign out', value: 'logout' },
];

const entityOptions = [
    { label: 'All Entities', value: '' },
    { label: 'Facility', value: 'facility' },
    { label: 'Contract', value: 'contract' },
    { label: 'Billing', value: 'billing' },
    { label: 'Invoice', value: 'invoice' },
    { label: 'Placement', value: 'placement' },
    { label: 'User', value: 'user' },
];

const queryString = computed(() => {
    const params = new URLSearchParams();
    params.set('per_page', '20');
    params.set('page', String(page.value));
    if (search.value.trim()) params.set('search', search.value.trim());
    if (action.value.trim()) params.set('action', action.value.trim());
    if (entity.value.trim()) params.set('entity', entity.value.trim());
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

async function fetchLogs() {
    try {
        loading.value = true;
        error.value = '';
        const res = await apiGet(`/activity-logs?${queryString.value}`);
        rows.value = normalizeApiList(res);
        meta.value = res?.meta || null;
        latestId.value = rows.value.reduce((max, row) => Math.max(max, Number(row?.id || 0)), 0);
    } catch (e) {
        rows.value = [];
        meta.value = null;
        error.value = e?.response?.data?.message || e?.message || 'Failed to load activity logs';
    } finally {
        loading.value = false;
    }
}

function dedupeById(list = []) {
    const seen = new Set();
    return list.filter((row) => {
        const id = Number(row?.id || 0);
        if (!id || seen.has(id)) return false;
        seen.add(id);
        return true;
    });
}

async function fetchLiveUpdates() {
    if (loading.value) return;
    if (page.value !== 1) return; // keep paginated navigation stable on other pages
    if (!latestId.value) return;
    try {
        const res = await apiGet(`/activity-logs?${queryString.value}&since_id=${encodeURIComponent(String(latestId.value))}`);
        const incoming = normalizeApiList(res);
        if (!Array.isArray(incoming) || incoming.length === 0) return;

        const merged = dedupeById([...incoming.slice().reverse(), ...rows.value]);
        const maxRows = Number(meta.value?.per_page || 20);
        rows.value = merged.slice(0, maxRows);

        const incomingMax = incoming.reduce((max, row) => Math.max(max, Number(row?.id || 0)), latestId.value);
        latestId.value = Math.max(latestId.value, incomingMax);

        if (meta.value && typeof meta.value.total === 'number') {
            meta.value = {
                ...meta.value,
                total: meta.value.total + incoming.length,
            };
        }
    } catch {
        // non-blocking; manual view remains available
    }
}

function applySearch() {
    page.value = 1;
    fetchLogs();
}

function onPage(event) {
    const nextPage = Math.floor(Number(event.first || 0) / Number(event.rows || 20)) + 1;
    page.value = nextPage;
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

watch(page, fetchLogs);
watch(selectedAdminId, () => {
    page.value = 1;
    latestId.value = 0;
    fetchLogs();
});

onMounted(async () => {
    await loadAdminsIfApplicable();
    await fetchLogs();
});

usePolling(fetchLiveUpdates, 5000, { immediate: false });
</script>
