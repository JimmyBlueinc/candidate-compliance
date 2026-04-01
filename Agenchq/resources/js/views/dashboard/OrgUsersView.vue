<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <AppPageHeader title="Team Members" subtitle="Create and manage your organization team.">
      <template #actions>
        <AppButton variant="secondary" size="sm" @click="load">
          <RefreshCw class="w-4 h-4" />
          Refresh
        </AppButton>
      </template>
    </AppPageHeader>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
      <AppStatCard label="Total" :value="teamSummary.total" :icon="Users" color="primary" />
      <AppStatCard label="Staff" :value="teamSummary.staff" :icon="User" color="cyan" />
      <AppStatCard label="Admins" :value="teamSummary.orgSuperAdmins" :icon="Shield" color="violet" />
      <AppStatCard label="Candidates" :value="teamSummary.candidates" :icon="UserCheck" color="emerald" />
      <AppStatCard label="Active" :value="teamSummary.active" :icon="CheckCircle" color="emerald" />
      <AppStatCard label="Suspended" :value="teamSummary.suspended" :icon="XCircle" color="rose" />
    </div>

    <!-- Error Message -->
    <div v-if="error" class="px-4 py-3 rounded-[var(--radius-lg)] bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm">
      {{ error }}
    </div>

    <!-- Create User Form -->
    <AppCard v-if="canManage" title="Add Team Member" subtitle="Provision new team members for your organization." accent accent-color="emerald">
      <form class="space-y-4" @submit.prevent="createUser">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Name</label>
            <input v-model="name" type="text" class="app-input" required placeholder="Full name" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Email</label>
            <input v-model="email" type="email" class="app-input" required placeholder="email@example.com" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Role</label>
            <select v-model="role" class="app-input">
              <option v-for="r in creatableRoles" :key="r.value" :value="r.value">{{ r.label }}</option>
            </select>
          </div>
        </div>
        <div class="pt-2">
          <AppButton type="submit" :loading="creating">
            <Plus class="w-4 h-4" />
            Create Team Member
          </AppButton>
        </div>
      </form>
    </AppCard>

    <!-- Team Table -->
    <AppCard title="Team" subtitle="All members in your organization." accent accent-color="primary">
      <template #actions>
        <AppButton v-if="canManage && users.length > 0" variant="secondary" size="sm" @click="openEdit()">
          <Edit3 class="w-4 h-4" />
          Edit Member
        </AppButton>
      </template>

      <div v-if="loading" class="py-8">
        <div class="space-y-3">
          <AppSkeleton v-for="i in 5" :key="i" variant="text" />
        </div>
      </div>

      <AppEmpty
        v-else-if="users.length === 0"
        title="No team members"
        description="Add your first team member using the form above."
        :icon="Users"
      />

      <div v-else class="overflow-x-auto -mx-6">
        <table class="w-full">
          <thead>
            <tr class="border-b border-[color:var(--aq-border)]">
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Name</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Email</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Role</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Access</th>
              <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[color:var(--aq-border)]">
            <tr v-for="user in users" :key="user.id" class="hover:bg-[color:var(--aq-surface-2)]/50 transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-[color:var(--aq-primary)]/20 flex items-center justify-center text-xs font-semibold text-[color:var(--aq-primary)]">
                    {{ (user.name || 'U').charAt(0).toUpperCase() }}
                  </div>
                  <span class="font-medium text-[color:var(--aq-fg)]">{{ user.name }}</span>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-[color:var(--aq-muted)]">{{ user.email }}</td>
              <td class="px-6 py-4">
                <AppBadge variant="default" size="sm">{{ formatRole(user.role) }}</AppBadge>
              </td>
              <td class="px-6 py-4">
                <AppBadge :variant="accessVariant(user.access_status)" size="sm">
                  {{ user.access_status || 'active' }}
                </AppBadge>
              </td>
              <td class="px-6 py-4 text-right">
                <div v-if="canManage && user.role !== 'platform_admin' && user.id !== auth.user?.id" class="flex items-center gap-2 justify-end">
                  <button
                    type="button"
                    class="p-2 rounded-[var(--radius-md)] text-[color:var(--aq-muted)] hover:text-cyan-400 hover:bg-cyan-500/10 transition-colors"
                    title="Message"
                    @click="openQuickMessage(user)"
                  >
                    <MessageCircle class="w-4 h-4" />
                  </button>
                  <button
                    type="button"
                    class="p-2 rounded-[var(--radius-md)] text-[color:var(--aq-muted)] hover:text-[color:var(--aq-fg)] hover:bg-[color:var(--aq-surface-2)] transition-colors"
                    title="Edit"
                    @click="openEdit(user.id)"
                  >
                    <Edit3 class="w-4 h-4" />
                  </button>
                  <button
                    type="button"
                    class="p-2 rounded-[var(--radius-md)] text-[color:var(--aq-muted)] hover:text-rose-400 hover:bg-rose-500/10 transition-colors"
                    title="Delete"
                    @click="confirmDelete(user)"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AppCard>

    <!-- Permission Notice -->
    <div v-if="!canManage" class="px-4 py-3 rounded-[var(--radius-lg)] bg-[color:var(--aq-surface-2)] border border-[color:var(--aq-border)] text-sm text-[color:var(--aq-muted)]">
      Only <span class="font-semibold text-[color:var(--aq-fg)]">Administrators</span> can create and manage team members.
    </div>

    <!-- Edit Modal -->
    <AppModal v-model="isEditOpen" title="Edit Team Member" size="lg">
      <form class="space-y-4" @submit.prevent="submitEdit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Search User</label>
            <input v-model="editSearch" type="text" class="app-input" placeholder="Search by name or email" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Select User</label>
            <select v-model="editUserId" class="app-input">
              <option value="">Select user</option>
              <option v-for="u in filteredUsers" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Name</label>
            <input v-model="editName" type="text" class="app-input" required />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Email</label>
            <input v-model="editEmail" type="email" class="app-input" required />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Role</label>
            <select v-model="editRole" class="app-input">
              <option v-for="r in creatableRoles" :key="r.value" :value="r.value">{{ r.label }}</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">New Password (optional)</label>
            <input v-model="editPassword" type="password" class="app-input" placeholder="Leave blank to keep current" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Confirm Password</label>
            <input v-model="editPasswordConfirm" type="password" class="app-input" :disabled="!editPassword" placeholder="Confirm new password" />
          </div>
        </div>

        <div v-if="currentEditUser" class="px-4 py-3 rounded-[var(--radius-lg)] bg-[color:var(--aq-surface-2)] border border-[color:var(--aq-border)] text-sm">
          Editing: <span class="font-semibold text-[color:var(--aq-fg)]">{{ currentEditUser.name }}</span>
        </div>
      </form>
      <template #footer>
        <div class="flex items-center gap-3 justify-end">
          <AppButton variant="ghost" @click="isEditOpen = false">Cancel</AppButton>
          <AppButton :loading="savingEdit" @click="submitEdit">Save Changes</AppButton>
        </div>
      </template>
    </AppModal>

    <!-- Credentials Modal -->
    <AppModal v-model="credentialsDialogOpen" title="Login Details" subtitle="Save these credentials securely.">
      <div class="space-y-4">
        <div class="p-4 rounded-[var(--radius-lg)] bg-[color:var(--aq-surface-2)] border border-[color:var(--aq-border)]">
          <div class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)] mb-1">Email</div>
          <div class="font-mono text-[color:var(--aq-fg)] break-all">{{ createdCredentials?.email }}</div>
        </div>
        <div class="p-4 rounded-[var(--radius-lg)] bg-[color:var(--aq-surface-2)] border border-[color:var(--aq-border)]">
          <div class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)] mb-1">Temporary Password</div>
          <div class="font-mono text-[color:var(--aq-fg)] break-all">{{ createdCredentials?.tempPassword }}</div>
        </div>

        <div v-if="createdCredentials?.emailSent === true" class="flex items-center gap-2 text-sm text-emerald-400">
          <CheckCircle class="w-4 h-4" />
          Email sent to user.
        </div>
        <div v-else-if="createdCredentials?.emailSent === false" class="flex items-center gap-2 text-sm text-amber-400">
          <AlertCircle class="w-4 h-4" />
          Email not sent. Use the credentials above.
        </div>

        <p class="text-xs text-[color:var(--aq-muted)]">
          These credentials are only shown once. Copy them now.
        </p>
      </div>
      <template #footer>
        <div class="flex items-center gap-3 justify-end">
          <AppButton variant="secondary" @click="copyCredentials">
            <Copy class="w-4 h-4" />
            Copy
          </AppButton>
          <AppButton variant="ghost" @click="createdCredentials = null">Done</AppButton>
        </div>
      </template>
    </AppModal>

    <AppModal v-model="quickMessageOpen" title="Quick Message" size="md">
      <div class="space-y-4">
        <div v-if="quickMessageTarget" class="text-sm text-[color:var(--aq-muted)]">
          To: <span class="font-semibold text-[color:var(--aq-fg)]">{{ quickMessageTarget.name }}</span>
          ({{ quickMessageTarget.email }})
        </div>
        <textarea
          v-model="quickMessageBody"
          class="app-input min-h-[120px]"
          placeholder="Type your message..."
          maxlength="5000"
        />
      </div>
      <template #footer>
        <div class="flex items-center gap-3 justify-end">
          <AppButton variant="ghost" @click="quickMessageOpen = false">Cancel</AppButton>
          <AppButton :loading="sendingQuickMessage" :disabled="!quickMessageBody.trim()" @click="sendQuickMessage">
            <Send class="w-4 h-4" />
            Send
          </AppButton>
        </div>
      </template>
    </AppModal>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { apiDelete, apiGet, apiPost, apiPut } from '../../lib/api';
