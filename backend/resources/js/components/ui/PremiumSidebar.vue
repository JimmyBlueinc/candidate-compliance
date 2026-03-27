<template>
  <aside
    class="relative z-20 h-screen shrink-0 sticky top-0 overflow-hidden border-r flex flex-col"
    :class="[
      ui.sidebarCollapsed ? 'w-[80px]' : 'w-[300px]',
      'transition-[width] duration-200 ease-out',
      'bg-[color:var(--p-surface-card)] border-[color:var(--p-surface-border)]'
    ]"
  >
    <div class="px-3 py-3 shrink-0">
      <div class="flex items-center gap-2 min-w-0">
        <div
          class="w-8 h-8 rounded-xl overflow-hidden border bg-[color:var(--p-surface-0)] flex items-center justify-center shrink-0"
          :class="'border-[color:var(--p-surface-border)]'"
        >
          <img v-if="brand.logoUrl" :src="brand.logoUrl" alt="Logo" class="w-full h-full object-contain p-1.5" />
          <LayoutDashboard v-else class="w-4 h-4" :style="{ color: 'var(--p-text-muted-color)' }" />
        </div>

        <div v-if="!ui.sidebarCollapsed" class="min-w-0 flex-1">
          <div class="font-display text-sm tracking-tight text-[color:var(--p-text-color)] truncate" :title="brand.name || 'AgencyHQ'">
            {{ brand.name || 'AgencyHQ' }}
          </div>
        </div>

        <button
          type="button"
          class="w-7 h-7 inline-flex items-center justify-center rounded-lg border transition-colors shrink-0 border-[color:var(--p-surface-border)] hover:bg-[color:var(--p-surface-hover)] bg-[color:var(--p-surface-0)]"
          :class="ui.sidebarCollapsed ? 'mx-auto' : ''"
          :title="ui.sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
          @click="ui.toggleSidebar()"
        >
          <ChevronRight v-if="ui.sidebarCollapsed" class="w-4 h-4" :style="{ color: 'var(--p-text-muted-color)' }" />
          <ChevronLeft v-else class="w-4 h-4" :style="{ color: 'var(--p-text-muted-color)' }" />
        </button>
      </div>
    </div>

    <nav class="flex-1 min-h-0 px-4 overflow-y-auto custom-scrollbar pb-5">
      <div v-for="group in visibleGroups" :key="group.id" class="mb-6">
        <div
          v-if="!ui.sidebarCollapsed"
          class="px-2 pt-1 pb-2 text-[10px] font-black tracking-[0.22em] uppercase text-[color:var(--p-text-muted-color)]"
        >
          {{ group.label }}
        </div>

        <div class="space-y-1">
          <button
            v-for="item in group.items"
            :key="item.id"
            type="button"
            class="relative w-full flex items-center gap-3 px-3 py-2.5 rounded-2xl transition-all border"
            :class="navItemClass(item)"
            :style="navItemStyle(item)"
            @click="navigateTo(item)"
            :title="item.label"
          >
            <component :is="resolveIcon(item.icon)" class="w-[18px] h-[18px]" :style="{ color: iconColor(item) }" />
            <span v-if="!ui.sidebarCollapsed" class="text-sm font-semibold truncate">
              {{ item.label }}
            </span>

            <span
              v-if="isActiveRoute(item)"
              class="absolute right-2 top-1/2 -translate-y-1/2 w-1.5 h-1.5 rounded-full"
              :style="{ backgroundColor: primaryColor }"
            />
          </button>
        </div>
      </div>

      <div class="pt-5 mt-5 border-t border-[color:var(--p-surface-border)]">
        <button
          type="button"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-2xl transition-all"
          :class="ui.sidebarCollapsed ? 'justify-center' : ''"
          @click="handleLogout"
          title="Sign Out"
        >
          <LogOut class="w-[18px] h-[18px]" style="color: rgb(239 68 68);" />
          <span v-if="!ui.sidebarCollapsed" class="text-sm font-semibold text-red-500">Sign Out</span>
        </button>
      </div>
    </nav>

    <div class="p-3 border-t border-[color:var(--p-surface-border)] shrink-0 bg-[color:color-mix(in_srgb,var(--p-surface-card)_78%,transparent)] backdrop-blur-xl">
      <div class="flex items-center gap-2">
        <button
          type="button"
          class="w-8 h-8 flex items-center justify-center rounded-lg border transition-colors shrink-0"
          :class="'border-[color:var(--p-surface-border)] hover:bg-[color:var(--p-surface-hover)] bg-[color:var(--p-surface-0)]'"
          @click="ui.toggleTheme()"
          :title="ui.theme === 'light' ? 'Switch to dark mode' : 'Switch to light mode'"
        >
          <Moon v-if="ui.theme === 'light'" class="w-4 h-4" :style="{ color: 'var(--p-text-muted-color)' }" />
          <Sun v-else class="w-4 h-4" :style="{ color: 'var(--p-text-muted-color)' }" />
        </button>

        <button
          type="button"
          class="flex items-center gap-2 flex-1 p-1.5 rounded-xl transition-colors text-left"
          :class="'hover:bg-[color:var(--p-surface-hover)]'"
          @click="router.push({ name: 'dashboard.profile' })"
          title="Profile"
        >
          <div class="w-8 h-8 rounded-full overflow-hidden border border-[color:var(--p-surface-border)] shrink-0">
            <img
              alt="Profile"
              class="w-full h-full object-cover"
              :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(auth.user?.name || 'User')}&background=8B5CF6&color=fff`"
            />
          </div>
          <div v-if="!ui.sidebarCollapsed" class="min-w-0 flex-1">
            <div class="text-xs font-semibold truncate">{{ auth.user?.name || 'User' }}</div>
            <div class="text-[10px] text-[color:var(--p-text-muted-color)] truncate">{{ auth.user?.role || '' }}</div>
          </div>
        </button>
      </div>
    </div>

    <div
      class="pointer-events-none absolute inset-y-0 right-0 w-[1px]"
      :style="{ background: `linear-gradient(180deg, transparent, color-mix(in srgb, ${primaryColor} 35%, transparent), transparent)` }"
    />
  </aside>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useBrandStore } from '../../stores/brand';
import { useUiStore } from '../../stores/ui';
import {
  LayoutDashboard,
  LayoutGrid,
  Home,
  Badge,
  ShieldCheck,
  ClipboardList,
  FileText,
  HeartPulse,
  IdCard,
  Users,
  Building2,
  Settings,
  MessageSquare,
  Bell,
  Truck,
  Briefcase,
  Calendar,
  Timer,
  ArrowLeftRight,
  ChevronLeft,
  ChevronRight,
  LogOut,
  Moon,
  Sun,
  Gauge,
  Activity,
  Megaphone,
  CloudUpload,
  Rss,
  Search,
} from 'lucide-vue-next';

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
  ROLE_FACILITY,
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
    backgroundColor: `color-mix(in srgb, ${c} 10%, transparent)`,
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
        { id: 'dashboard', label: 'Dashboard', icon: 'dashboard', routeName: 'dashboard.finance' },
        { id: 'public_home', label: 'Public Home', icon: 'home', routeName: 'tenant.home' },
        { id: 'invoices', label: 'Invoices', icon: 'file', routeName: 'dashboard.invoices' },
        { id: 'accounts_receivable', label: 'Accounts Receivable', icon: 'gauge', routeName: 'dashboard.accounts_receivable' },
        { id: 'org_users', label: 'Organization Users', icon: 'users', routeName: 'dashboard.org_users' },
        { id: 'facilities', label: 'Facilities', icon: 'building', routeName: 'dashboard.facilities' },
        { id: 'settings', label: 'Settings', icon: 'settings', routeName: 'dashboard.agency_settings' },
      ],
    },
    {
      id: 'finance',
      label: 'Finance',
      show: isFinance.value,
      items: [
        { id: 'dashboard', label: 'Overview', icon: 'grid', routeName: 'dashboard.finance' },
        { id: 'invoices', label: 'Invoices', icon: 'file', routeName: 'dashboard.invoices' },
        { id: 'accounts_receivable', label: 'A/R', icon: 'gauge', routeName: 'dashboard.accounts_receivable' },
      ],
    },
    {
      id: 'talent',
      label: 'Talent',
      show: isOrgSuperAdmin.value || isRecruiter.value,
      items: [
        { id: 'candidates', label: 'Candidates', icon: 'search', routeName: 'dashboard.candidates' },
        { id: 'intake_feed', label: 'Intake', icon: 'rss', routeName: 'dashboard.intake_feed' },
        { id: 'intake_external', label: 'External', icon: 'upload', routeName: 'dashboard.intake_external' },
      ],
    },
    {
      id: 'compliance_group',
      label: 'Compliance',
      show: isCompliance.value,
      items: [
        { id: 'compliance_dashboard', label: 'Overview', icon: 'shield', routeName: 'dashboard.compliance' },
        { id: 'compliance_queue', label: 'Queue', icon: 'checklist', routeName: 'dashboard.compliance_queue' },
        { id: 'credentials', label: 'Creds', icon: 'badge', routeName: 'dashboard.credentials' },
        { id: 'background_checks', label: 'Background', icon: 'file', routeName: 'dashboard.background_checks' },
        { id: 'health_records', label: 'Health', icon: 'health', routeName: 'dashboard.health_records' },
        { id: 'work_authorizations', label: 'Work Auth', icon: 'id', routeName: 'dashboard.work_authorizations' },
      ],
    },
    {
      id: 'operations',
      label: 'Operations',
      show: isOrgSuperAdmin.value || isRecruiter.value || isScheduler.value || isLogistics.value,
      items: [
        { id: 'jobs', label: 'Jobs', icon: 'briefcase', routeName: 'dashboard.job_orders', show: isOrgSuperAdmin.value || isRecruiter.value },
        { id: 'job_sources', label: 'Sources', icon: 'activity', routeName: 'dashboard.job_sources', show: isOrgSuperAdmin.value || isRecruiter.value },
        { id: 'placements', label: 'Placements', icon: 'truck', routeName: 'dashboard.placements', show: isOrgSuperAdmin.value || isRecruiter.value || isLogistics.value },
        { id: 'shifts', label: 'Shifts', icon: 'calendar', routeName: 'dashboard.shifts', show: isOrgSuperAdmin.value || isRecruiter.value || isScheduler.value },
        { id: 'timesheets', label: 'Timesheets', icon: 'timer', routeName: 'dashboard.timesheets', show: isOrgSuperAdmin.value || isRecruiter.value },
        { id: 'compliance', label: 'Compliance', icon: 'shield', routeName: 'dashboard.compliance', show: isOrgSuperAdmin.value || isRecruiter.value },
        { id: 'logistics', label: 'Logistics', icon: 'truck', routeName: 'dashboard.logistics', show: isOrgSuperAdmin.value || isRecruiter.value || isLogistics.value },
        { id: 'messages', label: 'Messages', icon: 'messages', routeName: 'dashboard.messages' },
        { id: 'notifications', label: 'Notifications', icon: 'bell', routeName: 'dashboard.notifications' },
      ].filter((i) => i.show !== false),
    },
    {
      id: 'godmode',
      label: 'Platform',
      show: isPlatformAdmin.value,
      items: [
        { id: 'health', label: 'Health', icon: 'health', routeName: 'dashboard.platform_health' },
        { id: 'tenants', label: 'Orgs', icon: 'building', routeName: 'dashboard.platform_organizations' },
        { id: 'broadcast', label: 'Broadcast', icon: 'megaphone', routeName: 'dashboard.broadcast' },
      ],
    },
    {
      id: 'candidate',
      label: 'Candidate',
      show: isCandidate.value,
      items: [
        { id: 'my_career', label: 'Home', icon: 'grid', routeName: 'portal.dashboard' },
        { id: 'my_credentials', label: 'Creds', icon: 'badge', routeName: 'portal.credentials' },
        { id: 'job_board', label: 'Jobs', icon: 'briefcase', routeName: 'portal.jobs' },
        { id: 'my_travel', label: 'Travel', icon: 'truck', routeName: 'portal.travel' },
        { id: 'my_shifts', label: 'Shifts', icon: 'calendar', routeName: 'portal.shifts' },
        { id: 'my_timesheets', label: 'Time', icon: 'timer', routeName: 'portal.timesheets' },
        { id: 'messages', label: 'Messages', icon: 'messages', routeName: 'portal.messages' },
      ],
    },
    {
      id: 'facility',
      label: 'Facility',
      show: isFacility.value,
      items: [
        { id: 'facility_dashboard', label: 'Home', icon: 'grid', routeName: 'facility.dashboard' },
        { id: 'facility_workers', label: 'Workers', icon: 'users', routeName: 'facility.workers' },
        { id: 'facility_shifts', label: 'Shifts', icon: 'calendar', routeName: 'facility.shifts' },
        { id: 'facility_timesheets', label: 'Time', icon: 'timer', routeName: 'facility.timesheets' },
        { id: 'facility_invoices', label: 'Invoices', icon: 'file', routeName: 'facility.invoices' },
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

function navItemStyle(item) {
  return isActiveRoute(item) ? activeStyle.value : inactiveStyle;
}

function navItemClass(item) {
  const base = 'border-[color:var(--p-surface-border)]/0 hover:border-[color:var(--p-surface-border)] hover:bg-[color:var(--p-surface-hover)]';
  if (isActiveRoute(item)) return 'border-[color:var(--p-surface-border)]';
  return base;
}

function iconColor(item) {
  if (isActiveRoute(item)) return primaryColor.value;
  return 'var(--p-text-muted-color)';
}

function resolveIcon(key) {
  const map = {
    dashboard: LayoutDashboard,
    home: Home,
    grid: LayoutGrid,
    badge: Badge,
    shield: ShieldCheck,
    checklist: ClipboardList,
    file: FileText,
    health: HeartPulse,
    id: IdCard,
    users: Users,
    building: Building2,
    settings: Settings,
    messages: MessageSquare,
    bell: Bell,
    truck: Truck,
    briefcase: Briefcase,
    calendar: Calendar,
    timer: Timer,
    gauge: Gauge,
    activity: Activity,
    megaphone: Megaphone,
    upload: CloudUpload,
    rss: Rss,
    search: Search,
  };

  return map[String(key || '')] || LayoutGrid;
}

async function handleLogout() {
  await auth.logout();
  router.push({ name: 'login' });
}

function navigateTo(item) {
  if (item.params) {
    router.push({ name: item.routeName, params: item.params });
  } else {
    router.push({ name: item.routeName });
  }
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: color-mix(in srgb, var(--p-text-muted-color) 28%, transparent);
  border-radius: 999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: color-mix(in srgb, var(--p-text-muted-color) 42%, transparent);
}
</style>
