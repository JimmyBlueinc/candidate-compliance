<template>
  <div class="flex h-screen overflow-hidden bg-[var(--app-bg)] text-[var(--app-fg)]">
    <PremiumSidebar />

    <main class="flex-1 overflow-y-auto relative flex flex-col">
      <SystemBanner />
      <ForcePasswordChangeModal />

      <div class="aq-container">
        <DashboardHeader :title="pageTitle" />

        <div
        v-if="showProfileNudge"
        class="mt-3 px-3 py-2 rounded-lg border border-amber-200/50 bg-amber-50/50 dark:border-amber-500/20 dark:bg-amber-500/10 flex items-center gap-2"
      >
        <span class="material-symbols-outlined text-amber-600 text-sm">info</span>
        <span class="text-xs text-amber-700 dark:text-amber-300">Complete your profile to help your team contact you faster.</span>
        <button
          type="button"
          class="ml-auto text-[10px] font-semibold text-amber-700 dark:text-amber-300 hover:underline"
          @click="goToProfile"
        >
          Complete
        </button>
      </div>
      </div>

      <div class="flex-1 aq-page">
        <div class="aq-container">
          <RouterView />
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useBrandStore } from '../../stores/brand';
import DashboardHeader from '../dashboard/DashboardHeader.vue';
import SystemBanner from '../SystemBanner.vue';
import PremiumSidebar from '../ui/PremiumSidebar.vue';
import ForcePasswordChangeModal from '../auth/ForcePasswordChangeModal.vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useUiStore } from '../../stores/ui';

const route = useRoute();
const router = useRouter();
const brand = useBrandStore();
const auth = useAuthStore();
const ui = useUiStore();

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
        'dashboard.org_users': 'Organization Users',
        'dashboard.facilities': 'Facilities',
        'dashboard.messages': 'Messages',
        'dashboard.notifications': 'Notification Center',
        'dashboard.admin_users': 'Platform Users',
        'dashboard.finance': 'Financial Overview',
        'dashboard.platform_health': 'Platform Health',
        'dashboard.broadcast': 'Global Broadcast',
        'dashboard.candidates': 'Candidates',
        'dashboard.candidate_search': 'Candidate Search',
        'dashboard.intake_feed': 'Intake Feed',
        'dashboard.candidate_profile': 'Candidate Profile',
        'dashboard.job_orders': 'Job Orders',
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
        'facility.dashboard': 'Facility Dashboard',
        'facility.workers': 'Workers',
        'facility.shifts': 'Shifts',
        'facility.timesheets': 'Timesheet Approvals',
        'facility.invoices': 'Invoices',
        'facility.invoice_detail': 'Invoice Details',
    };

    return titles[name] || 'Dashboard';
});

onMounted(async () => {
    await brand.load();
});
</script>
