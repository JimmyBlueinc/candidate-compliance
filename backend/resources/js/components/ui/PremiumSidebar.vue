<template>
  <aside
    class="app-sidebar relative z-20 h-screen shrink-0 sticky top-0 overflow-visible border-r flex flex-col"
    :class="[
      ui.sidebarCollapsed ? 'w-[72px]' : 'w-[280px]',
      'transition-[width] duration-[var(--transition-base)] ease-out',
      'bg-[color:var(--aq-surface-1)] border-[color:var(--aq-border)]'
    ]"
  >
    <!-- Header -->
    <div class="relative px-3 py-4 shrink-0 border-b border-[color:var(--aq-border)]">
      <div class="flex items-center gap-3 min-w-0">
        <!-- Logo -->
        <div
          class="w-9 h-9 rounded-[var(--radius-xl)] overflow-hidden border flex items-center justify-center shrink-0 transition-all duration-[var(--transition-base)]"
          :class="[
            'border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)]',
            !ui.sidebarCollapsed && 'shadow-sm'
          ]"
        >
          <img v-if="resolvedLogoUrl" :src="resolvedLogoUrl" alt="Logo" class="w-full h-full object-contain p-1.5" />
          <LayoutDashboard v-else class="w-4 h-4 text-[color:var(--aq-muted)]" />
        </div>

        <!-- Brand Name -->
        <div v-if="!ui.sidebarCollapsed" class="min-w-0 flex-1">
          <div class="font-display text-sm font-semibold tracking-tight text-[color:var(--aq-fg)] truncate">
            {{ resolvedBrandName }}
          </div>
          <div class="text-[10px] font-medium tracking-wider uppercase text-[color:var(--aq-muted)]">
            Operations Hub
          </div>
        </div>
      </div>
      <!-- Collapse Toggle -->
      <button
        type="button"
        class="absolute top-4 right-3 z-20 w-7 h-7 inline-flex items-center justify-center rounded-[var(--radius-md)] border transition-all duration-[var(--transition-fast)] shrink-0 border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] hover:bg-[color:var(--aq-primary)]/10 hover:border-[color:var(--aq-primary)]/30"
        :title="ui.sidebarCollapsed ? 'Expand' : 'Collapse'"
        @click="ui.toggleSidebar()"
      >
        <ChevronRight v-if="ui.sidebarCollapsed" class="w-3.5 h-3.5 text-[color:var(--aq-muted)]" />
        <ChevronLeft v-else class="w-3.5 h-3.5 text-[color:var(--aq-muted)]" />
      </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 min-h-0 px-3 overflow-y-auto py-4">
      <div v-for="group in visibleGroups" :key="group.id" class="mb-6">
        <!-- Group Label -->
        <div
          v-if="!ui.sidebarCollapsed && group.label"
          class="px-3 mb-2 text-[10px] font-semibold tracking-widest uppercase text-[color:var(--aq-muted)]"
        >
          {{ group.label }}
        </div>

        <!-- Nav Items -->
        <div class="space-y-1">
          <div v-for="item in group.items" :key="item.id" class="space-y-1">
            <button
              type="button"
              class="nav-item relative w-full flex items-center gap-3 px-3 py-2.5 rounded-[var(--radius-lg)] transition-all duration-[var(--transition-fast)]"
              :class="navItemClass(item)"
              :style="navItemStyle(item)"
              @click="navigateTo(item)"
              :title="item.label"
            >
              <component
                :is="resolveIcon(item.icon)"
                class="w-[18px] h-[18px] shrink-0 transition-colors duration-[var(--transition-fast)]"
                :style="{ color: iconColor(item) }"
              />

              <span v-if="!ui.sidebarCollapsed" class="text-[13px] font-medium truncate flex-1 text-left">
                {{ item.label }}
              </span>

              <ChevronDown
                v-if="!ui.sidebarCollapsed && itemHasChildren(item)"
                class="w-3.5 h-3.5 text-[color:var(--aq-muted)] transition-transform"
                :class="isGroupExpanded(item.id) ? 'rotate-180' : ''"
              />

              <span
                v-if="isActiveRoute(item) && !ui.sidebarCollapsed && !itemHasChildren(item)"
                class="absolute right-3 top-1/2 -translate-y-1/2 w-1.5 h-1.5 rounded-full"
                :style="{ backgroundColor: primaryColor }"
              />
            </button>

            <div
              v-if="!ui.sidebarCollapsed && itemHasChildren(item) && isGroupExpanded(item.id)"
              class="ml-6 pl-3 border-l border-[color:var(--aq-border)] space-y-1"
            >
              <button
                v-for="child in item.children"
                :key="child.id"
                type="button"
                class="w-full text-left px-2.5 py-2 rounded-[var(--radius-md)] text-[12px] font-medium transition-colors"
                :class="isActiveRoute(child) ? 'bg-[color:var(--aq-primary)]/12 text-[color:var(--aq-primary)]' : 'text-[color:var(--aq-muted)] hover:bg-[color:var(--aq-surface-2)] hover:text-[color:var(--aq-fg)]'"
                @click="navigateTo(child)"
              >
                {{ child.label }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Footer -->
    <div class="shrink-0 border-t border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)]/50 backdrop-blur-sm">
      <!-- User Profile -->
      <div class="px-3 pb-3">
        <button
          type="button"
          class="w-full flex items-center gap-3 p-2 rounded-[var(--radius-lg)] transition-colors text-left"
          :class="[
            'hover:bg-[color:var(--aq-surface-card)]',
            ui.sidebarCollapsed && 'justify-center'
          ]"
          @click="router.push({ name: 'dashboard.profile' })"
        >
          <div class="w-8 h-8 rounded-full overflow-hidden border border-[color:var(--aq-border)] shrink-0 bg-[color:var(--aq-primary)]/20">
            <img
              v-if="auth.user?.avatar"
              :src="auth.user.avatar"
              alt="Profile"
              class="w-full h-full object-cover"
            />
            <span v-else class="w-full h-full flex items-center justify-center text-xs font-semibold text-[color:var(--aq-primary)]">
              {{ (auth.user?.name || 'U').charAt(0).toUpperCase() }}
            </span>
          </div>
          <div v-if="!ui.sidebarCollapsed" class="min-w-0 flex-1">
            <div class="text-xs font-semibold text-[color:var(--aq-fg)] truncate">
              {{ auth.user?.name || 'User' }}
            </div>
            <div class="text-[10px] text-[color:var(--aq-muted)] truncate">
              {{ formatRole(auth.user?.role) }}
            </div>
          </div>
        </button>
      </div>

      <!-- Sign Out -->
      <div class="px-3 pb-3">
        <button
          type="button"
          class="w-full flex items-center gap-3 px-3 py-2 rounded-[var(--radius-lg)] transition-colors text-rose-400"
          :class="[
            'hover:bg-rose-500/10',
            ui.sidebarCollapsed && 'justify-center'
          ]"
          @click="handleLogout"
          title="Sign Out"
        >
          <LogOut class="w-4 h-4" />
          <span v-if="!ui.sidebarCollapsed" class="text-xs font-medium">Sign Out</span>
        </button>
      </div>
    </div>

    <!-- Accent Line -->
    <div
      class="pointer-events-none absolute inset-y-0 right-0 w-[1px]"
      :style="{ background: `linear-gradient(180deg, transparent, color-mix(in srgb, ${primaryColor} 30%, transparent), transparent)` }"
    />
  </aside>
