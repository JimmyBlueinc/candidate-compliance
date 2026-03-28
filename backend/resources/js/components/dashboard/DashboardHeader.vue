<template>
  <header class="aq-page-header flex justify-between items-start">
    <div>
      <div class="flex items-center gap-2 mb-1">
        <div class="w-7 h-7 rounded-lg overflow-hidden border border-[color:var(--p-surface-border)] bg-[color:var(--p-surface-0)] flex items-center justify-center shrink-0">
          <img v-if="brand.logoUrl" :src="brand.logoUrl" alt="Logo" class="w-full h-full object-contain p-1" />
          <span v-else class="material-symbols-outlined text-[color:var(--p-text-muted-color)] text-[14px]">apartment</span>
        </div>
        <div class="min-w-0">
          <div class="text-xs font-semibold text-[color:var(--p-text-color)] leading-tight truncate" :title="brand.name || ''">
            {{ brand.name || '' }}
          </div>
        </div>
      </div>
      <h1 class="font-display text-xl text-[color:var(--p-text-color)]">{{ title }}</h1>
    </div>
    <div class="flex items-center gap-3">
      <!-- Online Users (non-candidates only) -->
      <OnlineUsers v-if="!isCandidate" />
      
      <!-- Quick Message Icon (non-candidates only) -->
      <QuickMessage v-if="!isCandidate" />
      
      <!-- Notification Bell -->
      <NotificationBell />
      
      <!-- User Profile -->
      <button
        type="button"
        class="flex items-center gap-2 p-1.5 rounded-full border border-[color:var(--p-surface-border)] bg-[color:var(--p-surface-0)] hover:bg-[color:var(--p-surface-hover)] transition-colors"
        @click="goProfile"
      >
        <div class="w-7 h-7 rounded-full overflow-hidden border border-[color:var(--p-surface-border)] shrink-0">
          <img
            alt="User"
            class="w-full h-full object-cover"
            :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(auth.user?.name || 'User')}&background=8B5CF6&color=fff`"
          />
        </div>
      </button>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue';
import { useUiStore } from '../../stores/ui';
import { useAuthStore } from '../../stores/auth';
import { useBrandStore } from '../../stores/brand';
import { useRouter } from 'vue-router';
import NotificationBell from '../NotificationBell.vue';
import OnlineUsers from './OnlineUsers.vue';
import QuickMessage from './QuickMessage.vue';

const ui = useUiStore();
const auth = useAuthStore();
const brand = useBrandStore();
const router = useRouter();

const isCandidate = computed(() => auth.user?.role === 'candidate');

const roleLabel = computed(() => {
    const role = String(auth.user?.role || '');
    if (role === 'org_super_admin') return '';
    return role;
});

defineProps({
    title: {
        type: String,
        required: true,
    },
});

function goProfile() {
    router.push({ name: 'dashboard.profile' });
}
</script>
