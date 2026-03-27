<template>
  <div class="space-y-4">
    <Card>
      <template #content>
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
          <div>
            <h2 class="font-display text-xl">Organization Users</h2>
            <p class="text-sm text-[color:var(--p-text-muted-color)]">
              Create and manage your organization team.
            </p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 w-full lg:w-auto">
            <div class="rounded-2xl border border-[color:var(--p-surface-border)] px-4 py-3">
              <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Total</div>
              <div class="text-xl font-black mt-1">{{ teamSummary.total }}</div>
            </div>
            <div class="rounded-2xl border border-[color:var(--p-surface-border)] px-4 py-3">
              <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Staff</div>
              <div class="text-xl font-black mt-1">{{ teamSummary.staff }}</div>
            </div>
            <div class="rounded-2xl border border-[color:var(--p-surface-border)] px-4 py-3">
              <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Org Admins</div>
              <div class="text-xl font-black mt-1">{{ teamSummary.orgSuperAdmins }}</div>
            </div>
            <div class="rounded-2xl border border-[color:var(--p-surface-border)] px-4 py-3">
              <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Candidates</div>
              <div class="text-xl font-black mt-1">{{ teamSummary.candidates }}</div>
            </div>
            <div class="rounded-2xl border border-[color:var(--p-surface-border)] px-4 py-3">
              <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Active</div>
              <div class="text-xl font-black mt-1">{{ teamSummary.active }}</div>
            </div>
            <div class="rounded-2xl border border-[color:var(--p-surface-border)] px-4 py-3">
              <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Suspended</div>
              <div class="text-xl font-black mt-1">{{ teamSummary.suspended }}</div>
            </div>
          </div>
        </div>
      </template>
    </Card>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <Card v-if="canManage">
      <template #content>
        <div class="flex items-start justify-between gap-6">
          <div>
            <h3 class="font-display text-lg">Admins</h3>
            <p class="text-sm text-[color:var(--p-text-muted-color)]">Provision new admins for your org.</p>
          </div>
        </div>

        <form class="mt-4 space-y-3" @submit.prevent="createUser">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="space-y-2">
              <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Name</label>
              <InputText v-model="name" class="w-full" required size="small" />
            </div>
            <div class="space-y-2">
              <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Email</label>
              <InputText v-model="email" type="email" class="w-full" required size="small" />
            </div>
            <div class="space-y-2">
              <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Role</label>
              <Dropdown v-model="role" :options="creatableRoles" optionLabel="label" optionValue="value" class="w-full" size="small" />
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
        <div class="flex items-center justify-between gap-4 mb-4">
          <h3 class="font-display text-xl">Team</h3>
          <div class="flex items-center gap-2">
            <Button v-if="canManage && users.length > 0" label="Edit Admin" size="small" severity="secondary" outlined @click="openEdit()" />
            <Button label="Refresh" size="small" severity="secondary" outlined @click="load" />
          </div>
        </div>

        <DataTable :value="users" :loading="loading" dataKey="id" stripedRows responsiveLayout="scroll" size="small">
          <Column field="name" header="Name">
            <template #body="{ data }">
              <span class="font-medium">{{ data.name }}</span>
            </template>
          </Column>
          <Column field="email" header="Email" />
          <Column field="role" header="Role">
            <template #body="{ data }">
              <span class="capitalize">{{ data.role?.replace('_', ' ') }}</span>
            </template>
          </Column>
          <Column header="Access">
            <template #body="{ data }">
              <Tag :severity="accessSeverity(data.access_status)" :value="data.access_status || 'active'" />
            </template>
          </Column>
          <Column header="Actions" style="width: 1%; white-space: nowrap">
            <template #body="{ data }">
              <div class="flex items-center gap-1 justify-end flex-nowrap whitespace-nowrap">
                <Button
                  v-if="canManage && data.role !== 'platform_admin' && data.id !== auth.user?.id"
                  icon="pi pi-pencil"
                  size="small"
                  severity="primary"
                  rounded
                  text
                  aria-label="Edit"
                  title="Edit"
                  @click="openEdit(data.id)"
                />
                <Button
                  v-if="canManage && data.role !== 'platform_admin' && data.id !== auth.user?.id"
                  icon="pi pi-trash"
                  size="small"
                  severity="danger"
                  rounded
                  text
                  aria-label="Delete"
                  title="Delete"
                  @click="confirmDelete(data)"
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
      Only <span class="font-semibold">org_super_admin</span> can create new users.
    </div>

    <Dialog v-model:visible="isEditOpen" modal header="Edit Team Member" :style="{ width: 'min(900px, 95vw)' }">
      <form class="space-y-4" @submit.prevent="submitEdit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Search User</label>
            <InputText v-model="editSearch" class="w-full" placeholder="Search by name or email" size="small" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Select User</label>
            <Dropdown v-model="editUserId" :options="filteredUsers" optionLabel="name" optionValue="id" class="w-full" filter size="small">
              <template #value="slotProps">
                <span v-if="slotProps.value">
                  {{ users.find((a) => String(a.id) === String(slotProps.value))?.name || 'Select' }}
                </span>
                <span v-else>Select</span>
              </template>
              <template #option="slotProps">
                <div class="flex flex-col">
                  <span class="font-medium">{{ slotProps.option.name }}</span>
                  <span class="text-xs text-[color:var(--p-text-muted-color)]">{{ slotProps.option.email }}</span>
                </div>
              </template>
            </Dropdown>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Name</label>
            <InputText v-model="editName" class="w-full" required size="small" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Email</label>
            <InputText v-model="editEmail" type="email" class="w-full" required size="small" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Role</label>
            <Dropdown v-model="editRole" :options="creatableRoles" optionLabel="label" optionValue="value" class="w-full" size="small" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">New Password (optional)</label>
            <Password v-model="editPassword" :feedback="false" toggleMask class="w-full" inputClass="w-full" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Confirm</label>
            <Password v-model="editPasswordConfirm" :feedback="false" toggleMask class="w-full" inputClass="w-full" :disabled="!editPassword" />
          </div>
        </div>

        <div v-if="currentEditUser" class="text-xs text-[color:var(--p-text-muted-color)]">
          Editing: <span class="font-semibold">{{ currentEditUser.name }}</span>
        </div>

        <div class="flex gap-2 justify-end pt-2">
          <Button type="button" label="Cancel" severity="secondary" outlined size="small" @click="isEditOpen = false" />
          <Button type="submit" label="Save" :loading="savingEdit" size="small" />
        </div>
      </form>
    </Dialog>

    <Dialog v-model:visible="credentialsDialogOpen" modal header="Admin Login Details" :style="{ width: 'min(700px, 95vw)' }">
      <div class="space-y-3">
        <div class="rounded-2xl border border-[color:var(--p-surface-border)] p-3">
          <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Email</div>
          <div class="mt-1 font-semibold break-all">{{ createdCredentials?.email }}</div>
        </div>
        <div class="rounded-2xl border border-[color:var(--p-surface-border)] p-3">
          <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Temporary Password</div>
          <div class="mt-1 font-mono break-all">{{ createdCredentials?.tempPassword }}</div>
        </div>

        <div v-if="createdCredentials?.emailSent === true" class="text-xs text-emerald-600">
          Email sent.
        </div>
        <div v-else-if="createdCredentials?.emailSent === false" class="text-xs text-amber-600">
          Email not sent. Use test login details above.
        </div>

        <div class="text-xs text-[color:var(--p-text-muted-color)]">
          These credentials are only shown once. Copy them now.
        </div>

        <div class="flex gap-2 justify-end">
          <Button type="button" label="Copy" size="small" @click="copyCredentials" />
          <Button type="button" label="Done" severity="secondary" outlined size="small" @click="credentialsDialogOpen = false" />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { apiDelete, apiGet, apiPost, apiPut } from '../../lib/api';
