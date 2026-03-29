<template>
  <div class="space-y-6">
    <div class="glass-dark rounded-[32px] p-8 border border-white/5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="font-display text-2xl text-white">Platform Health</h2>
          <p class="text-sm text-[color:var(--p-text-muted-color)] mt-1">Global organization health & broadcast messaging.</p>
        </div>
        <button
          type="button"
          class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
          :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
          @click="refresh"
        >
          Refresh
        </button>
      </div>

      <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="p-6 rounded-2xl bg-white/[0.03] border border-white/5">
          <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Global Broadcast</div>
          <div class="mt-2 text-sm text-slate-300">Send a system-wide message shown at the top of every screen.</div>

          <textarea
            v-model="message"
            class="mt-4 w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white"
            rows="4"
            placeholder="System maintenance tonight at 10pm..."
          ></textarea>

          <div class="mt-4 flex items-center gap-2">
            <button
              type="button"
              class="px-4 py-3 rounded-2xl text-xs font-black tracking-widest uppercase border transition-colors"
              :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
              :disabled="saving"
              @click="send"
            >
              {{ saving ? 'Sending…' : 'Broadcast' }}
            </button>

            <button
              type="button"
              class="px-4 py-3 rounded-2xl text-xs font-black tracking-widest uppercase border border-red-500/30 bg-red-500/10 text-red-400 hover:bg-red-500/15"
              :disabled="clearing"
              @click="clear"
            >
              {{ clearing ? 'Clearing…' : 'Clear' }}
            </button>
          </div>

          <div v-if="status" class="mt-3 text-sm text-[color:var(--p-text-muted-color)]">{{ status }}</div>
        </div>

        <div class="p-6 rounded-2xl bg-white/[0.03] border border-white/5">
          <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Organizations</div>
          <div class="mt-2 text-sm text-slate-300">Active placement counts by organization.</div>

          <div v-if="loading" class="mt-4 text-sm text-[color:var(--p-text-muted-color)]">Loading...</div>
          <div v-else class="mt-4 space-y-3">
            <div
              v-for="t in tenants"
              :key="t.tenant_id"
              class="p-4 rounded-2xl border border-white/5 bg-white/[0.02]"
            >
              <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                  <div class="font-semibold text-white truncate">{{ t.name }}</div>
                  <div class="mt-1 text-xs text-[color:var(--p-text-muted-color)] truncate">{{ t.slug || '—' }}</div>
                </div>
                <div class="shrink-0 text-right">
                  <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Active</div>
                  <div class="mt-1 text-lg font-display" :style="{ color: primaryColor }">{{ t.active_placements }}</div>
                </div>
              </div>
            </div>

            <div v-if="tenants.length === 0" class="text-sm text-[color:var(--p-text-muted-color)]">No organizations found.</div>
          </div>
        </div>
      </div>

      <div class="mt-6 rounded-2xl bg-white/[0.03] border border-white/5 p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Workforce Analytics</div>
            <div class="mt-1 text-sm text-slate-300">Login time, last active, session duration, and activity level for all users.</div>
          </div>
          <div class="flex gap-2">
            <select
              v-model="workforceRole"
              class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs text-white"
            >
              <option value="">All roles</option>
              <option value="platform_admin">Platform Admin</option>
              <option value="org_super_admin">Org Super Admin</option>
              <option value="admin">Admin</option>
              <option value="recruiter">Recruiter</option>
              <option value="candidate">Candidate</option>
              <option value="facility">Facility</option>
            </select>
            <select
              v-model="workforceSort"
              class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs text-white"
            >
              <option value="last_active">Sort: Last Active</option>
              <option value="login_time">Sort: Login Time</option>
              <option value="session_duration">Sort: Session Duration</option>
              <option value="name">Sort: Name</option>
            </select>
            <input
              v-model="workforceSearch"
              type="text"
              placeholder="Search users..."
              class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs text-white"
              @keyup.enter="loadWorkforce"
            />
            <button
              type="button"
              class="px-3 py-2 rounded-xl text-xs font-bold border transition-colors"
              :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
              @click="loadWorkforce"
            >
              Refresh
            </button>
          </div>
        </div>

        <div class="mt-4 overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left text-[11px] uppercase tracking-[0.16em] text-[color:var(--p-text-muted-color)]">
                <th class="py-2 pr-4">User</th>
                <th class="py-2 pr-4">Role</th>
                <th class="py-2 pr-4">Organization</th>
                <th class="py-2 pr-4">Login</th>
                <th class="py-2 pr-4">Last Active</th>
                <th class="py-2 pr-4">Session</th>
                <th class="py-2 pr-4">Activity</th>
                <th class="py-2 pr-2 text-right">Message</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in workforceRows"
                :key="row.id"
                class="border-t border-white/5 text-slate-200"
              >
                <td class="py-3 pr-4">
                  <div class="font-semibold text-white">{{ row.name }}</div>
                  <div class="text-xs text-[color:var(--p-text-muted-color)]">{{ row.email }}</div>
                </td>
                <td class="py-3 pr-4 capitalize">{{ row.role }}</td>
                <td class="py-3 pr-4">{{ row.organization_name || 'N/A' }}</td>
                <td class="py-3 pr-4">{{ formatDateTime(row.login_time) }}</td>
                <td class="py-3 pr-4">{{ formatDateTime(row.last_active_time) }}</td>
                <td class="py-3 pr-4">{{ formatSession(row.session_duration_minutes) }}</td>
                <td class="py-3 pr-4">
                  <span class="px-2 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
                    :class="row.activity_level === 'high' ? 'bg-emerald-500/20 text-emerald-300' : row.activity_level === 'medium' ? 'bg-amber-500/20 text-amber-300' : 'bg-slate-600/30 text-slate-300'">
                    {{ row.activity_level }}
                  </span>
                </td>
                <td class="py-3 pr-2 text-right">
                  <button
                    type="button"
                    class="inline-flex items-center px-2.5 py-1.5 rounded-lg border border-white/10 bg-white/5 text-xs font-semibold hover:bg-white/10"
                    :disabled="!row.organization_id"
                    :class="!row.organization_id ? 'opacity-50 cursor-not-allowed' : ''"
                    :title="row.organization_id ? 'Send direct message' : 'Direct messaging unavailable for users without organization context'"
                    @click="openMessageModal(row)"
                  >
                    <i class="pi pi-send mr-1" />
                    Message
                  </button>
                </td>
              </tr>
              <tr v-if="!workforceLoading && workforceRows.length === 0">
                <td colspan="8" class="py-6 text-center text-sm text-[color:var(--p-text-muted-color)]">No users found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <Dialog v-model:visible="messageDialogOpen" modal header="Quick Message" :style="{ width: 'min(580px, 95vw)' }">
      <div class="space-y-4">
        <div class="text-sm text-slate-300">
          Send a direct message to <span class="font-semibold text-white">{{ selectedRecipient?.name || 'user' }}</span>.
        </div>
        <textarea
          v-model="quickMessageBody"
          rows="4"
          class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white"
          placeholder="Type your message..."
        />
        <div class="flex justify-end gap-2">
          <button type="button" class="px-3 py-2 rounded-xl border border-white/10 bg-white/5 text-xs font-semibold" @click="messageDialogOpen = false">
            Cancel
          </button>
          <button
            type="button"
            class="px-3 py-2 rounded-xl text-xs font-semibold"
            :style="{ backgroundColor: primaryColor, color: '#fff' }"
            :disabled="quickMessageSending"
            @click="sendQuickMessage"
          >
            {{ quickMessageSending ? 'Sending...' : 'Send Message' }}
          </button>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { apiGet, apiPost } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';
