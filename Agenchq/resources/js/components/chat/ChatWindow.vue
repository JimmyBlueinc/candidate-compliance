<template>
  <div class="flex flex-col h-[600px] rounded-[24px] overflow-hidden border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-card)] shadow-[var(--shadow-premium)]">
    <!-- Header -->
    <div class="p-4 border-b border-[color:var(--aq-border)] flex items-center justify-between bg-[color:var(--aq-surface-2)]">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-[color:var(--aq-primary)]/15 flex items-center justify-center border border-[color:var(--aq-primary)]/30">
          <span class="material-symbols-outlined text-[color:var(--aq-primary)]">chat_bubble</span>
        </div>
        <div>
          <h3 class="font-display text-lg text-[color:var(--aq-fg)] leading-tight">Messages</h3>
          <p class="text-[10px] uppercase tracking-widest text-[color:var(--aq-muted)] font-black">
            {{ contextTitle || 'Support Chat' }}
          </p>
        </div>
      </div>
      <Button icon="pi pi-refresh" severity="secondary" text rounded size="small" @click="loadMessages" :loading="loading" />
    </div>

    <!-- Messages List -->
    <div ref="messageContainer" class="flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar bg-[color:var(--aq-surface-1)]/55">
      <div v-if="loading && messages.length === 0" class="flex flex-col items-center justify-center h-full space-y-2">
        <i class="pi pi-spin pi-spinner text-[color:var(--aq-primary)] text-2xl"></i>
        <p class="text-xs text-[color:var(--aq-muted)]">Loading conversation...</p>
      </div>
      
      <div v-else-if="messages.length === 0" class="flex flex-col items-center justify-center h-full text-center px-6 space-y-3">
        <div class="w-16 h-16 rounded-full bg-[color:var(--aq-primary)]/10 flex items-center justify-center">
          <span class="material-symbols-outlined text-3xl text-[color:var(--aq-primary)]/45">forum</span>
        </div>
        <p class="text-sm text-[color:var(--aq-muted)]">No messages yet. Start the conversation below.</p>
      </div>

      <template v-else>
        <div v-for="msg in messages" :key="msg.id" 
             class="flex flex-col"
             :class="[msg.user_id === auth.user.id ? 'items-end' : 'items-start']">
          <div class="flex items-end gap-2 max-w-[85%]">
            <div v-if="msg.user_id !== auth.user.id" class="w-6 h-6 rounded-full overflow-hidden shrink-0 border border-[color:var(--aq-border)]">
              <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(msg.user?.name || 'Deleted User')}&background=random&color=fff`" class="w-full h-full object-cover" />
            </div>
            
            <div class="px-4 py-2 rounded-[20px] text-sm shadow-sm relative group"
                 :class="[
                   msg.user_id === auth.user.id 
                     ? 'bg-primary text-white rounded-br-none' 
                    : 'bg-[color:var(--aq-surface-2)] text-[color:var(--aq-fg)] border border-[color:var(--aq-border)] rounded-bl-none'
                 ]">
              {{ msg.body }}
              
              <div v-if="msg.created_at" class="text-[9px] mt-1 opacity-50 block" :class="[msg.user_id === auth.user.id ? 'text-right' : 'text-left']">
                {{ formatTime(msg.created_at) }}
              </div>
            </div>
          </div>
          <div class="text-[9px] mt-1 text-[color:var(--aq-muted)] px-1" v-if="msg.user_id !== auth.user.id">
            {{ msg.user?.name || 'Deleted User' }} • {{ formatRole(msg.user?.role) }}
          </div>
        </div>
      </template>
    </div>

    <!-- Input Area -->
    <div class="p-4 border-t border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)]/55">
      <form @submit.prevent="sendMessage" class="flex gap-2">
        <input
          ref="attachmentInput"
          type="file"
          class="hidden"
          @change="onAttachmentSelected"
        />
        <Button
          type="button"
          icon="pi pi-paperclip"
          rounded
          severity="secondary"
          text
          :disabled="sending"
          @click="openAttachmentPicker"
        />
        <InputText v-model="newMessage" 
                  placeholder="Type a message..." 
                  class="flex-1 !bg-[color:var(--aq-surface-card)] !border-[color:var(--aq-border)] !rounded-full !px-4 text-sm"
                  :disabled="sending" />
        <Button type="submit" 
                icon="pi pi-send" 
                rounded 
                :loading="sending || uploadingAttachment" 
                :disabled="!newMessage.trim() && !selectedAttachment" />
      </form>
      <div v-if="selectedAttachment" class="mt-2 text-[11px] text-[color:var(--aq-muted)] truncate">
        Ready to send: {{ selectedAttachment.name }}
      </div>

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
  groupChannel: { type: String, default: '' },
  contextTitle: { type: String, default: '' }
});

const auth = useAuthStore();
const messages = ref([]);
const loading = ref(false);
const sending = ref(false);
const newMessage = ref('');
const sendError = ref('');
const messageContainer = ref(null);
const hasLoadedOnce = ref(false);
const lastLoadedMessageId = ref(0);
const attachmentInput = ref(null);
const selectedAttachment = ref(null);
const uploadingAttachment = ref(false);

async function loadMessages({ incremental = false } = {}) {
  if (loading.value) return;
  loading.value = true;
  try {
    const params = {};
    if (props.groupChannel) params.group_channel = props.groupChannel;
    else if (props.jobOrderId) params.job_order_id = props.jobOrderId;
    else if (props.submissionId) params.submission_id = props.submissionId;
    else if (props.placementId) params.placement_id = props.placementId;
    else if (props.recipientId) params.recipient_id = props.recipientId;
    else return; // Need a context

    params.limit = incremental ? 80 : 120;
    if (incremental && lastLoadedMessageId.value > 0) {
      params.since_id = lastLoadedMessageId.value;
    }

    const res = await apiGet('/messages', {
      params,
      timeout: 20000,
    });
    const incoming = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : []);

    if (!incremental || !hasLoadedOnce.value) {
      messages.value = incoming;
      hasLoadedOnce.value = true;
      lastLoadedMessageId.value = messages.value.length > 0
        ? Number(messages.value[messages.value.length - 1].id || 0)
        : 0;
      await scrollToBottom();
      return;
    }

    if (incoming.length === 0) return;

    const existingIds = new Set(messages.value.map((m) => Number(m.id)));
    const next = incoming.filter((m) => !existingIds.has(Number(m.id)));
    if (next.length === 0) return;

    const shouldStick = isNearBottom();
    messages.value.push(...next);
    lastLoadedMessageId.value = Number(messages.value[messages.value.length - 1]?.id || lastLoadedMessageId.value || 0);
    if (shouldStick) {
      await scrollToBottom();
    }
  } catch (e) {
    console.error('Failed to load messages:', e);
  } finally {
    loading.value = false;
  }
}

async function sendMessage() {
  if ((!newMessage.value.trim() && !selectedAttachment.value) || sending.value) return;

  if (!props.groupChannel && !props.jobOrderId && !props.submissionId && !props.placementId && !props.recipientId) {
    sendError.value = 'Select a conversation before sending a message.';
    return;
  }

  sending.value = true;
  sendError.value = '';
  try {
    let attachmentSnippet = '';
    if (selectedAttachment.value) {
      uploadingAttachment.value = true;
      const formData = new FormData();
      formData.append('file', selectedAttachment.value);
      const uploadRes = await apiPost('/drive/files', formData, { timeout: 180000 });
      const uploadPayload = uploadRes?.data || uploadRes || {};
      const uploaded = uploadPayload?.file || uploadPayload;
      if (uploaded?.download_url) {
        attachmentSnippet = `Attachment: ${uploaded.name || 'file'}\n${uploaded.download_url}`;
      }
    }

    const messageBody = [newMessage.value.trim(), attachmentSnippet].filter(Boolean).join('\n\n');
    if (!messageBody) return;

    const payload = { body: messageBody };
    if (props.groupChannel) payload.group_channel = props.groupChannel;
    else if (props.jobOrderId) payload.job_order_id = props.jobOrderId;
    else if (props.submissionId) payload.submission_id = props.submissionId;
    else if (props.placementId) payload.placement_id = props.placementId;
    else if (props.recipientId) payload.recipient_id = props.recipientId;

    const res = await apiPost('/messages', payload);
    const msg = res?.data ?? res;
    if (msg && typeof msg === 'object' && msg.id) {
      messages.value.push(msg);
      lastLoadedMessageId.value = Math.max(lastLoadedMessageId.value, Number(msg.id || 0));
      newMessage.value = '';
      selectedAttachment.value = null;
      if (attachmentInput.value) attachmentInput.value.value = '';
      await scrollToBottom();
    } else {
      newMessage.value = '';
      selectedAttachment.value = null;
      if (attachmentInput.value) attachmentInput.value.value = '';
      await loadMessages({ incremental: true });
    }
  } catch (e) {
    console.error('Failed to send message:', e);
    sendError.value = e?.response?.data?.message || e?.message || 'Failed to send message.';
  } finally {
    sending.value = false;
    uploadingAttachment.value = false;
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

function formatRole(role) {
  const roleMap = {
    org_super_admin: 'HR Admin',
    platform_admin: 'Platform Admin',
    admin: 'HR',
    recruiter: 'HR',
    scheduler: 'Scheduler',
    compliance: 'Compliance',
    finance: 'Finance',
    logistics: 'Logistics',
    candidate: 'Candidate',
  };
  return roleMap[String(role || '').toLowerCase()] || 'User';
}

function isNearBottom() {
  if (!messageContainer.value) return true;
  const c = messageContainer.value;
  const remaining = c.scrollHeight - c.scrollTop - c.clientHeight;
  return remaining < 80;
}

function resetConversation() {
  messages.value = [];
  hasLoadedOnce.value = false;
  lastLoadedMessageId.value = 0;
  sendError.value = '';
  selectedAttachment.value = null;
  if (attachmentInput.value) attachmentInput.value.value = '';
}

function onAttachmentSelected(event) {
  selectedAttachment.value = event?.target?.files?.[0] || null;
}

function openAttachmentPicker() {
  attachmentInput.value?.click();
}

let pollInterval = null;

onMounted(() => {
  loadMessages({ incremental: false });
  // Poll for new messages every 15s without overlapping request bursts.
  pollInterval = setInterval(() => {
    if (!document.hidden && !loading.value) {
      loadMessages({ incremental: true });
    }
  }, 15000);
});

onUnmounted(() => {
  if (pollInterval) {
    clearInterval(pollInterval);
  }
});

watch(() => [props.jobOrderId, props.submissionId, props.placementId, props.recipientId, props.groupChannel], () => {
  resetConversation();
  loadMessages({ incremental: false });
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
