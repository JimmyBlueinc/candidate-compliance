<template>
  <div class="flex h-screen overflow-hidden bg-[var(--app-bg)] text-[var(--app-fg)]">
    <aside class="w-[280px] shrink-0 border-r border-[color:var(--p-surface-border)] glass-dark flex flex-col h-full overflow-hidden">
      <div class="p-6 shrink-0">
        <div class="flex items-center justify-between gap-3">
          <div class="flex items-center gap-3 min-w-0">
          <div class="w-10 h-10 rounded-2xl overflow-hidden bg-white/10 border border-white/10 flex items-center justify-center">
            <img v-if="brand.logoUrl" :src="brand.logoUrl" alt="Logo" class="w-full h-full object-contain p-2" />
            <span v-else class="material-symbols-outlined text-white text-[22px]">shield_person</span>
          </div>
          <div class="min-w-0">
            <div class="text-[10px] font-black tracking-[0.28em] uppercase text-[color:var(--p-text-muted-color)]">Candidate Portal</div>
            <div class="font-display text-lg leading-tight truncate text-white">{{ brand.name || 'Workspace' }}</div>
          </div>
        </div>
          <button
            type="button"
            class="flex items-center gap-2 p-1.5 rounded-full border border-white/10 bg-white/5 hover:bg-white/10 transition-colors shrink-0"
            @click="toggleUserMenu"
          >
            <div class="w-8 h-8 rounded-full overflow-hidden border border-white/15">
              <img alt="User" class="w-full h-full object-cover" :src="profileImage" />
            </div>
          </button>
          <Menu ref="userMenuRef" :model="userMenuItems" :popup="true" />
        </div>
      </div>

      <nav class="flex-1 min-h-0 overflow-y-auto custom-scrollbar px-4 pb-4 space-y-1">
        <div v-for="item in portalNav" :key="item.name" class="relative">
          <RouterLink
            v-if="!isLocked(item.name)"
            :to="{ name: item.name }"
            class="w-full flex items-center gap-3 px-4 py-2.5 rounded-2xl border transition-all text-sm"
            :style="navItemStyle(item.name)"
          >
            <span class="material-symbols-outlined text-[18px]">{{ item.icon }}</span>
            <span class="font-semibold">{{ item.label }}</span>
          </RouterLink>

          <button
            v-else
            type="button"
            class="w-full flex items-center gap-3 px-4 py-2.5 rounded-2xl border transition-all text-sm"
            :style="navItemStyle(item.name)"
            @click="openOnboardingGate(item)"
          >
            <span class="material-symbols-outlined text-[18px]">{{ item.icon }}</span>
            <span class="font-semibold">{{ item.label }}</span>
            <span class="ml-auto text-[10px] font-black uppercase tracking-widest opacity-80">Locked</span>
          </button>
        </div>

        <div class="pt-4">
          <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
            <div class="flex items-center justify-between gap-4 mb-3">
              <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Profile Strength</div>
              <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">{{ profileStrength }}%</div>
            </div>
            <div class="w-full h-2 rounded-full bg-white/5 overflow-hidden">
              <div class="h-2 rounded-full" :style="{ width: `${profileStrength}%`, backgroundColor: primaryColor }"></div>
            </div>
            <div class="mt-3 text-xs text-[color:var(--p-text-muted-color)]">Complete your profile and upload documents to reach 100%.</div>
          </div>
        </div>

        <div class="pt-2">
          <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
            <div class="flex items-center justify-between gap-2">
              <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Application Progress</div>
              <div
                class="text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded-md border"
                :style="applicationStatusStyle"
              >
                {{ applicationStatusLabel }}
              </div>
            </div>

            <div class="mt-3 space-y-2">
              <div class="flex items-center justify-between text-xs">
                <span class="text-[color:var(--p-text-muted-color)]">Phase 1 (Profile)</span>
                <span :class="phase1Complete ? 'text-emerald-300 font-bold' : 'text-amber-300 font-semibold'">{{ phase1Complete ? 'Complete' : 'Pending' }}</span>
              </div>
              <div class="flex items-center justify-between text-xs">
                <span class="text-[color:var(--p-text-muted-color)]">Phase 2 (Credentials)</span>
                <span :class="phase2Complete ? 'text-emerald-300 font-bold' : 'text-amber-300 font-semibold'">{{ phase2Complete ? 'Complete' : 'Pending' }}</span>
              </div>
            </div>

            <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-white/10">
              <div class="h-2 rounded-full transition-all duration-400" :style="{ width: `${applicationProgressPercent}%`, backgroundColor: primaryColor }" />
            </div>

            <div class="mt-3 text-xs text-[color:var(--p-text-muted-color)]">
              {{ applicationProgressHint }}
            </div>

            <div class="mt-3 flex gap-2">
              <button
                type="button"
                class="flex-1 px-3 py-2 rounded-xl border text-[11px] font-bold transition-colors"
                :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
                @click="router.push({ name: 'portal.profile' })"
              >
                Phase 1
              </button>
              <button
                type="button"
                class="flex-1 px-3 py-2 rounded-xl border text-[11px] font-bold transition-colors"
                :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
                @click="router.push({ name: 'portal.credentials' })"
              >
                Phase 2
              </button>
            </div>
          </div>
        </div>
      </nav>

      <div class="p-4 border-t border-white/10 shrink-0 bg-white/5 space-y-2">
        <div class="flex items-center justify-between gap-2">
          <button
            type="button"
            class="flex-1 px-4 py-2 rounded-2xl bg-white/5 border border-white/10 text-slate-300 text-xs font-bold hover:bg-white/10 hover:text-white transition-all"
            @click="ui.toggleTheme()"
          >
            <span class="material-symbols-outlined align-middle text-base mr-1">{{ ui.theme === 'light' ? 'dark_mode' : 'light_mode' }}</span>
            Theme
          </button>
          <button
            type="button"
            class="flex-1 px-4 py-2 rounded-2xl bg-white/5 border border-white/10 text-slate-300 text-xs font-bold hover:bg-white/10 hover:text-white transition-all"
            @click="logout"
          >
            Logout
          </button>
        </div>
      </div>
    </aside>

    <main class="flex-1 overflow-y-auto relative flex flex-col">
      <SystemBanner />

      <div class="aq-page">
        <div class="aq-container">
          <ForcePasswordChangeModal />

          <Dialog v-model:visible="onboardingGateOpen" modal header="Action required" :style="{ width: 'min(560px, 95vw)' }">
            <div class="space-y-4">
              <div class="text-sm text-[color:var(--p-text-color)]">
                To access <span class="font-semibold">{{ onboardingGateTargetLabel }}</span>, please complete your onboarding in <span class="font-semibold">My Profile</span>.
              </div>
              <div class="text-xs text-[color:var(--p-text-muted-color)]">
                This helps us verify your details and ensure you can apply, schedule, and communicate without delays.
              </div>

              <div class="flex gap-2 justify-end pt-2">
                <Button type="button" label="Close" severity="secondary" outlined size="small" @click="onboardingGateOpen = false" />
                <Button type="button" label="Go to My Profile" size="small" @click="goToProfileForOnboarding" />
              </div>
            </div>
          </Dialog>

          <Dialog v-model:visible="profilePromptOpen" modal header="Complete Your Profile" :style="{ width: 'min(480px, 95vw)' }">
            <div class="space-y-4">
              <div class="text-sm text-[color:var(--p-text-color)]">
                Please complete your <span class="font-semibold">Personal Information</span> to unlock all features and apply for jobs.
              </div>
              <div class="text-xs text-[color:var(--p-text-muted-color)]">
                Your profile is missing required fields. Complete it now to get started.
              </div>

              <div class="flex gap-2 justify-end pt-2">
                <Button type="button" label="Later" severity="secondary" outlined size="small" @click="profilePromptOpen = false" />
                <Button type="button" label="Go to My Profile" size="small" @click="goToProfileFromPrompt" />
              </div>
            </div>
          </Dialog>

          <div
            v-if="showOnboardingHint"
            class="mb-6 px-4 py-3 rounded-2xl border border-[color:var(--aq-border)] bg-[color:color-mix(in_srgb,var(--p-surface-card)_78%,transparent)] backdrop-blur-xl text-xs text-[color:var(--p-text-muted-color)]"
          >
            Complete your <span class="font-bold">Personal Information</span> in My Profile to unlock all features.
          </div>

          <DashboardIntelligencePanel class="mb-6" />

          <RouterView />
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiGet } from '../../lib/api';
import { useAuthStore } from '../../stores/auth';
import { useBrandStore } from '../../stores/brand';
import { useUiStore } from '../../stores/ui';
import SystemBanner from '../SystemBanner.vue';
import ForcePasswordChangeModal from '../auth/ForcePasswordChangeModal.vue';
import DashboardIntelligencePanel from '../dashboard/DashboardIntelligencePanel.vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Menu from 'primevue/menu';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const brand = useBrandStore();
const ui = useUiStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const showGlow = computed(() => ui.theme !== 'light');
const glowColor = computed(() => `color-mix(in srgb, ${primaryColor.value} 22%, transparent)`);