import { useAuthStore } from '../../stores/auth';
import { ROLE_ORG_SUPER_ADMIN, ROLE_ADMIN, ROLE_RECRUITER, ROLE_SCHEDULER, ROLE_COMPLIANCE, ROLE_FINANCE, ROLE_LOGISTICS, STAFF_ROLES } from '../../lib/roles';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Button from 'primevue/button';
import Password from 'primevue/password';
import Tag from 'primevue/tag';
const auth = useAuthStore();

const users = ref([]);
const loading = ref(true);
const error = ref('');

const name = ref('');
const email = ref('');
const role = ref('admin');
const creating = ref(false);

const createdCredentials = ref(null);

const isEditOpen = ref(false);
const editSearch = ref('');
const editUserId = ref('');
const editName = ref('');
const editEmail = ref('');
const editRole = ref('');
const editPassword = ref('');
const editPasswordConfirm = ref('');
const savingEdit = ref(false);

const canManage = computed(() => auth.user?.role === 'org_super_admin');

const creatableRoles = computed(() => {
    return [
        { label: 'Recruiter (Admin)', value: ROLE_ADMIN },
        { label: 'Recruiter', value: ROLE_RECRUITER },
        { label: 'Scheduler', value: ROLE_SCHEDULER },
        { label: 'Compliance', value: ROLE_COMPLIANCE },
        { label: 'Finance', value: ROLE_FINANCE },
        { label: 'Logistics', value: ROLE_LOGISTICS },
    ];
});

