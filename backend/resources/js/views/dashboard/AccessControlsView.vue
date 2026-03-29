<template>
  <div class="space-y-6">
    <Card>
      <template #content>
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
          <div>
            <h2 class="font-display text-2xl">Access Controls</h2>
            <p class="text-sm text-[color:var(--p-text-muted-color)]">Activate, suspend, or terminate access for team members in your organization.</p>
          </div>
          <div class="text-right">
            <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Current role</div>
            <div class="text-sm font-semibold">{{ auth.user?.role || '—' }}</div>
          </div>
        </div>
      </template>
    </Card>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <Card>
      <template #content>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
          <SelectButton v-model="tab" :options="tabOptions" optionLabel="label" optionValue="value" />
          <Button label="Refresh" size="small" severity="secondary" outlined @click="load" />
        </div>

        <DataTable :value="activeRows" :loading="loading" dataKey="id" stripedRows responsiveLayout="scroll">
          <Column field="name" header="Name">
            <template #body="{ data }">
              <span class="font-medium">{{ data.name }}</span>
            </template>
          </Column>
          <Column field="email" header="Email" />
          <Column field="role" header="Role" />
          <Column header="Access">
            <template #body="{ data }">
              <Tag :severity="accessSeverity(data.access_status)" :value="data.access_status || 'active'" />
            </template>
          </Column>
          <Column header="Actions" style="width: 1%; white-space: nowrap">
            <template #body="{ data }">
              <span v-if="!canManage" class="text-xs text-[color:var(--p-text-muted-color)]">No access</span>
              <div v-else class="flex items-center justify-end gap-2 flex-wrap">
                <Button
                  label="Activate"
                  size="small"
                  severity="success"
                  outlined
                  :disabled="isSelfDisabled(data)"
                  @click="setAccess(data, 'active')"
                />
                <Button
                  label="Suspend"
                  size="small"
                  severity="warning"
                  outlined
                  :disabled="isSelfDisabled(data)"
                  @click="setAccess(data, 'suspended')"
                />
                <Button
                  label="Terminate"
                  size="small"
                  severity="danger"
                  outlined
                  :disabled="isSelfDisabled(data)"
                  @click="setAccess(data, 'terminated')"
                />
              </div>
            </template>
          </Column>

          <template #empty>
            <div class="py-6 text-[color:var(--p-text-muted-color)]">No users</div>
          </template>
        </DataTable>
      </template>
    </Card>

    <div v-if="!canManage" class="text-xs text-[color:var(--p-text-muted-color)]">
      Only <span class="font-semibold">org_super_admin</span> or <span class="font-semibold">platform_admin</span> can change user access.
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { apiGet, apiPut } from '../../lib/api';
import { useAuthStore } from '../../stores/auth';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Message from 'primevue/message';
import SelectButton from 'primevue/selectbutton';
import Tag from 'primevue/tag';

const auth = useAuthStore();

const rows = ref([]);
const loading = ref(true);
const error = ref('');
const tab = ref('admins');

const tabOptions = computed(() => {
    const options = [{ label: `Admins (${admins.value.length})`, value: 'admins' }];
    if (auth.user?.role === 'org_super_admin' || auth.user?.role === 'platform_admin') {
        options.push({ label: `Candidates (${candidates.value.length})`, value: 'candidates' });
    }
    return options;
});

const canManage = computed(() => auth.user?.role === 'org_super_admin' || auth.user?.role === 'platform_admin');

const admins = computed(() => {
    const base = rows.value.filter((r) => r.role === 'admin' || r.role === 'org_super_admin');
    if (auth.user?.role === 'org_super_admin') {
        return base.filter((r) => Number(r.id) !== Number(auth.user.id));
    }
    return base;
});

const candidates = computed(() => rows.value.filter((r) => r.role === 'candidate'));

const activeRows = computed(() => (tab.value === 'admins' ? admins.value : candidates.value));

function accessSeverity(status) {
    const value = String(status || 'active');
    if (value === 'active') return 'success';
    if (value === 'suspended') return 'warning';
    return 'danger';
}

watch(
    () => auth.user?.role,
    (role) => {
        if (role === 'org_super_admin' && tab.value !== 'admins') {
            tab.value = 'admins';
        }
    }
);

async function load() {
    try {
        loading.value = true;
        error.value = '';
        const res = await apiGet('/admin/users');
        rows.value = res?.users || [];
    } catch (e) {
        rows.value = [];
        error.value = e?.response?.data?.message || e?.message || 'Failed to load users';
    } finally {
        loading.value = false;
    }
}

function isSelfDisabled(u) {
    const isSelf = Number(u?.id) === Number(auth.user?.id);
    return auth.user?.role === 'org_super_admin' && isSelf;
}

async function setAccess(u, nextStatus) {
    if (!canManage.value) return;

    if (auth.user?.role === 'org_super_admin' && Number(u?.id) === Number(auth.user?.id)) {
        error.value = 'You cannot change your own access status.';
        return;
    }

    const label = nextStatus === 'active' ? 'activate' : nextStatus;
    if (!window.confirm(`Are you sure you want to ${label} this user?`)) return;

    try {
        error.value = '';
        await apiPut(`/admin/users/${encodeURIComponent(String(u.id))}`, { access_status: nextStatus });
        await load();
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Failed to update user access';
    }
}

onMounted(load);
</script>