const portalNav = [
    { name: 'portal.dashboard', label: 'Dashboard', icon: 'space_dashboard' },
    { name: 'portal.profile', label: 'My Profile', icon: 'person' },
    { name: 'portal.credentials', label: 'Credentials', icon: 'verified' },
    { name: 'portal.jobs', label: 'Jobs', icon: 'work' },
    { name: 'portal.travel', label: 'Travel', icon: 'travel' },
    { name: 'portal.shifts', label: 'Shifts', icon: 'schedule' },
    { name: 'portal.timesheets', label: 'Timesheets', icon: 'receipt_long' },
    { name: 'portal.availability', label: 'Availability', icon: 'event_available' },
    { name: 'portal.messages', label: 'Messages', icon: 'chat' },
    { name: 'portal.drive', label: 'My Drive', icon: 'folder' },
];

const navItemStyle = computed(() => (targetName) => {
    const isActive = String(route.name || '') === String(targetName);
    if (isActive) {
        return {
            backgroundColor: primaryColor.value,
            borderColor: primaryColor.value,
            color: '#fff',
            boxShadow: `0 10px 30px ${String(primaryColor.value).includes('#') ? primaryColor.value + '33' : 'rgba(139,92,246,0.25)'}`,
        };
    }
    return {
        backgroundColor: primarySoftBg.value,
        borderColor: primarySoftBorder.value,
        color: primaryColor.value,
    };
});