const teamSummary = computed(() => {
    const byRole = users.value.reduce((acc, u) => {
        acc[u.role] = (acc[u.role] || 0) + 1;
        return acc;
    }, {});

    const byAccess = users.value.reduce((acc, u) => {
        const s = String(u?.access_status || 'active');
        acc[s] = (acc[s] || 0) + 1;
        return acc;
    }, {});

    const staffCount = STAFF_ROLES.reduce((sum, r) => sum + (byRole[r] || 0), 0);

    return {
        total: users.value.length,
        orgSuperAdmins: byRole[ROLE_ORG_SUPER_ADMIN] || 0,
        staff: staffCount,
        candidates: byRole['candidate'] || 0,
        active: byAccess.active || 0,
        suspended: byAccess.suspended || 0,
    };
});

const filteredUsers = computed(() => {
    const q = editSearch.value.trim().toLowerCase();
    if (!q) return users.value;
    return users.value.filter((a) => {
        const n = String(a?.name || '').toLowerCase();
        const e = String(a?.email || '').toLowerCase();
        return n.includes(q) || e.includes(q);
    });
});

const currentEditUser = computed(() => users.value.find((a) => String(a.id) === String(editUserId.value)) || null);

const credentialsDialogOpen = computed({
    get: () => Boolean(createdCredentials.value),
    set: (v) => {
        if (!v) createdCredentials.value = null;
    },
});

function accessSeverity(status) {
    const value = String(status || 'active');
    if (value === 'active') return 'success';
    if (value === 'suspended') return 'warning';
    return 'danger';
}

async function load() {
    try {
        loading.value = true;
        error.value = '';
        const res = await apiGet('/admin/users');
        const payload = res?.data || res;
        users.value = Array.isArray(payload) ? payload : [];
    } catch (e) {
        users.value = [];
        error.value = e?.response?.data?.message || e?.message || 'Failed to load users';
    } finally {
        loading.value = false;
    }
}

async function confirmDelete(target) {
    if (!canManage.value) return;
    if (!target?.id) return;
    if (!window.confirm(`Delete ${target.name || 'this user'}?`)) return;

    try {
        error.value = '';
        await apiDelete(`/admin/users/${encodeURIComponent(String(target.id))}`);
        await load();
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Failed to delete user';
    }
}

async function createUser() {
    if (!canManage.value) return;

    try {
        creating.value = true;
        error.value = '';

        const res = await apiPost('/admin/users', {
            name: name.value,
            email: email.value,
            role: role.value,
        });

        const payload = res?.data || res;
        if (payload?.credentials?.email && payload?.credentials?.temp_password) {
            createdCredentials.value = {
                name: payload?.user?.name || name.value,
                email: payload.credentials.email,
                tempPassword: payload.credentials.temp_password,
                emailSent: payload?.email_sent,
            };
        }

        name.value = '';
        email.value = '';
        await load();
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Failed to create user';
    } finally {
        creating.value = false;
    }
}

function openEdit(targetId) {
    if (!canManage.value) return;

    const list = users.value;
    const idToEdit = targetId ? String(targetId) : (list.length ? String(list[0].id) : '');

    editUserId.value = idToEdit;
    const selected = list.find((a) => String(a.id) === idToEdit);
    editName.value = selected?.name || '';
    editEmail.value = selected?.email || '';
    editRole.value = selected?.role || '';
    editPassword.value = '';
    editPasswordConfirm.value = '';
    editSearch.value = '';
    isEditOpen.value = true;
}

async function submitEdit() {
    if (!canManage.value) return;
    if (!editUserId.value) return;

    try {
        savingEdit.value = true;
        error.value = '';

        const payload = {
            name: editName.value,
            email: editEmail.value,
            role: editRole.value,
        };

        if (editPassword.value) {
            payload.password = editPassword.value;
            payload.password_confirmation = editPasswordConfirm.value;
        }

        await apiPut(`/admin/users/${encodeURIComponent(String(editUserId.value))}`, payload);

        isEditOpen.value = false;
        await load();
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Failed to update user';
    } finally {
        savingEdit.value = false;
    }
}

async function copyCredentials() {
    if (!createdCredentials.value) return;

    await navigator.clipboard.writeText(`Email: ${createdCredentials.value.email}\nPassword: ${createdCredentials.value.tempPassword}`);
}

watch(
    () => ({ isEditOpen: isEditOpen.value, editUserId: editUserId.value, users: users.value }),
    ({ isEditOpen: open }) => {
        if (!open) return;
        if (!editUserId.value) return;
        const selected = users.value.find((a) => String(a.id) === String(editUserId.value));
        if (!selected) return;
        editName.value = selected.name;
        editEmail.value = selected.email;
        editRole.value = selected.role;
        editPassword.value = '';
        editPasswordConfirm.value = '';
    },
    { deep: true }
);

onMounted(load);
</script>
