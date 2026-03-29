<template>
  <header class="aq-page-header flex justify-between items-start rounded-2xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-card)]/95 px-4 py-3 shadow-sm">
    <div>
      <div class="flex items-center gap-2 mb-1">
        <div class="w-7 h-7 rounded-lg overflow-hidden border border-[color:var(--p-surface-border)] bg-[color:var(--p-surface-0)] flex items-center justify-center shrink-0">
          <img v-if="brandLogo" :src="brandLogo" alt="Logo" class="w-full h-full object-contain p-1" />
          <span v-else class="material-symbols-outlined text-[color:var(--p-text-muted-color)] text-[14px]">apartment</span>
        </div>
        <div class="min-w-0">
          <div class="text-xs font-semibold text-[color:var(--p-text-color)] leading-tight truncate" :title="brandLabel || ''">
            {{ brandLabel || '' }}
          </div>
        </div>
      </div>
      <h1 class="font-display text-xl text-[color:var(--p-text-color)]">{{ title }}</h1>
    </div>
    <div class="flex items-center gap-3">
      <button
        v-if="showCommand"
        type="button"
        class="hidden md:inline-flex items-center gap-2 px-2.5 py-1.5 rounded-lg border border-[color:var(--p-surface-border)] bg-[color:var(--p-surface-0)] text-[11px] font-semibold text-[color:var(--p-text-muted-color)] hover:text-[color:var(--p-text-color)] hover:bg-[color:var(--p-surface-hover)] transition-colors"
        @click="emit('open-command')"
      >
        <i class="pi pi-search text-[10px]" />
        Command
        <span class="px-1.5 py-0.5 rounded border border-[color:var(--p-surface-border)] text-[9px]">⌘K</span>
      </button>

      <!-- Online Users (non-candidates only) -->
      <OnlineUsers v-if="!isCandidate" />
      
      <!-- Quick Message Icon (non-candidates only) -->
      <QuickMessage v-if="!isCandidate" />
      
      <!-- Notification Bell -->
      <NotificationBell />
      
      <!-- User Profile Menu -->
      <button
        type="button"
        class="flex items-center gap-2 p-1.5 rounded-full border border-[color:var(--p-surface-border)] bg-[color:var(--p-surface-0)] hover:bg-[color:var(--p-surface-hover)] transition-colors"
        @click="toggleUserMenu"
      >
        <div class="w-7 h-7 rounded-full overflow-hidden border border-[color:var(--p-surface-border)] shrink-0">
          <img
            alt="User"
            class="w-full h-full object-cover"
            :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(auth.user?.name || 'User')}&background=8B5CF6&color=fff`"
          />
        </div>
      </button>
      <Menu ref="userMenuRef" :model="userMenuItems" :popup="true" />
    </div>
  </header>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useUiStore } from '../../stores/ui';
import { useAuthStore } from '../../stores/auth';
import { useBrandStore } from '../../stores/brand';
import { useRouter } from 'vue-router';
import Menu from 'primevue/menu';
import NotificationBell from '../NotificationBell.vue';
import OnlineUsers from './OnlineUsers.vue';
import QuickMessage from './QuickMessage.vue';

const ui = useUiStore();
const auth = useAuthStore();
const brand = useBrandStore();
const router = useRouter();
const userMenuRef = ref(null);
const emit = defineEmits(['open-command']);

const isCandidate = computed(() => auth.user?.role === 'candidate');
const isApexHost = computed(() => ['agenchq.com', 'www.agenchq.com'].includes(String(window.location.hostname || '').toLowerCase()));
const brandLabel = computed(() => (isApexHost.value ? 'AgencHQ' : (brand.name || 'Workspace')));
const brandLogo = computed(() => (isApexHost.value ? null : brand.logoUrl));

const userMenuItems = computed(() => {
    const role = String(auth.user?.role || '');
    const isCandidate = role === 'candidate';
    const isStaff = ['platform_admin', 'org_super_admin', 'admin', 'recruiter', 'compliance', 'scheduler', 'finance', 'logistics'].includes(role);
    const items = [
        { label: 'Profile', icon: 'pi pi-user', command: () => router.push({ name: isCandidate ? 'portal.profile' : 'dashboard.profile' }) },
        { label: 'Profile Settings', icon: 'pi pi-id-card', command: () => router.push({ name: isCandidate ? 'portal.profile' : 'dashboard.profile_settings' }) },
        { label: 'Account Settings', icon: 'pi pi-cog', command: () => router.push({ name: isCandidate ? 'portal.profile' : 'dashboard.account_settings' }) },
        { label: 'Notifications', icon: 'pi pi-bell', command: () => router.push({ name: isCandidate ? 'portal.messages' : 'dashboard.notifications_settings' }) },
        { label: 'Security', icon: 'pi pi-shield', command: () => router.push({ name: isCandidate ? 'portal.profile' : 'dashboard.security_settings' }) },
        { label: 'Preferences', icon: 'pi pi-sliders-h', command: () => router.push({ name: isCandidate ? 'portal.profile' : 'dashboard.preferences_settings' }) },
    ];

    if (isCandidate) {
        items.push(
            { separator: true },
            { label: 'Resume / CV', icon: 'pi pi-file', command: () => router.push({ name: 'portal.profile' }) },
            { label: 'Work Preferences', icon: 'pi pi-briefcase', command: () => router.push({ name: 'portal.availability' }) },
            { label: 'Availability', icon: 'pi pi-calendar', command: () => router.push({ name: 'portal.availability' }) },
            { label: 'Applications', icon: 'pi pi-send', command: () => router.push({ name: 'portal.jobs' }) },
            { label: 'Saved Jobs', icon: 'pi pi-bookmark', command: () => router.push({ name: 'portal.jobs' }) },
            { label: 'Documents', icon: 'pi pi-folder', command: () => router.push({ name: 'portal.credentials' }) },
            { label: 'Compliance / Verification', icon: 'pi pi-check-circle', command: () => router.push({ name: 'portal.credentials' }) },
        );
    }

    if (isStaff) {
        items.push(
            { separator: true },
            { label: 'Organization Settings', icon: 'pi pi-building', command: () => router.push({ name: 'dashboard.agency_settings' }) },
        );
    }

    items.push(
        { separator: true },
        { label: 'Logout', icon: 'pi pi-sign-out', command: async () => { await auth.logout(); router.push({ name: 'login' }); } },
    );

    return items;
});

defineProps({
    title: {
        type: String,
        required: true,
    },
    showCommand: {
        type: Boolean,
        default: true,
    },
});

function toggleUserMenu(event) {
    userMenuRef.value?.toggle(event);
}
</script>