const me = ref(null);
const credentialsCount = ref(0);
const approvedCredentialsCount = ref(0);
const onboarding = ref(null);
const onboardingGateOpen = ref(false);
const onboardingGateTargetLabel = ref('this page');
const profilePromptOpen = ref(false);
const userMenuRef = ref(null);
let profilePromptInterval = null;

const showOnboardingHint = computed(() => {
    const phase1Complete = Boolean(onboarding.value?.phase1_complete);
    const current = String(route.name || '');
    // Show hint if Phase 1 not complete (except on profile page)
    if (!phase1Complete && current.startsWith('portal.') && current !== 'portal.profile') {
        return true;
    }
    return false;
});

const phase1Complete = computed(() => Boolean(onboarding.value?.phase1_complete));
const phase2Complete = computed(() => Boolean(onboarding.value?.phase2_complete));
const applicationProgressPercent = computed(() => {
    if (phase1Complete.value && phase2Complete.value) return 100;
    if (phase1Complete.value || phase2Complete.value) return 50;
    return 15;
});
const applicationStatusLabel = computed(() => {
    if (phase1Complete.value && phase2Complete.value) return 'Ready';
    if (phase1Complete.value || phase2Complete.value) return 'In progress';
    return 'Action needed';
});
const applicationStatusStyle = computed(() => {
    if (phase1Complete.value && phase2Complete.value) {
        return { borderColor: 'rgba(34,197,94,0.35)', backgroundColor: 'rgba(34,197,94,0.10)', color: 'rgb(74,222,128)' };
    }
    if (phase1Complete.value || phase2Complete.value) {
        return { borderColor: 'rgba(251,191,36,0.35)', backgroundColor: 'rgba(251,191,36,0.10)', color: 'rgb(253,224,71)' };
    }
    return { borderColor: 'rgba(248,113,113,0.35)', backgroundColor: 'rgba(248,113,113,0.10)', color: 'rgb(252,165,165)' };
});
const applicationProgressHint = computed(() => {
    if (phase1Complete.value && phase2Complete.value) {
        return 'Great. Your account is ready for final job applications.';
    }
    if (!phase1Complete.value && !phase2Complete.value) {
        return 'Complete phase 1 and phase 2 to finish your application journey.';
    }
    if (!phase1Complete.value) {
        return 'Finish phase 1 profile details to continue.';
    }
    return 'Finish phase 2 credentials/documents to continue.';
});
const profileImage = computed(() => {
    const avatar = String(auth.user?.avatar_url || auth.user?.avatar_path || '').trim();
    if (avatar) return avatar;
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(auth.user?.name || 'User')}&background=8B5CF6&color=fff`;
});
const userMenuItems = computed(() => ([
    { label: 'My Profile', icon: 'pi pi-user', command: () => router.push({ name: 'portal.profile' }) },
    { label: 'Credentials', icon: 'pi pi-verified', command: () => router.push({ name: 'portal.credentials' }) },
    { label: 'Availability', icon: 'pi pi-calendar', command: () => router.push({ name: 'portal.availability' }) },
    { separator: true },
    { label: 'Logout', icon: 'pi pi-sign-out', command: async () => { await logout(); } },
]));

function isLocked(routeName) {
    const phase1Complete = Boolean(onboarding.value?.phase1_complete);
    
    // Phase 1 complete: all tabs unlocked
    if (phase1Complete) return false;
    
    // Phase 1 not complete: only core candidate actions unlocked
    const allowed = ['portal.dashboard', 'portal.profile', 'portal.jobs', 'portal.availability', 'portal.messages', 'portal.credentials', 'portal.drive'];
    return !allowed.includes(String(routeName || ''));
}

const profileStrength = computed(() => {
    const c = me.value?.candidate || null;
    const contactFields = [c?.first_name, c?.last_name, c?.email, c?.phone];
    const contactScore = contactFields.filter(Boolean).length / 4;

    const docs = Math.min(1, (Number(approvedCredentialsCount.value || 0) / 5));

    return Math.round((contactScore * 0.6 + docs * 0.4) * 100);
});

async function loadMe() {
    try {
        me.value = await apiGet('/v1/portal/me');
        credentialsCount.value = Number(me.value?.credentials_count || 0);
        approvedCredentialsCount.value = Number(me.value?.approved_credentials_count || 0);
    } catch (e) {
        const status = e?.response?.status;
        if (status === 401 || status === 403) {
            await auth.logout();
            await router.push({ name: 'login' });
            return;
        }

        console.error('Failed to load candidate profile', e);
        me.value = null;
        credentialsCount.value = 0;
        approvedCredentialsCount.value = 0;
    }

    try {
        const res = await apiGet('/v1/portal/profile');
        onboarding.value = res?.onboarding || null;
    } catch (e) {
        const status = e?.response?.status;
        if (status === 401 || status === 403) {
            await auth.logout();
            await router.push({ name: 'login' });
            return;
        }

        console.error('Failed to load portal onboarding status', e);
        onboarding.value = null;
    }
}

function enforceOnboardingGate() {
    const phase1Complete = Boolean(onboarding.value?.phase1_complete);
    const phase2Complete = Boolean(onboarding.value?.phase2_complete);
    const current = String(route.name || '');
    
    if (current.startsWith('portal.') && isLocked(current)) {
        onboardingGateTargetLabel.value = portalNav.find((i) => String(i.name) === current)?.label || 'this page';
        onboardingGateOpen.value = true;
        
        // Redirect based on phase
        if (!phase1Complete) {
            router.replace({ name: 'portal.profile' });
        } else {
            router.replace({ name: 'portal.profile' });
        }
    }
}

function openOnboardingGate(item) {
    onboardingGateTargetLabel.value = item?.label || 'this page';
    onboardingGateOpen.value = true;
}

function goToProfileForOnboarding() {
    onboardingGateOpen.value = false;
    router.push({ name: 'portal.profile' });
}

function goToProfileFromPrompt() {
    profilePromptOpen.value = false;
    router.push({ name: 'portal.profile' });
}

function toggleUserMenu(event) {
    userMenuRef.value?.toggle(event);
}

function startProfilePromptInterval() {
    // Clear any existing interval
    if (profilePromptInterval) {
        clearInterval(profilePromptInterval);
    }
    
    // Show prompt every 5 minutes if Phase 1 incomplete
    profilePromptInterval = setInterval(() => {
        const phase1Complete = Boolean(onboarding.value?.phase1_complete);
        const current = String(route.name || '');
        
        if (!phase1Complete && current.startsWith('portal.') && current !== 'portal.profile') {
            profilePromptOpen.value = true;
        }
    }, 5 * 60 * 1000); // 5 minutes
}

function stopProfilePromptInterval() {
    if (profilePromptInterval) {
        clearInterval(profilePromptInterval);
        profilePromptInterval = null;
    }
}

async function logout() {
    stopProfilePromptInterval();
    await auth.logout();
    await router.push({ name: 'login' });
}

onMounted(async () => {
    await brand.load();
    await loadMe();
    enforceOnboardingGate();
    
    // Start the 5-minute profile prompt interval
    startProfilePromptInterval();
});

watch(
    () => route.name,
    () => {
        enforceOnboardingGate();
    }
);
</script>