</template>

<script setup>
import { computed, ref } from 'vue';
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
  ChevronLeft,
  ChevronRight,
  ChevronDown,
  LogOut,
  Gauge,
  Activity,
  Megaphone,
  Search,
  PlugZap,
  FolderOpen,
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
const isApexHost = computed(() => ['agenchq.com', 'www.agenchq.com'].includes(String(window.location.hostname || '').toLowerCase()));
const resolvedBrandName = computed(() => (isApexHost.value ? 'AgencHQ' : (brand.name || 'AgencHQ')));
const resolvedLogoUrl = computed(() => (isApexHost.value ? null : brand.logoUrl));

const primaryColor = computed(() => brand.primaryColor || 'var(--aq-primary)');
const expandedGroups = ref({
  facilities_menu: false,
  finance_menu: false,
  app_platform_menu: false,
});

// Format role for display
function formatRole(r) {
  if (!r) return '';
  const roleMap = {
    'platform_admin': 'Platform Admin',
    'org_super_admin': 'Administrator',
    'admin': 'Admin',
    'recruiter': 'Recruiter',
    'scheduler': 'Scheduler',
    'compliance': 'Compliance',
    'finance': 'Finance',
    'logistics': 'Logistics',
    'candidate': 'Candidate',
    'facility': 'Facility',
  };
  return roleMap[r] || r.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

const activeStyle = computed(() => {
  const c = primaryColor.value;
  return {
    backgroundColor: `color-mix(in srgb, ${c} 12%, transparent)`,
    color: c,
  };
});

const inactiveStyle = {
  color: 'var(--aq-fg)',
};

const groups = computed(() => {
  return [
    {
      id: 'admin',
      label: 'Administration',
      show: isOrgSuperAdmin.value,
      items: [
        { id: 'dashboard', label: 'Dashboard', icon: 'dashboard', routeName: 'dashboard.finance' },
        { id: 'org_home', label: 'Organization Home', icon: 'home', tenantHome: true },
        {
          id: 'facilities_menu',
          label: 'Facilities',
          icon: 'building',
          children: [
            { id: 'facilities_dash', label: 'Facilities Dashboard', routeName: 'dashboard.facilities.dashboard' },
            { id: 'facilities_list', label: 'List of Facilities', routeName: 'dashboard.facilities.list' },
            { id: 'facilities_create', label: 'Create New Facility', routeName: 'dashboard.facilities.create' },
            { id: 'activity_logs', label: 'Activity & Audit', routeName: 'dashboard.activity_logs' },
          ],
        },
        {
          id: 'finance_menu',
          label: 'Finance',
          icon: 'gauge',
          children: [
            { id: 'invoices', label: 'Invoices', routeName: 'dashboard.invoices' },
            { id: 'accounts_receivable', label: 'Accounts Receivable', routeName: 'dashboard.accounts_receivable' },
          ],
        },
        { id: 'org_users', label: 'Team Members', icon: 'users', routeName: 'dashboard.org_users' },
        { id: 'settings', label: 'Settings', icon: 'settings', routeName: 'dashboard.agency_settings' },
        { id: 'integrations', label: 'Integrations', icon: 'integrations', routeName: 'dashboard.integrations' },
      ],
    },
    {
      id: 'finance',
      label: 'Finance',
      show: isFinance.value,
      items: [
        { id: 'dashboard', label: 'Overview', icon: 'grid', routeName: 'dashboard.finance' },
        { id: 'invoices', label: 'Invoices', icon: 'file', routeName: 'dashboard.invoices' },
        { id: 'accounts_receivable', label: 'Accounts Receivable', icon: 'gauge', routeName: 'dashboard.accounts_receivable' },
      ],
    },
    {
      id: 'talent',
      label: 'Talent Network',
      show: isOrgSuperAdmin.value || isRecruiter.value || isScheduler.value || isCompliance.value || isFinance.value || isLogistics.value,
      items: [
        { id: 'candidates', label: 'Candidates', icon: 'search', routeName: 'dashboard.candidates' },
      ],
    },
    {
      id: 'compliance_group',
      label: 'Compliance',
      show: isCompliance.value,
      items: [
        { id: 'compliance_dashboard', label: 'Overview', icon: 'shield', routeName: 'dashboard.compliance' },
        { id: 'compliance_queue', label: 'Review Queue', icon: 'checklist', routeName: 'dashboard.compliance_queue' },
        { id: 'credentials', label: 'Credentials', icon: 'badge', routeName: 'dashboard.credentials' },
        { id: 'background_checks', label: 'Background Checks', icon: 'file', routeName: 'dashboard.background_checks' },
        { id: 'health_records', label: 'Health Records', icon: 'health', routeName: 'dashboard.health_records' },
        { id: 'work_authorizations', label: 'Work Authorizations', icon: 'id', routeName: 'dashboard.work_authorizations' },
      ],
    },
    {
      id: 'operations',
      label: 'Operations',
      show: isOrgSuperAdmin.value || isRecruiter.value || isScheduler.value || isCompliance.value || isFinance.value || isLogistics.value,
      items: [
        { id: 'jobs', label: 'Job Orders', icon: 'briefcase', routeName: 'dashboard.job_orders', show: isOrgSuperAdmin.value || isRecruiter.value },
        { id: 'job_sources', label: 'Job Sources', icon: 'activity', routeName: 'dashboard.job_sources', show: isOrgSuperAdmin.value || isRecruiter.value },
        { id: 'placements', label: 'Placements', icon: 'truck', routeName: 'dashboard.placements', show: isOrgSuperAdmin.value || isRecruiter.value || isLogistics.value },
        { id: 'recruiter_tasks', label: 'Recruiter Tasks', icon: 'checklist', routeName: 'dashboard.recruiter_tasks', show: isOrgSuperAdmin.value || isRecruiter.value },
        { id: 'shifts', label: 'Shifts', icon: 'calendar', routeName: 'dashboard.shifts', show: isOrgSuperAdmin.value || isRecruiter.value || isScheduler.value },
        { id: 'timesheets', label: 'Timesheets', icon: 'timer', routeName: 'dashboard.timesheets', show: isOrgSuperAdmin.value || isRecruiter.value },
        { id: 'compliance', label: 'Compliance', icon: 'shield', routeName: 'dashboard.compliance', show: isOrgSuperAdmin.value || isRecruiter.value || isCompliance.value },
        { id: 'logistics', label: 'Logistics', icon: 'truck', routeName: 'dashboard.logistics', show: isOrgSuperAdmin.value || isRecruiter.value || isLogistics.value },
        { id: 'messages', label: 'Messages', icon: 'messages', routeName: 'dashboard.messages' },
        { id: 'drive', label: 'My Drive', icon: 'folder', routeName: 'dashboard.drive' },
        { id: 'notifications', label: 'Notifications', icon: 'bell', routeName: 'dashboard.notifications' },
      ].filter((i) => i.show !== false),
    },
    {
      id: 'godmode',
      label: 'Platform Administration',
      show: isPlatformAdmin.value,
      items: [
        { id: 'platform_candidates', label: 'Candidates', icon: 'search', routeName: 'dashboard.candidates' },
        { id: 'platform_facilities', label: 'Facilities', icon: 'building', routeName: 'dashboard.facilities.list' },
        { id: 'health', label: 'System Health', icon: 'health', routeName: 'dashboard.platform_health' },
        { id: 'tenants', label: 'Organizations', icon: 'building', routeName: 'dashboard.platform_organizations' },
        { id: 'broadcast', label: 'Broadcast', icon: 'megaphone', routeName: 'dashboard.broadcast' },
      ],
    },
    {
      id: 'candidate',
      label: 'My Portal',
      show: isCandidate.value,
      items: [
        { id: 'my_career', label: 'Home', icon: 'grid', routeName: 'portal.dashboard' },
        { id: 'my_credentials', label: 'Credentials', icon: 'badge', routeName: 'portal.credentials' },
        { id: 'job_board', label: 'Jobs', icon: 'briefcase', routeName: 'portal.jobs' },
        { id: 'my_travel', label: 'Travel', icon: 'truck', routeName: 'portal.travel' },
        { id: 'my_shifts', label: 'Shifts', icon: 'calendar', routeName: 'portal.shifts' },
        { id: 'my_timesheets', label: 'Timesheets', icon: 'timer', routeName: 'portal.timesheets' },
        { id: 'messages', label: 'Messages', icon: 'messages', routeName: 'portal.messages' },
        { id: 'drive', label: 'My Drive', icon: 'folder', routeName: 'portal.drive' },
      ],
    },
    {
      id: 'facility',
      label: 'Facility Portal',
      show: isFacility.value,
      items: [
        { id: 'facility_dashboard', label: 'Home', icon: 'grid', routeName: 'facility.dashboard' },
        { id: 'facility_workers', label: 'Workers', icon: 'users', routeName: 'facility.workers' },
        { id: 'facility_shifts', label: 'Shifts', icon: 'calendar', routeName: 'facility.shifts' },
        { id: 'facility_timesheets', label: 'Timesheets', icon: 'timer', routeName: 'facility.timesheets' },
        { id: 'facility_invoices', label: 'Invoices', icon: 'file', routeName: 'facility.invoices' },
      ],
    },
  ];
});

const visibleGroups = computed(() => groups.value.filter((g) => g.show && Array.isArray(g.items) && g.items.length > 0));

function isActiveRoute(item) {
  const current = String(route.name || '');
  if (item.routeName === current) return true;
  if (itemHasChildren(item)) {
    return item.children.some((child) => isActiveRoute(child));
  }
  if (item.routeName === 'dashboard.candidates' && current === 'dashboard.candidate_profile') return true;
  return false;
}

function navItemStyle(item) {
  return isActiveRoute(item) ? activeStyle.value : inactiveStyle;
}

function navItemClass(item) {
  if (isActiveRoute(item)) {
    return 'border border-[color:var(--aq-primary)]/20';
  }
  return 'border border-transparent hover:bg-[color:var(--aq-surface-2)] hover:border-[color:var(--aq-border)]';
}

function iconColor(item) {
  if (isActiveRoute(item)) return primaryColor.value;
  return 'var(--aq-muted)';
}

function itemHasChildren(item) {
  return Array.isArray(item?.children) && item.children.length > 0;
}

function isGroupExpanded(itemId) {
  return !!expandedGroups.value[itemId];
}

function toggleItemGroup(itemId) {
  expandedGroups.value[itemId] = !expandedGroups.value[itemId];
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
    search: Search,
    integrations: PlugZap,
    folder: FolderOpen,
  };

  return map[String(key || '')] || LayoutGrid;
}

async function handleLogout() {
  await auth.logout();
  router.push({ name: 'login' });
}

function navigateTo(item) {
  if (itemHasChildren(item)) {
    toggleItemGroup(item.id);
    return;
  }

  if (item.tenantHome) {
    const subdomain = String(brand.subdomain || '').trim();
    if (subdomain) {
      window.location.href = `https://${subdomain}.agenchq.com/home`;
      return;
    }
    router.push({ name: 'tenant.home' });
    return;
  }

  router.push({ name: item.routeName, params: item.params, query: item.query });
}
</script>

<style scoped>
.app-sidebar::-webkit-scrollbar {
  width: 4px;
}
.app-sidebar::-webkit-scrollbar-track {
  background: transparent;
}
.app-sidebar::-webkit-scrollbar-thumb {
  background: color-mix(in srgb, var(--aq-muted) 20%, transparent);
  border-radius: 999px;
}
.app-sidebar::-webkit-scrollbar-thumb:hover {
  background: color-mix(in srgb, var(--aq-muted) 35%, transparent);
}
</style>
