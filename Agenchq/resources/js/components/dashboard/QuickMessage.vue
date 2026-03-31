<template>
  <div class="relative">
    <button
      type="button"
      class="relative w-8 h-8 rounded-full bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400 hover:bg-cyan-500/20 hover:border-cyan-500/30 transition-all"
      title="Quick Message"
      @click="goMessages"
    >
      <MessageCircle class="w-4 h-4" />
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 w-4 h-4 bg-cyan-500 border-2 border-[color:var(--aq-surface-card)] rounded-full text-[9px] font-bold flex items-center justify-center text-white"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { MessageCircle } from 'lucide-vue-next';
import { apiGet } from '../../lib/api';

const router = useRouter();
const unreadCount = ref(0);
let timer = null;

async function loadUnreadCount() {
  try {
    const res = await apiGet('/messages/unread-count');
    unreadCount.value = res?.count || 0;
  } catch (e) {
    // Silently fail
  }
}

async function goMessages() {
  try {
    const res = await apiGet('/org/chat-users', { params: { q: '' }, timeout: 12000 });
    const rows = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : []);
    const target = rows
      .slice()
      .sort((a, b) => Number(b.unread_count || 0) - Number(a.unread_count || 0))
      .find((u) => Number(u.unread_count || 0) > 0);

    if (target?.id) {
      router.push({ name: 'dashboard.messages', query: { recipient_id: target.id } });
      return;
    }
  } catch (_e) {
    // Fallback to general inbox
  }

  router.push({ name: 'dashboard.messages' });
}

onMounted(() => {
  loadUnreadCount();
  timer = setInterval(loadUnreadCount, 30000);
});

onUnmounted(() => {
  if (timer) clearInterval(timer);
  timer = null;
});
</script>
