<template>
  <aside
    class="bg-[color:var(--p-surface-card)] border-r border-[color:var(--p-surface-border)] flex flex-col z-20 h-screen overflow-hidden shrink-0 sticky top-0 transition-[width] duration-200"
    :class="ui.sidebarCollapsed ? 'w-[76px]' : 'w-72'"
  >
    <div class="px-5 py-4 shrink-0">
      <div class="flex items-center gap-3 min-w-0">
        <div class="w-10 h-10 rounded-2xl overflow-hidden border border-[color:var(--p-surface-border)] bg-[color:var(--p-surface-0)] flex items-center justify-center shrink-0">
          <img v-if="brand.logoUrl" :src="brand.logoUrl" alt="Logo" class="w-full h-full object-contain p-2" />
          <span v-else class="material-symbols-outlined text-[color:var(--p-text-muted-color)] text-xl">health_metrics</span>
        </div>
        <div v-if="!ui.sidebarCollapsed" class="min-w-0">
          <div class="font-display text-lg tracking-tight text-[color:var(--p-text-color)] truncate" :title="brand.name || 'AgencyHQ'">
            {{ brand.name || 'AgencyHQ' }}
          </div>
          <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">
            Dashboard
          </div>
        </div>

        <button
          type="button"
          class="ml-auto w-9 h-9 inline-flex items-center justify-center rounded-xl border border-[color:var(--p-surface-border)] hover:bg-[color:var(--p-surface-hover)] transition-colors"
          :title="ui.sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
          @click="ui.toggleSidebar()"
        >
          <span class="material-symbols-outlined text-[18px] text-[color:var(--p-text-muted-color)]">
            {{ ui.sidebarCollapsed ? 'chevron_right' : 'chevron_left' }}
          </span>
        </button>
      </div>
    </div>

    <nav class="flex-1 px-4 overflow-y-auto custom-scrollbar pb-5">
      <div v-for="group in visibleGroups" :key="group.id" class="mb-5">
        <div v-if="!ui.sidebarCollapsed" class="px-2 pt-1 pb-2 text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">
          {{ group.label }}
        </div>

        <div class="space-y-1">
          <button
            v-for="item in group.items"
            :key="item.id"
            type="button"
            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-2xl transition-all border hover:bg-[color:var(--p-surface-hover)]"
            :style="isActiveRoute(item) ? activeStyle : inactiveStyle"
            @click="navigateTo(item)"
            :title="item.label"
          >
            <span class="material-symbols-outlined text-[22px]">{{ item.icon }}</span>
            <span v-if="!ui.sidebarCollapsed" class="text-sm font-semibold truncate">
              {{ item.label }}
            </span>
          </button>
        </div>
      </div>

      <div class="pt-4 mt-4 border-t border-[color:var(--p-surface-border)]">
        <button
          type="button"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-2xl text-red-500 hover:text-red-600 hover:bg-red-500/10 transition-all"
          @click="handleLogout"
          title="Sign Out"
        >
          <span class="material-symbols-outlined text-[22px]">logout</span>
          <span v-if="!ui.sidebarCollapsed" class="text-sm font-semibold">Sign Out</span>
        </button>
      </div>
    </nav>

    <div class="p-4 border-t border-[color:var(--p-surface-border)] shrink-0 bg-[color:var(--p-surface-0)] space-y-2">
      <button
        type="button"
        class="w-full flex items-center justify-between gap-3 p-2 rounded-2xl border border-[color:var(--p-surface-border)] hover:bg-[color:var(--p-surface-hover)] transition-colors"
        @click="ui.toggleTheme()"
        title="Theme"
      >
        <div class="flex items-center gap-3 min-w-0">
          <span class="material-symbols-outlined text-[22px] text-[color:var(--p-text-muted-color)]">{{ ui.theme === 'light' ? 'dark_mode' : 'light_mode' }}</span>
          <div v-if="!ui.sidebarCollapsed" class="min-w-0 text-left">
            <div class="text-sm font-semibold truncate">Theme</div>
            <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)] truncate">{{ ui.theme === 'light' ? 'Light' : 'Dark' }}</div>
          </div>
        </div>
        <span v-if="!ui.sidebarCollapsed" class="material-symbols-outlined text-[color:var(--p-text-muted-color)]">swap_horiz</span>
      </button>

      <button
        type="button"
        class="flex items-center gap-3 p-2 rounded-2xl hover:bg-[color:var(--p-surface-hover)] transition-colors w-full text-left"
        @click="router.push({ name: 'dashboard.profile' })"
        title="Profile"
      >
        <div class="w-10 h-10 rounded-full overflow-hidden border border-[color:var(--p-surface-border)] shrink-0">
          <img
            alt="Admin Profile"
            class="w-full h-full object-cover"
            :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(auth.user?.name || 'User')}&background=8B5CF6&color=fff`"
          />
        </div>
        <div v-if="!ui.sidebarCollapsed" class="min-w-0">
          <div class="text-sm font-semibold truncate">{{ auth.user?.name || 'Administrator' }}</div>
          <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)] truncate">{{ auth.user?.role || '' }}</div>
        </div>
      </button>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useUiStore } from '../../stores/ui';
import { useBrandStore } from '../../stores/brand';
import {
    ROLE_PLATFORM_ADMIN,
    ROLE_ORG_SUPER_ADMIN,
    ROLE_ADMIN,
    ROLE_RECRUITER,
    ROLE_SCHEDULER,
    ROLE_COMPLIANCE,
    ROLE_FINANCE,
    ROLE_LOGISTICS,
    ROLE_CANDIDATE,
    ROLE_FACILITY
} from '../../lib/roles';

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();
const ui = useUiStore();
const brand = useBrandStore();

const role = computed(() => auth.user?.role || null);

const isPlatformAdmin = computed(() => role.value === ROLE_PLATFORM_ADMIN);
const isOrgSuperAdmin = computed(() => role.value === ROLE_ORG_SUPER_ADMIN);
const isRecruiter = computed(() => [ROLE_ADMIN, ROLE_RECRUITER].includes(String(role.value)));
const isScheduler = computed(() => role.value === ROLE_SCHEDULER);
const isCompliance = computed(() => role.value === ROLE_COMPLIANCE);
const isFinance = computed(() => role.value === ROLE_FINANCE);
const isLogistics = computed(() => role.value === ROLE_LOGISTICS);
const isCandidate = computed(() => role.value === ROLE_CANDIDATE);
const isFacility = computed(() => role.value === ROLE_FACILITY);

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const activeStyle = computed(() => {
    const c = primaryColor.value;
    return {
        backgroundColor: `color-mix(in srgb, ${c} 12%, transparent)`,
        color: c,
        borderColor: `color-mix(in srgb, ${c} 35%, transparent)`,
    };
});

const inactiveStyle = {
    color: 'var(--p-text-color)',
    borderColor: 'transparent',
};

const groups = computed(() => {
    return [
        {
            id: 'admin',
            label: 'Admin',
            show: isOrgSuperAdmin.value,
            items: [
                { id: 'dashboard', label: 'Dashboard', icon: 'grid_view', routeName: 'dashboard.finance' },
                { id: 'org_home', label: 'Organization Home', icon: 'home', tenantHome: true },
                { id: 'msa_dashboard', label: 'MSA Dashboard', icon: 'description', routeName: 'dashboard.facilities' },
                { id: 'invoices', label: 'Invoices', icon: 'request_quote', routeName: 'dashboard.invoices' },
                { id: 'accounts_receivable', label: 'Accounts Receivable', icon: 'account_balance_wallet', routeName: 'dashboard.accounts_receivable' },
                { id: 'org_users', label: 'Organization Users', icon: 'group', routeName: 'dashboard.org_users' },
                { id: 'facilities', label: 'Facilities', icon: 'domain', routeName: 'dashboard.facilities' },
                { id: 'settings', label: 'Settings', icon: 'settings', routeName: 'dashboard.agency_settings' },
            ],
        },
        {
            id: 'finance',
            label: 'Finance',
            show: isFinance.value,
            items: [
                { id: 'dashboard', label: 'Overview', icon: 'grid_view', routeName: 'dashboard.finance' },
                { id: 'invoices', label: 'Invoices', icon: 'request_quote', routeName: 'dashboard.invoices' },
                { id: 'accounts_receivable', label: 'A/R', icon: 'account_balance_wallet', routeName: 'dashboard.accounts_receivable' },
            ],
        },
        {
            id: 'talent',
            label: 'Talent',
            show: isOrgSuperAdmin.value || isRecruiter.value,
            items: [
                { id: 'candidates', label: 'Candidates', icon: 'person_search', routeName: 'dashboard.candidates' },
            ],
        },
        {
            id: 'compliance_group',
            label: 'Compliance',
            show: isCompliance.value,
            items: [
                { id: 'compliance_dashboard', label: 'Overview', icon: 'verified_user', routeName: 'dashboard.compliance' },
                { id: 'compliance_queue', label: 'Queue', icon: 'fact_check', routeName: 'dashboard.compliance_queue' },
                { id: 'credentials', label: 'Creds', icon: 'badge', routeName: 'dashboard.credentials' },
                { id: 'background_checks', label: 'Background', icon: 'policy', routeName: 'dashboard.background_checks' },
                { id: 'health_records', label: 'Health', icon: 'ecg_heart', routeName: 'dashboard.health_records' },
                { id: 'work_authorizations', label: 'Work Auth', icon: 'id_card', routeName: 'dashboard.work_authorizations' },
            ],
        },
        {
            id: 'operations',
            label: 'Operations',
            show: isOrgSuperAdmin.value || isRecruiter.value || isScheduler.value || isLogistics.value,
            items: [
                { id: 'jobs', label: 'Jobs', icon: 'work_outline', routeName: 'dashboard.job_orders', show: isOrgSuperAdmin.value || isRecruiter.value },
                { id: 'job_sources', label: 'Sources', icon: 'hub', routeName: 'dashboard.job_sources', show: isOrgSuperAdmin.value || isRecruiter.value },
                { id: 'placements', label: 'Placements', icon: 'swap_horiz', routeName: 'dashboard.placements', show: isOrgSuperAdmin.value || isRecruiter.value || isLogistics.value },
                { id: 'recruiter_tasks', label: 'Recruiter Tasks', icon: 'task_alt', routeName: 'dashboard.recruiter_tasks', show: isOrgSuperAdmin.value || isRecruiter.value },
                { id: 'shifts', label: 'Shifts', icon: 'calendar_today', routeName: 'dashboard.shifts', show: isOrgSuperAdmin.value || isRecruiter.value || isScheduler.value },
                { id: 'timesheets', label: 'Timesheets', icon: 'timer', routeName: 'dashboard.timesheets', show: isOrgSuperAdmin.value || isRecruiter.value },
                { id: 'compliance', label: 'Compliance', icon: 'verified', routeName: 'dashboard.compliance', show: isOrgSuperAdmin.value || isRecruiter.value },
                { id: 'logistics', label: 'Logistics', icon: 'local_shipping', routeName: 'dashboard.logistics', show: isOrgSuperAdmin.value || isRecruiter.value || isLogistics.value },
                { id: 'messages', label: 'Messages', icon: 'chat_bubble', routeName: 'dashboard.messages' },
                { id: 'notifications', label: 'Notifications', icon: 'notifications', routeName: 'dashboard.notifications' },
            ].filter(i => i.show !== false),
        },
        {
            id: 'godmode',
            label: 'Platform',
            show: isPlatformAdmin.value,
            items: [
                { id: 'platform_candidates', label: 'Candidates', icon: 'person_search', routeName: 'dashboard.candidates' },
                { id: 'platform_facilities', label: 'Facilities', icon: 'domain', routeName: 'dashboard.facilities' },
                { id: 'health', label: 'Health', icon: 'monitor_heart', routeName: 'dashboard.platform_health' },
                { id: 'tenants', label: 'Orgs', icon: 'domain', routeName: 'dashboard.platform_organizations' },
                { id: 'broadcast', label: 'Broadcast', icon: 'campaign', routeName: 'dashboard.broadcast' },
            ],
        },
        {
            id: 'candidate',
            label: 'Candidate',
            show: isCandidate.value,
            items: [
                { id: 'my_career', label: 'Home', icon: 'grid_view', routeName: 'portal.dashboard' },
                { id: 'my_credentials', label: 'Creds', icon: 'badge', routeName: 'portal.credentials' },
                { id: 'job_board', label: 'Jobs', icon: 'work_outline', routeName: 'portal.jobs' },
                { id: 'my_travel', label: 'Travel', icon: 'flight_takeoff', routeName: 'portal.travel' },
                { id: 'my_shifts', label: 'Shifts', icon: 'calendar_today', routeName: 'portal.shifts' },
                { id: 'my_timesheets', label: 'Time', icon: 'timer', routeName: 'portal.timesheets' },
                { id: 'messages', label: 'Messages', icon: 'chat_bubble', routeName: 'portal.messages' },
            ],
        },
        {
            id: 'facility',
            label: 'Facility',
            show: isFacility.value,
            items: [
                { id: 'facility_dashboard', label: 'Home', icon: 'grid_view', routeName: 'facility.dashboard' },
                { id: 'facility_workers', label: 'Workers', icon: 'groups', routeName: 'facility.workers' },
                { id: 'facility_shifts', label: 'Shifts', icon: 'calendar_today', routeName: 'facility.shifts' },
                { id: 'facility_timesheets', label: 'Time', icon: 'timer', routeName: 'facility.timesheets' },
                { id: 'facility_invoices', label: 'Invoices', icon: 'request_quote', routeName: 'facility.invoices' },
            ],
        },
    ];
});

const visibleGroups = computed(() => groups.value.filter((g) => g.show && Array.isArray(g.items) && g.items.length > 0));

function isActiveRoute(item) {
    const current = String(route.name || '');
    if (item.routeName === current) return true;
    if (item.routeName === 'dashboard.candidates' && current === 'dashboard.candidate_profile') return true;
    return false;
}

function navigateTo(item) {
    if (item.tenantHome) {
        const subdomain = String(brand.subdomain || '').trim();
        if (subdomain) {
            window.location.href = `https://${subdomain}.agenchq.com/home`;
            return;
        }
        router.push({ name: 'tenant.home' });
        return;
    }

    if (item.params || item.query) {
        router.push({ name: item.routeName, params: item.params, query: item.query });
    } else {
        router.push({ name: item.routeName });
    }
}

async function handleLogout() {
    await auth.logout();
    router.push({ name: 'login' });
}
</script>
