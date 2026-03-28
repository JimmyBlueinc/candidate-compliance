<template>
  <div class="relative">
    <button
      type="button"
      class="relative w-8 h-8 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 hover:bg-emerald-500/20 hover:border-emerald-500/30 transition-all"
      @click="togglePanel"
      title="Online Team Members"
    >
      <Users class="w-4 h-4" />
      <span
        v-if="onlineUsers.length > 0"
        class="absolute -top-1 -right-1 w-4 h-4 bg-emerald-500 border-2 border-[color:var(--aq-surface-card)] rounded-full text-[9px] font-bold flex items-center justify-center text-white"
      >
        {{ onlineUsers.length > 9 ? '9+' : onlineUsers.length }}
      </span>
    </button>

    <!-- Dropdown Panel -->
    <div
      v-if="isOpen"
      class="absolute right-0 mt-2 w-64 rounded-xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-card)] shadow-xl z-50 overflow-hidden"
    >
      <div class="p-3 border-b border-[color:var(--aq-border)] flex items-center justify-between">
        <h3 class="font-semibold text-sm text-[color:var(--aq-fg)]">Online Team</h3>
        <span class="text-[10px] font-medium text-emerald-400 flex items-center gap-1">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          {{ onlineUsers.length }} active
        </span>
      </div>

      <div class="max-h-64 overflow-y-auto">
        <div v-if="loading" class="p-6 text-center">
          <RefreshCw class="w-5 h-5 text-[color:var(--aq-muted)] animate-spin mx-auto" />
        </div>

        <div v-else-if="onlineUsers.length === 0" class="p-6 text-center">
          <Users class="w-8 h-8 text-[color:var(--aq-muted)] mx-auto mb-2" />
          <p class="text-xs text-[color:var(--aq-muted)]">No team members online</p>
        </div>

        <div v-else>
          <button
            v-for="user in onlineUsers"
            :key="user.id"
            type="button"
            class="w-full p-3 flex items-center gap-3 hover:bg-[color:var(--aq-surface-2)] transition-colors border-b border-[color:var(--aq-border)] last:border-b-0"
            @click="startChat(user)"
          >
            <div class="relative">
              <div class="w-9 h-9 rounded-full bg-[color:var(--aq-primary)]/20 flex items-center justify-center text-[color:var(--aq-primary)] font-bold text-sm overflow-hidden">
                <span v-if="!user.avatar">{{ user.name?.charAt(0) || 'U' }}</span>
                <img v-else :src="user.avatar" class="w-full h-full object-cover" />
              </div>
              <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-[color:var(--aq-surface-card)] bg-emerald-500"></span>
            </div>
            <div class="flex-1 min-w-0 text-left">
              <p class="text-sm font-medium text-[color:var(--aq-fg)] truncate">{{ user.name }}</p>
              <p class="text-[11px] text-[color:var(--aq-muted)]">{{ formatRole(user.role) }}</p>
            </div>
            <MessageSquare class="w-4 h-4 text-cyan-400 shrink-0" />
          </button>
        </div>
      </div>

      <div class="p-2 border-t border-[color:var(--aq-border)] text-center">
        <button
          class="text-[10px] font-medium text-[color:var(--aq-muted)] hover:text-[color:var(--aq-fg)] transition-colors"
          @click="isOpen = false"
        >
          Close
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { Users, MessageSquare, RefreshCw } from 'lucide-vue-next';
import { apiGet } from '../../lib/api';

const router = useRouter();
const isOpen = ref(false);
const loading = ref(false);
const onlineUsers = ref([]);

async function loadOnlineUsers() {
  loading.value = true;
  try {
    const res = await apiGet('/users/online');
    onlineUsers.value = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : []);
  } catch (e) {
    // Silently fail - online status is optional
    console.error('Failed to load online users:', e);
  } finally {
    loading.value = false;
  }
}

function togglePanel() {
  isOpen.value = !isOpen.value;
  if (isOpen.value) loadOnlineUsers();
}

function startChat(user) {
  isOpen.value = false;
  router.push({ name: 'dashboard.messages', query: { recipient: user.id } });
}

function formatRole(role) {
  if (!role) return '';
  const roleMap = {
    'org_super_admin': 'Administrator',
    'admin': 'Admin',
    'recruiter': 'Recruiter',
    'compliance': 'Compliance',
    'scheduler': 'Scheduler',
    'finance': 'Finance',
    'logistics': 'Logistics',
  };
  return roleMap[role] || role.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

let pollTimer = null;
onMounted(() => {
  loadOnlineUsers();
  // Poll every 30 seconds for online status
  pollTimer = setInterval(loadOnlineUsers, 30000);
});

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer);
});
</script>
