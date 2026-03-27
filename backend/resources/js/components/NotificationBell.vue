<template>
  <div class="relative">
    <button type="button" 
            class="relative w-8 h-8 rounded-full bg-[color:var(--p-surface-0)] border border-[color:var(--p-surface-border)] flex items-center justify-center text-[color:var(--p-text-muted-color)] hover:bg-[color:var(--p-surface-hover)] hover:text-[color:var(--p-text-color)] transition-all"
            @click="togglePanel">
      <span class="material-symbols-outlined text-[18px]">notifications</span>
      <span v-if="unreadCount > 0" 
            class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 border-2 border-[color:var(--p-surface-card)] rounded-full text-[9px] font-bold flex items-center justify-center text-white">
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <div v-if="isOpen" 
         class="absolute right-0 mt-2 w-72 rounded-xl border border-[color:var(--p-surface-border)] bg-[color:var(--p-surface-card)] shadow-xl z-50 overflow-hidden">
      <div class="p-3 border-b border-[color:var(--p-surface-border)] flex items-center justify-between">
        <h3 class="font-semibold text-sm text-[color:var(--p-text-color)]">Notifications</h3>
        <button v-if="unreadCount > 0" 
                class="text-[10px] font-medium text-[color:var(--p-primary-color)] hover:underline"
                @click="markAllRead">
          Mark all read
        </button>
      </div>

      <div class="max-h-72 overflow-y-auto custom-scrollbar">
        <div v-if="loading && items.length === 0" class="p-6 text-center">
          <i class="pi pi-spin pi-spinner text-[color:var(--p-primary-color)]"></i>
        </div>
        
        <div v-else-if="items.length === 0" class="p-6 text-center">
          <span class="material-symbols-outlined text-[color:var(--p-text-muted-color)] text-3xl block mb-2">notifications_off</span>
          <p class="text-xs text-[color:var(--p-text-muted-color)]">No notifications</p>
        </div>

        <div v-else>
          <div v-for="n in items" :key="n.id" 
               class="p-3 border-b border-[color:var(--p-surface-border)] hover:bg-[color:var(--p-surface-hover)] transition-colors cursor-pointer"
               :class="{'bg-[color:var(--p-primary-color)]/5': !n.read_at}"
               @click="handleAction(n)">
            <div class="flex gap-2">
              <div class="w-6 h-6 rounded-full bg-[color:var(--p-surface-100)] flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-xs" :class="getTypeColor(n.type)">
                  {{ getTypeIcon(n.type) }}
                </span>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-xs text-[color:var(--p-text-color)] leading-snug">{{ n.data?.message || 'New notification' }}</p>
                <p class="text-[10px] text-[color:var(--p-text-muted-color)] mt-0.5">{{ formatTime(n.created_at) }}</p>
              </div>
              <div v-if="!n.read_at" class="w-1.5 h-1.5 rounded-full bg-[color:var(--p-primary-color)] mt-1"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="p-2 border-t border-[color:var(--p-surface-border)] text-center">
        <button class="text-[10px] font-medium text-[color:var(--p-text-muted-color)] hover:text-[color:var(--p-text-color)] transition-colors"
                @click="isOpen = false">
          Close
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, onUnmounted } from 'vue';
import { apiGet, apiPost } from '../../lib/api';

const isOpen = ref(false);
const items = ref([]);
const loading = ref(false);
const unreadCount = computed(() => items.value.filter(i => !i.read_at).length);

async function loadNotifications() {
  try {
    const res = await apiGet('/notifications');
    items.value = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : []);
  } catch (e) {
    console.error('Failed to load notifications:', e);
  }
}

async function markAllRead() {
  try {
    await apiPost('/notifications/read-all');
    items.value.forEach(i => i.read_at = new Date().toISOString());
  } catch (e) {
    console.error('Failed to mark all as read:', e);
  }
}

async function handleAction(n) {
  if (!n.read_at) {
    try {
      await apiPost(`/notifications/${n.id}/read`);
      n.read_at = new Date().toISOString();
    } catch (e) {
      console.error('Failed to mark notification as read:', e);
    }
  }
  
  // Handle navigation based on n.type if needed
  isOpen.value = false;
}

function getTypeIcon(type) {
  switch (type) {
    case 'message':
    case 'new_message': return 'chat';
    case 'job': return 'work';
    case 'shift': return 'calendar_month';
    case 'credential': return 'badge';
    default: return 'notifications';
  }
}

function getTypeColor(type) {
  switch (type) {
    case 'message':
    case 'new_message': return 'text-blue-400';
    case 'job': return 'text-emerald-400';
    case 'shift': return 'text-amber-400';
    case 'credential': return 'text-purple-400';
    default: return 'text-slate-400';
  }
}

function formatTime(dateStr) {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  const now = new Date();
  const diffMs = now - d;
  const diffMin = Math.floor(diffMs / 60000);
  
  if (diffMin < 1) return 'Just now';
  if (diffMin < 60) return `${diffMin}m ago`;
  
  const diffHr = Math.floor(diffMin / 60);
  if (diffHr < 24) return `${diffHr}h ago`;
  
  return d.toLocaleDateString();
}

function togglePanel() {
  isOpen.value = !isOpen.value;
  if (isOpen.value) loadNotifications();
}

let pollTimer = null;
onMounted(() => {
  loadNotifications();
  pollTimer = setInterval(loadNotifications, 30000);
});

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer);
});
</script>

<style scoped>
@keyframes fadeInScale {
  from { opacity: 0; transform: scale(0.95) translateY(-10px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 10px;
}
</style>
