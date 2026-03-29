<template>
  <div class="space-y-4">
    <Card>
      <template #content>
        <div class="flex items-start justify-between gap-4">
          <div>
            <h2 class="font-display text-xl">Platform Users</h2>
            <p class="text-sm text-[color:var(--p-text-muted-color)]">Platform admin user management.</p>
          </div>
          <Button label="Refresh" icon="pi pi-refresh" severity="secondary" outlined size="small" @click="load" />
        </div>
      </template>
    </Card>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <Card>
      <template #content>
        <div class="flex items-start justify-between gap-6">
          <div>
            <h3 class="font-display text-lg">Create Organization Admin</h3>
            <p class="text-sm text-[color:var(--p-text-muted-color)]">
              Provision an organization owner (<span class="font-semibold">org_super_admin</span>) for a selected organization.
            </p>
          </div>
        </div>

        <form class="mt-4 space-y-3" @submit.prevent="createOrgAdmin">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="space-y-2">
              <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Organization</label>
              <Dropdown
                v-model="organizationId"
                :options="organizations"
                optionLabel="name"
                optionValue="id"
                class="w-full"
                placeholder="Select organization"
                filter
                required
                size="small"
              />
            </div>
            <div class="space-y-2">
              <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Name</label>
              <InputText v-model="name" class="w-full" required size="small" />
            </div>
            <div class="space-y-2">
              <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Email</label>
              <InputText v-model="email" type="email" class="w-full" required size="small" />
            </div>
          </div>

          <div>
            <Button :loading="creating" type="submit" label="Create" size="small" />
          </div>
        </form>
      </template>
    </Card>

    <Card>
      <template #content>
        <DataTable :value="users" :loading="loading" stripedRows responsiveLayout="scroll" size="small">
          <Column field="name" header="Name">
            <template #body="{ data }">
              <span class="font-medium">{{ data.name }}</span>
            </template>
          </Column>
          <Column field="email" header="Email" />
          <Column field="role" header="Role" />
          <Column field="credentials_count" header="Credentials">
            <template #body="{ data }">
              {{ data.credentials_count ?? '—' }}
            </template>
          </Column>

          <Column header="Actions" style="width: 1%; white-space: nowrap">
            <template #body="{ data }">
              <Button
                label="Delete"
                icon="pi pi-trash"
                severity="danger"
                outlined
                size="small"
                :disabled="!canDelete(data) || deletingId === data.id"
                :loading="deletingId === data.id"
                @click="deleteUser(data)"
              />
            </template>
          </Column>

          <template #empty>
            <div class="py-6 text-[color:var(--p-text-muted-color)]">No users</div>
          </template>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="credentialsDialogOpen" modal header="Organization Admin Login Details" :style="{ width: 'min(700px, 95vw)' }">
      <div class="space-y-3">
        <div class="rounded-2xl border border-[color:var(--p-surface-border)] p-3">
          <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Email</div>
          <div class="mt-1 font-semibold break-all">{{ createdCredentials?.email }}</div>
        </div>
        <div class="rounded-2xl border border-[color:var(--p-surface-border)] p-3">
          <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Temporary Password</div>
          <div class="mt-1 font-mono break-all">{{ createdCredentials?.tempPassword }}</div>
        </div>

        <div class="text-xs text-[color:var(--p-text-muted-color)]">
          This password is shown once. The org admin will be prompted to change it after login.
        </div>

        <div class="flex gap-2 justify-end">
          <Button type="button" label="Copy" size="small" @click="copyCredentials" />
          <Button type="button" label="Done" severity="secondary" outlined size="small" @click="createdCredentials = null" />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { apiDelete, apiGet, apiPost } from '../../lib/api';
import { useAuthStore } from '../../stores/auth';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';

const auth = useAuthStore();

const users = ref([]);
const organizations = ref([]);
const loading = ref(true);
const error = ref('');

const organizationId = ref(null);
const name = ref('');
const email = ref('');
const creating = ref(false);
const createdCredentials = ref(null);

const deletingId = ref(null);

const credentialsDialogOpen = computed({
    get: () => Boolean(createdCredentials.value),
    set: (v) => {
        if (!v) createdCredentials.value = null;
    },
});

function extractApiError(e, fallback) {
    const data = e?.response?.data || {};
    const validation = data?.errors;
    if (validation && typeof validation === 'object') {
        const firstField = Object.keys(validation)[0];
        const firstMessage = firstField ? validation[firstField]?.[0] : null;
        if (firstMessage) return String(firstMessage);
    }
    return data?.message || e?.message || fallback;
}

async function load() {
    try {
        loading.value = true;
        error.value = '';
        const [usersRes, orgsRes] = await Promise.all([
            apiGet('/admin/users'),
            apiGet('/platform/organizations'),
        ]);
        users.value = usersRes?.users || usersRes?.data || (Array.isArray(usersRes) ? usersRes : []);
        organizations.value = orgsRes?.organizations || [];
    } catch (e) {
        users.value = [];
        organizations.value = [];
        error.value = extractApiError(e, 'Failed to load users');
    } finally {
        loading.value = false;
    }
}

function canDelete(user) {
    if (!user?.id) return false;
    if (auth.user?.id && Number(user.id) === Number(auth.user.id)) return false;
    if (user.role === 'platform_admin') return false;
    return true;
}

async function deleteUser(user) {
    if (!canDelete(user)) return;

    const ok = window.confirm(`Delete ${user.email}? This cannot be undone.`);
    if (!ok) return;

    try {
        deletingId.value = user.id;
        error.value = '';
        await apiDelete(`/admin/users/${user.id}`);
        await load();
    } catch (e) {
        error.value = extractApiError(e, 'Failed to delete user');
    } finally {
        deletingId.value = null;
    }
}

async function createOrgAdmin() {
    if (!organizationId.value) return;

    try {
        creating.value = true;
        error.value = '';

        const res = await apiPost('/admin/users', {
            organization_id: organizationId.value,
            name: String(name.value || '').trim(),
            email: String(email.value || '').trim().toLowerCase(),
            role: 'org_super_admin',
        });

        if (res?.credentials?.email && res?.credentials?.temp_password) {
            createdCredentials.value = {
                email: res.credentials.email,
                tempPassword: res.credentials.temp_password,
            };
        }

        name.value = '';
        email.value = '';
        await load();
    } catch (e) {
        error.value = extractApiError(e, 'Failed to create org admin');
    } finally {
        creating.value = false;
    }
}

async function copyCredentials() {
    if (!createdCredentials.value) return;
    await navigator.clipboard.writeText(`Email: ${createdCredentials.value.email}\nPassword: ${createdCredentials.value.tempPassword}`);
}

onMounted(load);
</script>
