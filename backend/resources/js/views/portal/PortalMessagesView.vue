<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import ChatWindow from '../../components/chat/ChatWindow.vue';
import { apiGet } from '../../lib/api';
import UiCard from '../../components/ui/UiCard.vue';
import UiIcon from '../../components/ui/UiIcon.vue';
import UiPageHeader from '../../components/ui/UiPageHeader.vue';
import { useBrandStore } from '../../stores/brand';
import { UserX } from 'lucide-vue-next';

const brand = useBrandStore();
const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const staff = ref([]);
const selectedId = ref(null);
const loading = ref(true);

const selectedUser = computed(() => {
  return staff.value.find((u) => Number(u.id) === Number(selectedId.value)) || null;
});

async function loadStaff() {
  try {
    const res = await apiGet('/org/staff-chat-users');
    const rows = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : []);
    staff.value = rows;
    if (rows.length > 0) {
      selectedId.value = rows[0].id;
    }
  } catch (e) {
    console.error('Failed to load staff', e);
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  loadStaff();
  pollTimer = window.setInterval(() => {
    loadStaff();
  }, 30000);
});

let pollTimer = null;
onBeforeUnmount(() => {
  if (pollTimer) {
    window.clearInterval(pollTimer);
    pollTimer = null;
  }
});
</script>

<template>
  <div class="space-y-8">
    <UiPageHeader
      title="Messages"
      subtitle="Chat with your recruiter or team members."
    />

    <div v-if="loading" class="flex justify-center p-12">
      <i class="pi pi-spin pi-spinner text-primary text-2xl"></i>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Conversation List -->
      <div class="lg:col-span-1 space-y-4">
        <UiCard
          v-motion
          :initial="{ opacity: 0, y: 10 }"
          :enter="{ opacity: 1, y: 0, transition: { duration: 0.35 } }"
          class="p-4 h-[600px] flex flex-col"
        >
          <div class="text-[10px] font-black uppercase tracking-widest text-[color:var(--p-text-muted-color)] mb-4 px-2">
            Active Chats
          </div>
          <div class="flex-1 overflow-y-auto space-y-2 custom-scrollbar">
            <button
              v-for="u in staff"
              :key="u.id"
              type="button"
              class="w-full text-left p-3 rounded-2xl transition-all border"
              :style="Number(u.id) === Number(selectedId) ? { borderColor: primarySoftBorder, backgroundColor: primarySoftBg } : { borderColor: 'rgba(255,255,255,0.08)' }"
              @click="selectedId = u.id"
            >
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold" :style="{ backgroundColor: primaryColor }">
                  {{ u.name?.charAt(0) || 'S' }}
                </div>
                <div class="flex-1 min-w-0">
                  <div class="text-sm font-bold text-white truncate">{{ u.name }}</div>
                  <div class="text-[10px] font-black uppercase tracking-widest" :style="{ color: primaryColor }">{{ u.role }}</div>
                </div>
              </div>
            </button>

            <div v-if="staff.length === 0" class="text-xs text-[color:var(--p-text-muted-color)] p-4 text-center">
              No staff found to chat with.
            </div>
          </div>
        </UiCard>
      </div>

      <!-- Chat Window -->
      <div class="lg:col-span-2">
        <ChatWindow
          v-if="selectedUser"
          :contextTitle="`Chat with ${selectedUser.name}`"
          :recipientId="selectedUser.id"
        />
        <UiCard
          v-else
          v-motion
          :initial="{ opacity: 0, y: 10 }"
          :enter="{ opacity: 1, y: 0, transition: { delay: 0.05, duration: 0.35 } }"
          class="p-12 h-[600px] flex flex-col items-center justify-center text-center"
        >
          <UiIcon :icon="UserX" fallback="person_off" class="w-10 h-10 mb-4" style="color: rgba(255,255,255,0.12);" />
          <p class="text-slate-400">Select a conversation to start messaging.</p>
        </UiCard>
      </div>
    </div>
  </div>
</template>
