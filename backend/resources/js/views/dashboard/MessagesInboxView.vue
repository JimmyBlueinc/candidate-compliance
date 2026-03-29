<template>
  <div class="min-h-[calc(100vh-11rem)] pb-6 flex flex-col gap-6">
    <UiPageHeader
      title="Messages"
      subtitle="Recruiter-candidate communication center with live presence and fast search."
    />

    <section class="msg-hero" :style="heroBgStyle">
      <div class="msg-hero-overlay" />
      <div class="relative z-10 flex flex-wrap items-end justify-between gap-4">
        <div>
          <p class="msg-kicker">Communication Hub</p>
          <h2 class="msg-title">Coordinate staffing conversations in one place</h2>
          <p class="msg-subtitle">Prioritize candidate responses, maintain context, and accelerate fill times.</p>
        </div>
        <div class="flex items-center gap-2 text-xs">
          <span class="msg-chip">Secure threads</span>
          <span class="msg-chip">Live presence</span>
          <span class="msg-chip">Fast routing</span>
        </div>
      </div>
    </section>

    <div class="flex-1 min-h-0 grid grid-cols-1 lg:grid-cols-12 gap-5">
      <aside class="lg:col-span-4 xl:col-span-3 flex flex-col min-h-0">
        <UiCard class="flex flex-col h-full" title="Conversations">
          <template #header-right>
            <div v-if="loading" class="flex items-center gap-2">
              <RefreshCw class="w-3.5 h-3.5 text-[color:var(--aq-muted)] animate-spin" />
              <span class="text-[10px] text-[color:var(--aq-muted)] font-semibold uppercase tracking-wider">Syncing</span>
            </div>
          </template>

          <div class="space-y-3 mb-4">
            <div class="relative">
              <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[color:var(--aq-muted)]" />
              <InputText
                v-model="query"
                class="w-full pl-10"
                placeholder="Search by name or email"
                @keydown.enter.prevent="runSearch"
              />
            </div>
            <div class="flex gap-2">
              <Button label="Search" icon="pi pi-search" class="flex-1" size="small" :loading="loading" @click="runSearch" />
              <Button label="Clear" icon="pi pi-filter-slash" size="small" severity="secondary" outlined :disabled="loading" @click="clearSearch" />
            </div>
          </div>

          <div class="flex-1 overflow-y-auto custom-scrollbar -mx-2 px-2 space-y-1">
            <button
              v-for="c in recipients"
              :key="c.id"
              type="button"
              class="candidate-item"
              :class="selectedRecipientId === c.id ? 'candidate-item-active' : ''"
              @click="selectRecipient(c)"
            >
              <div class="relative">
                <div class="w-10 h-10 rounded-full bg-[color:var(--aq-surface-2)] border border-[color:var(--aq-border)] flex items-center justify-center text-[color:var(--aq-fg)] font-bold text-sm overflow-hidden">
                  <span v-if="!c.avatar">{{ c.name?.charAt(0) || 'C' }}</span>
                  <img v-else :src="c.avatar" class="w-full h-full object-cover" />
                </div>
                <div
                  class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-[color:var(--aq-surface-card)]"
                  :class="c.is_online ? 'bg-emerald-500 pulsing-dot' : 'bg-slate-500/70'"
                />
              </div>

              <div class="min-w-0 flex-1 text-left">
                <div class="text-sm font-semibold text-[color:var(--aq-fg)] truncate">{{ c.name || 'Candidate' }}</div>
                <div class="text-[11px] text-[color:var(--aq-muted)] truncate">{{ c.email || '—' }}</div>
              </div>
            </button>

            <div v-if="!loading && recipients.length === 0" class="flex flex-col items-center justify-center py-12 text-center px-4">
              <Users class="w-8 h-8 text-[color:var(--aq-muted)] mb-2" />
              <div class="text-xs text-[color:var(--aq-muted)]">No users found.</div>
            </div>
          </div>
        </UiCard>
      </aside>

      <main class="lg:col-span-8 xl:col-span-9 flex flex-col min-h-0">
        <ChatWindow
          v-if="selectedRecipientId"
          :contextTitle="selectedLabel"
          :recipientId="selectedRecipientId"
          class="flex-1 min-h-0"
        />

        <UiCard v-else class="flex-1 flex flex-col items-center justify-center text-center border border-dashed border-[color:var(--aq-border)]">
          <div class="w-20 h-20 rounded-full bg-[color:var(--aq-primary)]/10 border border-[color:var(--aq-primary)]/25 flex items-center justify-center mb-6">
            <MessageSquare class="w-10 h-10 text-[color:var(--aq-primary)]" />
          </div>
          <h3 class="text-lg font-display text-[color:var(--aq-fg)] mb-2">Select a conversation</h3>
          <p class="text-sm text-[color:var(--aq-muted)] max-w-xs mx-auto">
            Choose a candidate from the list to start secure communication.
          </p>
        </UiCard>
      </main>
    </div>

    <Message v-if="error" severity="error" :closable="false" class="mt-4">{{ error }}</Message>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { apiGet } from '../../lib/api';