import { useAuthStore } from '../../stores/auth';
import { ROLE_ORG_SUPER_ADMIN, ROLE_ADMIN, ROLE_RECRUITER, ROLE_SCHEDULER, ROLE_COMPLIANCE, ROLE_FINANCE, ROLE_LOGISTICS, STAFF_ROLES } from '../../lib/roles';
import { Users, User, Shield, UserCheck, CheckCircle, XCircle, Plus, RefreshCw, Edit3, Trash2, Copy, AlertCircle, MessageCircle, Send } from 'lucide-vue-next';
import AppPageHeader from '../../components/ui/AppPageHeader.vue';
import AppCard from '../../components/ui/AppCard.vue';
import AppStatCard from '../../components/ui/AppStatCard.vue';
import AppButton from '../../components/ui/AppButton.vue';
import AppBadge from '../../components/ui/AppBadge.vue';
import AppModal from '../../components/ui/AppModal.vue';
import AppEmpty from '../../components/ui/AppEmpty.vue';
import AppSkeleton from '../../components/ui/AppSkeleton.vue';

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
const quickMessageOpen = ref(false);
const quickMessageUserId = ref('');
const quickMessageBody = ref('');
const sendingQuickMessage = ref(false);

const canManage = computed(() => ['org_super_admin', 'admin', 'recruiter'].includes(String(auth.user?.role || '')));

