<template>
  <div class="app-shell flex h-screen overflow-hidden bg-[color:var(--aq-bg)] text-[color:var(--aq-fg)]">
    <PremiumSidebar />

    <main class="app-main flex-1 overflow-y-auto relative flex flex-col min-w-0">
      <!-- System Banner & Modals -->
      <SystemBanner />
      <ForcePasswordChangeModal />

      <!-- Page Header -->
      <header class="app-header shrink-0 px-6 pt-6 pb-4">
        <div class="max-w-[1400px] mx-auto">
          <DashboardHeader :title="pageTitle" @open-command="openCommandPalette" />

          <!-- Profile Completion Nudge -->
          <div
            v-if="showProfileNudge"
            class="mt-4 px-4 py-3 rounded-[var(--radius-lg)] border flex items-center gap-3"
            :class="[
              'bg-amber-500/10 border-amber-500/20'
            ]"
          >
            <AlertCircle class="w-4 h-4 text-amber-400 shrink-0" />
            <span class="text-sm text-amber-300">Complete your profile to help your team contact you faster.</span>
            <button
              type="button"
              class="ml-auto text-xs font-semibold text-amber-400 hover:text-amber-300 transition-colors"
              @click="goToProfile"
            >
              Complete Now
            </button>
          </div>
        </div>
      </header>

      <!-- Main Content -->
      <div class="app-content flex-1 min-h-0 px-6 pb-6">
        <div class="max-w-[1400px] mx-auto h-full">
          <RouterView />
        </div>
      </div>
    </main>

    <CommandPalette ref="commandPaletteRef" />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiPost } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';
import { useAuthStore } from '../../stores/auth';
import { useUiStore } from '../../stores/ui';
import { usePolling } from '../../composables/usePolling';
import { AlertCircle } from 'lucide-vue-next';
import DashboardHeader from '../dashboard/DashboardHeader.vue';
import CommandPalette from '../dashboard/CommandPalette.vue';
import SystemBanner from '../SystemBanner.vue';
import PremiumSidebar from '../ui/PremiumSidebar.vue';
import ForcePasswordChangeModal from '../auth/ForcePasswordChangeModal.vue';

const route = useRoute();
const router = useRouter();
const brand = useBrandStore();
const auth = useAuthStore();
const ui = useUiStore();
const commandPaletteRef = ref(null);

const missingProfileFields = computed(() => {
  const u = auth.user;
  if (!u) return [];

  const missing = [];
  if (!String(u.phone || '').trim()) missing.push('phone');
  if (!String(u.address || '').trim()) missing.push('address');
  if (!String(u.job_title || '').trim()) missing.push('job_title');
  if (!String(u.department || '').trim()) missing.push('department');
  return missing;
});

const showProfileNudge = computed(() => {
  const u = auth.user;
  if (!u) return false;
  if ((u.role || '') === 'candidate') return false;
  if (String(route.name || '') === 'dashboard.profile') return false;
  return missingProfileFields.value.length > 0;
});

function goToProfile() {
  router.push({ name: 'dashboard.profile' });
}

function openCommandPalette() {
  commandPaletteRef.value?.openPalette?.();
}

async function sendHeartbeat() {
  const role = String(auth.user?.role || '');
  const staffRoles = ['org_super_admin', 'admin', 'recruiter', 'compliance', 'scheduler', 'finance', 'logistics'];
  if (!auth.isAuthenticated || !staffRoles.includes(role)) return;
  await apiPost('/users/heartbeat');
}

const pageTitle = computed(() => {
  const name = String(route.name || '');

  const titles = {
    'dashboard.compliance': 'Compliance Hub',
    'dashboard.credentials': 'Credentials',
    'dashboard.personnel': 'Personnel Database',
    'dashboard.pipeline': 'Credentialing Pipeline',
    'dashboard.placements': 'Placements',
    'dashboard.shifts': 'Shifts',
    'dashboard.timesheets': 'Timesheets',
    'dashboard.platform_organizations': 'Organizations',
    'dashboard.org_users': 'Team Members',
    'dashboard.facilities': 'Facilities',
    'dashboard.messages': 'Messages',
    'dashboard.notifications': 'Notifications',
    'dashboard.admin_users': 'Platform Users',
    'dashboard.finance': 'Financial Overview',
    'dashboard.platform_health': 'System Health',
    'dashboard.broadcast': 'Broadcast',
    'dashboard.candidates': 'Candidates',
    'dashboard.candidate_search': 'Candidate Search',
    'dashboard.intake_feed': 'Intake Feed',
    'dashboard.intake_external': 'External Sources',
    'dashboard.candidate_profile': 'Candidate Profile',
    'dashboard.job_orders': 'Job Orders',
    'dashboard.job_sources': 'Job Sources',
    'dashboard.agency_settings': 'Agency Settings',
    'dashboard.background_checks': 'Background Checks',
    'dashboard.health_records': 'Health Records',
    'dashboard.work_authorizations': 'Work Authorizations',
    'dashboard.activity_logs': 'Activity Logs',
    'dashboard.email_settings': 'Email Settings',
    'dashboard.config': 'Configuration',
    'dashboard.access': 'Access Controls',
    'dashboard.templates': 'Document Templates',
    'dashboard.filters': 'Saved Filters',
    'dashboard.profile': 'Profile',
    'dashboard.change_password': 'Change Password',
    'dashboard.invoices': 'Invoices',
    'dashboard.invoice_detail': 'Invoice Details',
    'dashboard.accounts_receivable': 'Accounts Receivable',
    'facility.dashboard': 'Facility Dashboard',
    'facility.workers': 'Workers',
    'facility.shifts': 'Shifts',
    'facility.timesheets': 'Timesheets',
    'facility.invoices': 'Invoices',
    'facility.invoice_detail': 'Invoice Details',
  };

  return titles[name] || 'Dashboard';
});

onMounted(async () => {
  await brand.load();
});

usePolling(sendHeartbeat, 45000, { immediate: true });
</script>
