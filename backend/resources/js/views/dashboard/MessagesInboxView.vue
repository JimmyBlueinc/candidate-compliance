<template>
  <div class="h-[calc(100vh-12rem)] flex flex-col gap-6">
    <UiPageHeader 
      title="Messages" 
      subtitle="Search candidates and start a conversation"
    />

    <div class="flex-1 min-h-0">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 h-full">
        <!-- Sidebar: Candidate List -->
        <div class="lg:col-span-1 flex flex-col min-h-0">
          <UiCard class="flex flex-col h-full" title="Candidates">
            <template #header-right>
              <div v-if="loading" class="flex items-center gap-2">
                <RefreshCw class="w-3 h-3 text-slate-500 animate-spin" />
                <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Syncing</span>
              </div>
            </template>

            <div class="space-y-3 mb-4">
              <div class="relative">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" />
                <InputText 
                  v-model="query" 
                  class="w-full pl-10" 
                  placeholder="Name or email..." 
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
                v-for="c in candidates"
                :key="c.id"
                type="button"
                class="w-full text-left p-3 rounded-xl border transition-all relative group"
                :class="selectedRecipientId === c.id 
                  ? 'border-primary/30 bg-primary/5' 
                  : 'border-transparent hover:bg-white/[0.03]'"
                @click="selectCandidate(c)"
              >
                <div 
                  v-if="selectedRecipientId === c.id" 
                  class="absolute left-0 top-2 bottom-2 w-1 rounded-full"
                  :style="{ backgroundColor: primaryColor }"
                ></div>

                <div class="flex items-center gap-3">
                  <div class="relative">
                    <div class="w-10 h-10 rounded-full bg-slate-800 border border-white/5 flex items-center justify-center text-white font-bold text-sm overflow-hidden">
                      <span v-if="!c.avatar">{{ c.name?.charAt(0) || 'C' }}</span>
                      <img v-else :src="c.avatar" class="w-full h-full object-cover" />
                    </div>
                    <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-[#09090b] bg-emerald-500"></div>
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2">
                      <div class="text-sm font-bold text-white truncate group-hover:text-white transition-colors">
                        {{ c.name || 'Candidate' }}
                      </div>
                    </div>
                    <div class="text-[11px] text-slate-500 truncate">{{ c.email || '—' }}</div>
                  </div>
                </div>
              </button>

              <div v-if="!loading && candidates.length === 0" class="flex flex-col items-center justify-center py-12 text-center px-4">
                <Users class="w-8 h-8 text-slate-700 mb-2" />
                <div class="text-xs text-slate-500">No candidates found.</div>
              </div>
            </div>
          </UiCard>
        </div>

        <!-- Main Content: Chat Window -->
        <div class="lg:col-span-2 flex flex-col min-h-0">
          <ChatWindow
            v-if="selectedRecipientId"
            :contextTitle="selectedLabel"
            :recipientId="selectedRecipientId"
            class="flex-1 min-h-0"
          />

          <UiCard v-else class="flex-1 flex flex-col items-center justify-center text-center">
            <div class="w-20 h-20 rounded-full bg-white/[0.02] border border-white/5 flex items-center justify-center mb-6">
              <MessageSquare class="w-10 h-10 text-slate-700" />
            </div>
            <h3 class="text-lg font-display text-white mb-2">Select a conversation</h3>
            <p class="text-sm text-slate-500 max-w-xs mx-auto">
              Choose a candidate from the list to start messaging or view history.
            </p>
          </UiCard>
        </div>
      </div>
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
  RefreshCw,
  User
} from 'lucide-vue-next';

const brand = useBrandStore();
const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');

const query = ref('');
const candidates = ref([]);
const loading = ref(false);
const error = ref('');

const selectedRecipientId = ref(null);
const selectedLabel = computed(() => {
  const row = candidates.value.find((c) => Number(c.id) === Number(selectedRecipientId.value));
  return row?.name ? `Chat with ${row.name}` : 'Messages';
});

async function runSearch() {
  try {
    loading.value = true;
    error.value = '';
    const res = await apiGet('/org/candidate-users', { params: { q: query.value || '' } });
    candidates.value = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : []);
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to load candidates.';
    candidates.value = [];
  } finally {
    loading.value = false;
  }
}

let pollTimer = null;
onMounted(() => {
  runSearch();
  pollTimer = window.setInterval(() => {
    if (!loading.value) runSearch();
  }, 20000);
});

onBeforeUnmount(() => {
  if (pollTimer) {
    window.clearInterval(pollTimer);
    pollTimer = null;
  }
});

function clearSearch() {
  query.value = '';
  candidates.value = [];
  selectedRecipientId.value = null;
}

function selectCandidate(c) {
  selectedRecipientId.value = Number(c.id);
}
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