const creatableRoles = computed(() => {
  return [
    { label: 'HR (Admin)', value: ROLE_ADMIN },
    { label: 'HR', value: ROLE_RECRUITER },
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
const quickMessageTarget = computed(() => users.value.find((u) => String(u.id) === String(quickMessageUserId.value)) || null);

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

function formatRole(r) {
  if (!r) return '—';
  const roleMap = {
    'org_super_admin': 'Administrator',
    'admin': 'HR',
    'recruiter': 'HR',
    'scheduler': 'Scheduler',
    'compliance': 'Compliance',
    'finance': 'Finance',
    'logistics': 'Logistics',
    'candidate': 'Candidate',
    'facility': 'Facility',
  };
  return roleMap[r] || r.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function accessVariant(status) {
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
      name: String(name.value || '').trim(),
      email: String(email.value || '').trim().toLowerCase(),
      role: String(role.value || '').trim().toLowerCase(),
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
    error.value = extractApiError(e, 'Failed to create user');
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
      name: String(editName.value || '').trim(),
      email: String(editEmail.value || '').trim().toLowerCase(),
      role: String(editRole.value || '').trim().toLowerCase(),
    };

    if (editPassword.value) {
      payload.password = editPassword.value;
      payload.password_confirmation = editPasswordConfirm.value;
    }

    await apiPut(`/admin/users/${encodeURIComponent(String(editUserId.value))}`, payload);

    isEditOpen.value = false;
    await load();
  } catch (e) {
    error.value = extractApiError(e, 'Failed to update user');
  } finally {
    savingEdit.value = false;
  }
}

async function copyCredentials() {
  if (!createdCredentials.value) return;
  await navigator.clipboard.writeText(`Email: ${createdCredentials.value.email}\nPassword: ${createdCredentials.value.tempPassword}`);
}

function openQuickMessage(user) {
  if (!user?.id) return;
  quickMessageUserId.value = String(user.id);
  quickMessageBody.value = '';
  quickMessageOpen.value = true;
}

async function sendQuickMessage() {
  if (!quickMessageTarget.value?.id) return;
  if (!quickMessageBody.value.trim()) return;

  try {
    sendingQuickMessage.value = true;
    error.value = '';
    await apiPost('/messages', {
      recipient_id: Number(quickMessageTarget.value.id),
      body: String(quickMessageBody.value || '').trim(),
    });
    quickMessageOpen.value = false;
    quickMessageBody.value = '';
  } catch (e) {
    error.value = extractApiError(e, 'Failed to send message');
  } finally {
    sendingQuickMessage.value = false;
  }
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

<style scoped>
.app-input {
  width: 100%;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  border-radius: var(--radius-lg);
  border: 1px solid var(--aq-border);
  background: var(--aq-surface-2);
  color: var(--aq-fg);
  transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
}

.app-input::placeholder {
  color: var(--aq-muted);
}

.app-input:focus {
  outline: none;
  border-color: color-mix(in srgb, var(--aq-primary) 50%, transparent);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--aq-primary) 10%, transparent);
}
</style>