import Dialog from 'primevue/dialog';

const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const loading = ref(false);
const tenants = ref([]);

const message = ref('');
const saving = ref(false);
const clearing = ref(false);
const status = ref('');
const workforceRows = ref([]);
const workforceLoading = ref(false);
const workforceSearch = ref('');
const workforceRole = ref('');
const workforceSort = ref('last_active');
const messageDialogOpen = ref(false);
const selectedRecipient = ref(null);
const quickMessageBody = ref('');
const quickMessageSending = ref(false);

async function refresh() {
    loading.value = true;
    try {
        const res = await apiGet('/v1/admin/platform-health');
        tenants.value = Array.isArray(res?.data) ? res.data : [];
    } finally {
        loading.value = false;
    }
}

async function loadWorkforce() {
    workforceLoading.value = true;
    try {
        const res = await apiGet('/v1/admin/workforce', {
            params: {
                search: workforceSearch.value || undefined,
                role: workforceRole.value || undefined,
                sort: workforceSort.value || 'last_active',
            },
        });
        workforceRows.value = Array.isArray(res?.data) ? res.data : [];
    } finally {
        workforceLoading.value = false;
    }
}

async function send() {
    saving.value = true;
    status.value = '';
    try {
        await apiPost('/v1/admin/system-message', { message: message.value, is_active: true });
        status.value = 'Broadcast sent.';
    } finally {
        saving.value = false;
    }
}

async function clear() {
    clearing.value = true;
    status.value = '';
    try {
        await apiPost('/v1/admin/system-message/clear');
        status.value = 'Broadcast cleared.';
    } finally {
        clearing.value = false;
    }
}

function openMessageModal(row) {
    selectedRecipient.value = row;
    quickMessageBody.value = '';
    messageDialogOpen.value = true;
}

async function sendQuickMessage() {
    if (!selectedRecipient.value?.id || !quickMessageBody.value.trim()) return;
    quickMessageSending.value = true;
    try {
        await apiPost('/v1/admin/workforce/message', {
            recipient_id: selectedRecipient.value.id,
            body: quickMessageBody.value.trim(),
        });
        messageDialogOpen.value = false;
        status.value = 'Quick message sent successfully.';
    } finally {
        quickMessageSending.value = false;
    }
}

function formatDateTime(value) {
    if (!value) return 'N/A';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return 'N/A';
    return date.toLocaleString();
}

function formatSession(minutes) {
    const mins = Number(minutes);
    if (!Number.isFinite(mins) || mins < 0) return 'N/A';
    const hours = Math.floor(mins / 60);
    const remainder = mins % 60;
    return hours > 0 ? `${hours}h ${remainder}m` : `${remainder}m`;
}

refresh();
loadWorkforce();
</script>
