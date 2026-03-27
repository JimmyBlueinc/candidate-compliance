<template>
  <div class="flex flex-col h-[600px] glass-dark rounded-[24px] overflow-hidden border border-white/5 shadow-2xl">
    <!-- Header -->
    <div class="p-4 border-b border-white/10 flex items-center justify-between bg-white/5">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center border border-primary/30">
          <span class="material-symbols-outlined text-primary">chat_bubble</span>
        </div>
        <div>
          <h3 class="font-display text-lg text-white leading-tight">Messages</h3>
          <p class="text-[10px] uppercase tracking-widest text-[color:var(--p-text-muted-color)] font-black">
            {{ contextTitle || 'Support Chat' }}
          </p>
        </div>
      </div>
      <Button icon="pi pi-refresh" severity="secondary" text rounded size="small" @click="loadMessages" :loading="loading" />
    </div>

    <!-- Messages List -->
    <div ref="messageContainer" class="flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar bg-black/20">
      <div v-if="loading && messages.length === 0" class="flex flex-col items-center justify-center h-full space-y-2">
        <i class="pi pi-spin pi-spinner text-primary text-2xl"></i>
        <p class="text-xs text-[color:var(--p-text-muted-color)]">Loading conversation...</p>
      </div>
      
      <div v-else-if="messages.length === 0" class="flex flex-col items-center justify-center h-full text-center px-6 space-y-3">
        <div class="w-16 h-16 rounded-full bg-white/5 flex items-center justify-center">
          <span class="material-symbols-outlined text-3xl text-white/20">forum</span>
        </div>
        <p class="text-sm text-[color:var(--p-text-muted-color)]">No messages yet. Start the conversation below.</p>
      </div>

      <template v-else>
        <div v-for="msg in messages" :key="msg.id" 
             class="flex flex-col"
             :class="[msg.user_id === auth.user.id ? 'items-end' : 'items-start']">
          <div class="flex items-end gap-2 max-w-[85%]">
            <div v-if="msg.user_id !== auth.user.id" class="w-6 h-6 rounded-full overflow-hidden shrink-0 border border-white/10">
              <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(msg.user?.name || 'U')}&background=random&color=fff`" class="w-full h-full object-cover" />
            </div>
            
            <div class="px-4 py-2 rounded-[20px] text-sm shadow-sm relative group"
                 :class="[
                   msg.user_id === auth.user.id 
                     ? 'bg-primary text-white rounded-br-none' 
                     : 'bg-white/10 text-slate-200 border border-white/5 rounded-bl-none'
                 ]">
              {{ msg.body }}
              
              <div v-if="msg.created_at" class="text-[9px] mt-1 opacity-50 block" :class="[msg.user_id === auth.user.id ? 'text-right' : 'text-left']">
                {{ formatTime(msg.created_at) }}
              </div>
            </div>
          </div>
          <div class="text-[9px] mt-1 text-[color:var(--p-text-muted-color)] px-1" v-if="msg.user_id !== auth.user.id">
            {{ msg.user?.name }} • {{ msg.user?.role }}
          </div>
        </div>
      </template>
    </div>

    <!-- Input Area -->
    <div class="p-4 border-t border-white/10 bg-white/5">
      <form @submit.prevent="sendMessage" class="flex gap-2">
        <InputText v-model="newMessage" 
                  placeholder="Type a message..." 
                  class="flex-1 !bg-white/5 !border-white/10 !rounded-full !px-4 text-sm"
                  :disabled="sending" />
        <Button type="submit" 
                icon="pi pi-send" 
                rounded 
                :loading="sending" 
                :disabled="!newMessage.trim()" />
      </form>

      <Message
        v-if="sendError"
        severity="error"
        :closable="false"
        class="mt-3"
      >
        {{ sendError }}
      </Message>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { apiGet, apiPost } from '../../lib/api';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';

const props = defineProps({
  jobOrderId: { type: Number, default: null },
  submissionId: { type: Number, default: null },
  placementId: { type: Number, default: null },
  recipientId: { type: Number, default: null },
  contextTitle: { type: String, default: '' }
});

const auth = useAuthStore();
const messages = ref([]);
const loading = ref(false);
const sending = ref(false);
const newMessage = ref('');
const sendError = ref('');
const messageContainer = ref(null);

async function loadMessages() {
  if (loading.value) return;
  loading.value = true;
  try {
    const params = {};
    if (props.jobOrderId) params.job_order_id = props.jobOrderId;
    else if (props.submissionId) params.submission_id = props.submissionId;
    else if (props.placementId) params.placement_id = props.placementId;
    else if (props.recipientId) params.recipient_id = props.recipientId;
    else return; // Need a context

    const res = await apiGet('/messages', { params });
    messages.value = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : []);
    await scrollToBottom();
  } catch (e) {
    console.error('Failed to load messages:', e);
  } finally {
    loading.value = false;
  }
}

async function sendMessage() {
  if (!newMessage.value.trim() || sending.value) return;

  if (!props.jobOrderId && !props.submissionId && !props.placementId && !props.recipientId) {
    sendError.value = 'Select a conversation before sending a message.';
    return;
  }

  sending.value = true;
  sendError.value = '';
  try {
    const payload = { body: newMessage.value };
    if (props.jobOrderId) payload.job_order_id = props.jobOrderId;
    else if (props.submissionId) payload.submission_id = props.submissionId;
    else if (props.placementId) payload.placement_id = props.placementId;
    else if (props.recipientId) payload.recipient_id = props.recipientId;

    const res = await apiPost('/messages', payload);
    const msg = res?.data ?? res;
    if (msg && typeof msg === 'object' && msg.id) {
      messages.value.push(msg);
      newMessage.value = '';
      await scrollToBottom();
    } else {
      newMessage.value = '';
      await loadMessages();
    }
  } catch (e) {
    console.error('Failed to send message:', e);
    sendError.value = e?.response?.data?.message || e?.message || 'Failed to send message.';
  } finally {
    sending.value = false;
  }
}

async function scrollToBottom() {
  await nextTick();
  if (messageContainer.value) {
    messageContainer.value.scrollTop = messageContainer.value.scrollHeight;
  }
}

function formatTime(dateStr) {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

let pollInterval = null;

onMounted(() => {
  loadMessages();
  // Poll for new messages every 10 seconds as a fallback since we don't have WebSockets fully set up for this yet
  pollInterval = setInterval(loadMessages, 10000);
});

onUnmounted(() => {
  if (pollInterval) {
    clearInterval(pollInterval);
  }
});

watch(() => [props.jobOrderId, props.submissionId, props.placementId, props.recipientId], () => {
  loadMessages();
});
</script>

<style scoped>
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
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.2);
}
</style>
