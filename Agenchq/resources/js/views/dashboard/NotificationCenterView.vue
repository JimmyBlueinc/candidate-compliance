<template>
  <div class="space-y-6">
    <Card>
      <template #content>
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
          <div>
            <h2 class="font-display text-2xl">Notification Center</h2>
            <p class="text-sm text-[color:var(--p-text-muted-color)]">All your recent notifications.</p>
          </div>
          <div class="flex items-center gap-2 justify-end">
            <Button label="Refresh" size="small" severity="secondary" outlined :loading="loading" @click="load" />
            <Button label="Mark all read" size="small" :disabled="unreadCount === 0" :loading="marking" @click="markAll" />
          </div>
        </div>
      </template>
    </Card>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <Card>
      <template #content>
        <div v-if="loading" class="text-sm text-[color:var(--p-text-muted-color)]">Loading…</div>

        <div v-else-if="items.length === 0" class="text-sm text-[color:var(--p-text-muted-color)]">No notifications yet.</div>

        <div v-else class="space-y-2">
          <button
            v-for="n in items"
            :key="n.id"
            type="button"
            class="w-full text-left p-4 rounded-2xl border transition-colors"
            :class="!n.read_at ? 'border-primary/40 bg-primary/5' : 'border-[color:var(--p-surface-border)] hover:bg-[color:var(--p-surface-hover)]'"
            @click="openNotification(n)"
          >
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <div class="text-sm font-semibold">
                  {{ n.data?.message || 'Notification' }}
                </div>
                <div class="text-xs text-[color:var(--p-text-muted-color)] mt-1">
                  {{ n.created_at ? formatTime(n.created_at) : '' }}
                </div>
              </div>
              <div v-if="!n.read_at" class="shrink-0 w-2 h-2 rounded-full bg-primary mt-2"></div>
            </div>
          </button>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { apiGet, apiPost } from '../../lib/api';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Message from 'primevue/message';

const items = ref([]);
const loading = ref(false);
const marking = ref(false);
const error = ref('');

const unreadCount = computed(() => items.value.filter((i) => !i.read_at).length);
const router = useRouter();

function normalize(res) {
  return Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : []);
}

async function load() {
  try {
    loading.value = true;
    error.value = '';
    const res = await apiGet('/notifications');
    items.value = normalize(res);
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to load notifications.';
    items.value = [];
  } finally {
    loading.value = false;
  }
}

async function markAll() {
  try {
    marking.value = true;
    await apiPost('/notifications/read-all');
    const now = new Date().toISOString();
    items.value.forEach((i) => {
      i.read_at = i.read_at || now;
    });
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to mark all as read.';
  } finally {
    marking.value = false;
  }
}

async function markOne(n) {
  if (n.read_at) return;
  try {
    await apiPost(`/notifications/${n.id}/read`);
    n.read_at = new Date().toISOString();
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to mark as read.';
  }
}

async function openNotification(n) {
  await markOne(n);

  const type = String(n?.type || '');
  if (type === 'message' || type === 'new_message') {
    const senderId = Number(n?.data?.sender_id || 0);
    if (senderId > 0) {
      router.push({ name: 'dashboard.messages', query: { recipient_id: senderId } });
      return;
    }
    router.push({ name: 'dashboard.messages' });
    return;
  }

  router.push({ name: 'dashboard.notifications' });
}

function formatTime(dateStr) {
  const d = new Date(dateStr);
  return d.toLocaleString();
}

onMounted(load);
</script>
