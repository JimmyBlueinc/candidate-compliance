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

async function loadUnreadCount() {
  try {
    const res = await apiGet('/messages/unread-count');
    unreadCount.value = res?.count || 0;
  } catch (e) {
    // Silently fail
  }
}

function goMessages() {
  router.push({ name: 'dashboard.messages' });
}

onMounted(() => {
  loadUnreadCount();
  // Poll every 30 seconds
  const timer = setInterval(loadUnreadCount, 30000);
  onUnmounted(() => clearInterval(timer));
});
</script>