import ChatWindow from '../../components/chat/ChatWindow.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import UiPageHeader from '../../components/ui/UiPageHeader.vue';
import UiCard from '../../components/ui/UiCard.vue';
import { useBrandStore } from '../../stores/brand';
import { 
  Search, 
  MessageSquare, 
  Users, 
  RefreshCw
} from 'lucide-vue-next';

const brand = useBrandStore();
const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');

const query = ref('');
const recipients = ref([]);
const loading = ref(false);
const error = ref('');

const selectedRecipientId = ref(null);
const selectedLabel = computed(() => {
  const row = recipients.value.find((c) => Number(c.id) === Number(selectedRecipientId.value));
  return row?.name ? `Chat with ${row.name}` : 'Messages';
});
const heroBgStyle = {
  backgroundImage: "url('https://images.unsplash.com/photo-1516549655169-df83a0774514?auto=format&fit=crop&w=1800&q=80')",
};

async function runSearch() {
  try {
    loading.value = true;
    error.value = '';
    const activeId = Number(selectedRecipientId.value || 0);
    const res = await apiGet('/org/chat-users', {
      params: { q: query.value || '' },
      timeout: 20000,
    });
    const next = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : []);
    recipients.value = next;
    if (activeId > 0 && !next.some((c) => Number(c.id) === activeId)) {
      selectedRecipientId.value = null;
    }
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to load chat users.';
    recipients.value = [];
  } finally {
    loading.value = false;
  }
}

let pollTimer = null;
onMounted(() => {
  runSearch();
  pollTimer = window.setInterval(() => {
    if (!document.hidden && !loading.value) runSearch();
  }, 30000);
});

onBeforeUnmount(() => {
  if (pollTimer) {
    window.clearInterval(pollTimer);
    pollTimer = null;
  }
});

function clearSearch() {
  query.value = '';
  recipients.value = [];
  selectedRecipientId.value = null;
}

function selectRecipient(c) {
  selectedRecipientId.value = Number(c.id);
}
</script>

<style scoped>
.msg-hero {
  position: relative;
  overflow: hidden;
  border-radius: 1rem;
  border: 1px solid color-mix(in srgb, var(--aq-border) 80%, transparent);
  min-height: 150px;
  padding: 1rem 1.1rem;
  background-size: cover;
  background-position: center;
}

.msg-hero-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(100deg, rgba(15, 23, 42, 0.72), rgba(15, 23, 42, 0.4), rgba(15, 23, 42, 0.28));
}

.msg-kicker {
  color: rgba(255, 255, 255, 0.88);
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  font-weight: 700;
}

.msg-title {
  margin-top: 0.22rem;
  color: #fff;
  font-size: clamp(1rem, 1.8vw, 1.4rem);
  font-weight: 700;
}

.msg-subtitle {
  margin-top: 0.2rem;
  color: rgba(255, 255, 255, 0.84);
  font-size: 0.82rem;
}

.msg-chip {
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.35);
  background: rgba(255, 255, 255, 0.1);
  border-radius: 999px;
  padding: 0.2rem 0.55rem;
  backdrop-filter: blur(4px);
}

.candidate-item {
  width: 100%;
  text-align: left;
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.62rem;
  border-radius: 0.82rem;
  border: 1px solid transparent;
  transition: background var(--transition-fast), border-color var(--transition-fast), transform var(--transition-fast);
}

.candidate-item:hover {
  background: color-mix(in srgb, var(--aq-surface-2) 88%, transparent);
  border-color: color-mix(in srgb, var(--aq-border) 80%, transparent);
}

.candidate-item-active {
  background: color-mix(in srgb, var(--aq-primary) 9%, transparent);
  border-color: color-mix(in srgb, var(--aq-primary) 28%, transparent);
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
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.2);
}
</style>
