import { createRouter, createWebHistory } from 'vue-router';

import LoginView from '../views/auth/LoginView.vue';
import RegisterView from '../views/auth/RegisterView.vue';
import ForgotPasswordView from '../views/auth/ForgotPasswordView.vue';
import ResetPasswordView from '../views/auth/ResetPasswordView.vue';
import DashboardLayout from '../components/layouts/DashboardLayout.vue';
import DashboardIndexRedirect from '../views/dashboard/DashboardIndexRedirect.vue';
import ComplianceHubView from '../views/dashboard/ComplianceHubView.vue';
import ComplianceDashboardView from '../views/dashboard/ComplianceDashboardView.vue';
import CredentialsView from '../views/dashboard/CredentialsView.vue';
import PlatformOrganizationsView from '../views/dashboard/PlatformOrganizationsView.vue';
import OrgUsersView from '../views/dashboard/OrgUsersView.vue';
import AdminUsersView from '../views/dashboard/AdminUsersView.vue';
import BackgroundChecksView from '../views/dashboard/BackgroundChecksView.vue';
import HealthRecordsView from '../views/dashboard/HealthRecordsView.vue';
import WorkAuthorizationsView from '../views/dashboard/WorkAuthorizationsView.vue';
import ActivityLogsView from '../views/dashboard/ActivityLogsView.vue';
import PersonnelDatabaseView from '../views/dashboard/PersonnelDatabaseView.vue';
import PipelineView from '../views/dashboard/PipelineView.vue';
import ConfigurationView from '../views/dashboard/ConfigurationView.vue';
import AccessControlsView from '../views/dashboard/AccessControlsView.vue';
import PublicSubmissionView from '../views/public/PublicSubmissionView.vue';
import EmailSettingsView from '../views/dashboard/EmailSettingsView.vue';
import ProfileView from '../views/dashboard/ProfileView.vue';
import TemplatesView from '../views/dashboard/TemplatesView.vue';
import SavedFiltersView from '../views/dashboard/SavedFiltersView.vue';
import ChangePasswordView from '../views/dashboard/ChangePasswordView.vue';
import ComplianceQueueView from '../views/dashboard/ComplianceQueueView.vue';
import PlacementPipelineView from '../views/dashboard/PlacementPipelineView.vue';
import PlacementDetailView from '../views/dashboard/PlacementDetailView.vue';
import LogisticsDashboard from '../views/dashboard/LogisticsDashboard.vue';
import LogisticsDetailView from '../views/dashboard/LogisticsDetailView.vue';
import FinancialOverview from '../views/dashboard/FinancialOverview.vue';
import InvoiceListView from '../views/dashboard/InvoiceListView.vue';
import InvoiceDetailView from '../views/dashboard/InvoiceDetailView.vue';
import AccountsReceivableView from '../views/dashboard/AccountsReceivableView.vue';
import PlatformHealthView from '../views/dashboard/PlatformHealthView.vue';
import CandidateListView from '../views/dashboard/CandidateListView.vue';
import CandidateSearch from '../views/dashboard/CandidateSearch.vue';
import CandidateProfileView from '../views/dashboard/CandidateProfileView.vue';
import CandidateCredentialsView from '../views/dashboard/CandidateCredentialsView.vue';
import AdminIntakeView from '../views/dashboard/AdminIntakeView.vue';
import ExternalIntakeView from '../views/dashboard/ExternalIntakeView.vue';
import JobManagementView from '../views/dashboard/JobManagementView.vue';
import JobSourcesView from '../views/dashboard/JobSourcesView.vue';
import AgencySettingsView from '../views/dashboard/AgencySettingsView.vue';
import BroadcastCenterView from '../views/dashboard/BroadcastCenterView.vue';
import FacilitiesView from '../views/dashboard/FacilitiesView.vue';
import FacilityDetailView from '../views/dashboard/FacilityDetailView.vue';
import IntegrationsView from '../views/dashboard/IntegrationsView.vue';
import IntegrationDetailView from '../views/dashboard/IntegrationDetailView.vue';
import MessagesInboxView from '../views/dashboard/MessagesInboxView.vue';
import NotificationCenterView from '../views/dashboard/NotificationCenterView.vue';
import LandingView from '../views/public/LandingView.vue';
import SolutionsView from '../views/public/SolutionsView.vue';
import CustomersView from '../views/public/CustomersView.vue';
import PricingView from '../views/public/PricingView.vue';
import IntakeFeedView from '../views/dashboard/IntakeFeedView.vue';
import OrganizationSignupView from '../views/public/OrganizationSignupView.vue';
import OnboardingView from '../views/dashboard/OnboardingView.vue';
import PublicJobBoardView from '../views/public/PublicJobBoardView.vue';
import PublicJobDetailView from '../views/public/PublicJobDetailView.vue';
import PublicJobApplyView from '../views/public/PublicJobApplyView.vue';
import PublicOrgJobBoardView from '../views/public/PublicOrgJobBoardView.vue';
import OrgJobBoardView from '../views/public/OrgJobBoardView.vue';
import TenantHomeView from '../views/public/TenantHomeView.vue';
import TenantJobsView from '../views/public/TenantJobsView.vue';

import PrivatePlatformAdminPortalView from '../views/private/PrivatePlatformAdminPortalView.vue';

import CandidatePortalLayout from '../components/layouts/CandidatePortalLayout.vue';
import PortalLoginView from '../views/portal/PortalLoginView.vue';
import PortalDashboardView from '../views/portal/PortalDashboardView.vue';
import PortalProfileView from '../views/portal/PortalProfileView.vue';
import MyCredentials from '../views/portal/MyCredentials.vue';
import AvailableJobs from '../views/portal/AvailableJobs.vue';
import JobDetailView from '../views/portal/JobDetailView.vue';
import MyTravel from '../views/portal/MyTravel.vue';
import MyShifts from '../views/portal/MyShifts.vue';
import MyTimesheets from '../views/portal/MyTimesheets.vue';
import PortalMessagesView from '../views/portal/PortalMessagesView.vue';
import MyAvailability from '../views/portal/MyAvailability.vue';
import CompleteOnboardingView from '../views/portal/CompleteOnboardingView.vue';
import ShiftsView from '../views/dashboard/ShiftsView.vue';
import TimesheetsView from '../views/dashboard/TimesheetsView.vue';
import FacilityDashboardView from '../views/facility/FacilityDashboardView.vue';
import FacilityTimesheetsView from '../views/facility/FacilityTimesheetsView.vue';
import FacilityWorkersView from '../views/facility/FacilityWorkersView.vue';
import FacilityShiftsView from '../views/facility/FacilityShiftsView.vue';
import FacilityInvoicesView from '../views/facility/FacilityInvoicesView.vue';
import FacilityInvoiceDetailView from '../views/facility/FacilityInvoiceDetailView.vue';

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
} from '../lib/roles';

const STAFF_CHAT_ROLES = [ROLE_PLATFORM_ADMIN, ROLE_ORG_SUPER_ADMIN, ROLE_ADMIN, ROLE_RECRUITER, ROLE_COMPLIANCE, ROLE_SCHEDULER, ROLE_FINANCE, ROLE_LOGISTICS];

const base = window.location.pathname.startsWith('/app') ? '/app' : undefined;

const router = createRouter({
    history: createWebHistory(base),
    routes: [
        // Tenant public homepage routes (restored)
        {
            path: '/home',
            name: 'tenant.home',
            component: TenantHomeView,
            meta: { tenantPublic: true },
        },
        {
            path: '/home/jobs',
            name: 'tenant.jobs',
            component: TenantJobsView,
            meta: { tenantPublic: true },
        },
        {
            path: '/home/jobs/:id',
            name: 'tenant.job-detail',
            component: PublicJobDetailView,
            meta: { tenantPublic: true },
        },
        {
            path: '/home/jobs/:id/apply',
            name: 'tenant.job-apply',
            component: PublicJobApplyView,
            meta: { tenantPublic: true },
        },
        // Apex public routes
        {
            path: '/',
            name: 'landing',
            component: LandingView,
            meta: { publicPage: true }, // Public page, accessible to all (including authenticated users)
        },
        {
            path: '/signup',
            name: 'public.signup',
            component: OrganizationSignupView,
            meta: { guestOnly: true },
        },
        {
            path: '/solutions',
            name: 'public.solutions',
            component: SolutionsView,
            meta: { publicPage: true },
        },
        {
            path: '/customers',
            name: 'public.customers',
            component: CustomersView,
            meta: { publicPage: true },
        },
        {
            path: '/pricing',
            name: 'public.pricing',
            component: PricingView,
            meta: { publicPage: true },
        },
        {
            path: '/jobs',
            name: 'public.jobs',
            component: PublicJobBoardView,
            meta: { publicPage: true },
        },
        {
            path: '/jobs/:id',
            name: 'public.jobs.detail',
            component: PublicJobDetailView,
            meta: { publicPage: true },
        },
        {
            path: '/jobs/:id/apply',
            name: 'public.jobs.apply',
            component: PublicJobApplyView,
            meta: { publicPage: true },
        },
        {
            path: '/:orgSlug',
            name: 'public.org-home',
            component: PublicOrgJobBoardView,
            meta: { guestOnly: true },
        },
        {
            path: '/view/submission/:token',
            name: 'public.submission',
            component: PublicSubmissionView,
            meta: {},
        },
        {
            path: '/portal/login',
            name: 'portal.login',
            redirect: { name: 'login' },
            meta: { guestOnly: true },
        },
        {
            path: '/portal',
            component: CandidatePortalLayout,
            meta: { requiresAuth: true, allowedRoles: [ROLE_CANDIDATE] },
            children: [
                {
                    path: '',
                    redirect: { name: 'portal.dashboard' },
                },
                {
                    path: 'dashboard',
                    name: 'portal.dashboard',
                    component: PortalDashboardView,
                },
                {
                    path: 'profile',
                    name: 'portal.profile',
                    component: PortalProfileView,
                },
                {
                    path: 'credentials',
                    name: 'portal.credentials',
                    component: MyCredentials,
                },
                {
                    path: 'jobs',
                    name: 'portal.jobs',
                    component: AvailableJobs,
                },
                {
                    path: 'jobs/:id',
                    name: 'portal.jobs.detail',
                    component: JobDetailView,
                },
                {
                    path: 'job-board',
                    name: 'my-org.jobs',
                    component: OrgJobBoardView,
                },
                {
                    path: 'job-board/:id',
                    name: 'my-org.job',
                    component: OrgJobBoardView,
                },
                {
                    path: 'complete-profile',
                    name: 'portal.complete-profile',
                    component: CompleteOnboardingView,
                },

                {
                    path: 'travel',
                    name: 'portal.travel',
                    component: MyTravel,
                },
                {
                    path: 'shifts',
                    name: 'portal.shifts',
                    component: MyShifts,
                },
                {
                    path: 'timesheets',
                    name: 'portal.timesheets',
                    component: MyTimesheets,
                },
                {
                    path: 'availability',
                    name: 'portal.availability',
                    component: MyAvailability,
                },
                {
                    path: 'messages',
                    name: 'portal.messages',
                    component: PortalMessagesView,
                },
            ],
        },
        {
            path: '/facility',
            component: DashboardLayout,
            meta: { requiresAuth: true, allowedRoles: [ROLE_FACILITY] },
            children: [
                {
                    path: '',
                    redirect: { name: 'facility.dashboard' },
                },
                {
                    path: 'dashboard',
                    name: 'facility.dashboard',
                    component: FacilityDashboardView,
                },
                {
                    path: 'timesheets',
                    name: 'facility.timesheets',
                    component: FacilityTimesheetsView,
                },
                {
                    path: 'workers',
                    name: 'facility.workers',
                    component: FacilityWorkersView,
                },
                {
                    path: 'shifts',
                    name: 'facility.shifts',
                    component: FacilityShiftsView,
                },
                {
                    path: 'invoices',
                    name: 'facility.invoices',
                    component: FacilityInvoicesView,
                },
                {
                    path: 'invoices/:id',
                    name: 'facility.invoice_detail',
                    component: FacilityInvoiceDetailView,
                },
            ],
        },
        {
            path: '/onboarding',
            name: 'onboarding',
            component: OnboardingView,
            meta: { requiresAuth: true, allowedRoles: [ROLE_ORG_SUPER_ADMIN] },
        },
        {
            path: '/login',
            name: 'login',
            component: LoginView,
            meta: { guestOnly: true },
        },
        {
            path: '/register',
            name: 'register',
            component: RegisterView,
            meta: { guestOnly: true },
        },
        {
            path: '/forgot-password',
            name: 'forgot-password',
            component: ForgotPasswordView,
            meta: { guestOnly: true },
        },
        {
            path: '/reset-password',
            name: 'reset-password',
            component: ResetPasswordView,
            meta: { guestOnly: true },
        },
        {
            path: '/private/platform-admin',
            name: 'private.platform_admin',
            component: PrivatePlatformAdminPortalView,
            meta: { guestOnly: true },
        },
        {
            path: '/dashboard',
            component: DashboardLayout,
            meta: { requiresAuth: true },
            children: [
                {
                    path: '',
                    name: 'dashboard.index',
                    component: DashboardIndexRedirect,
                },
                {
                    path: 'compliance',
                    name: 'dashboard.compliance',
                    component: ComplianceDashboardView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN, ROLE_ADMIN, ROLE_RECRUITER, ROLE_COMPLIANCE] },
                },
                {
                    path: 'credentials',
                    name: 'dashboard.credentials',
                    component: CredentialsView,
                },

                {
                    path: 'compliance/queue',
                    name: 'dashboard.compliance_queue',
                    component: ComplianceQueueView,
                    alias: 'compliance_queue',
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN, ROLE_ADMIN, ROLE_RECRUITER, ROLE_COMPLIANCE] },
                },

                {
                    path: 'placements',
                    name: 'dashboard.placements',
                    component: PlacementPipelineView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN, ROLE_ADMIN, ROLE_RECRUITER, ROLE_SCHEDULER, ROLE_LOGISTICS] },
                },
                {
                    path: 'placements/:id',
                    name: 'dashboard.placement_detail',
                    component: PlacementDetailView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN, ROLE_ADMIN, ROLE_RECRUITER, ROLE_SCHEDULER, ROLE_LOGISTICS] },
                },
                {
                    path: 'shifts',
                    name: 'dashboard.shifts',
                    component: ShiftsView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN, ROLE_ADMIN, ROLE_RECRUITER, ROLE_SCHEDULER] },
                },
                {
                    path: 'timesheets',
                    name: 'dashboard.timesheets',
                    component: TimesheetsView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN, ROLE_ADMIN, ROLE_RECRUITER] },
                },

                {
                    path: 'logistics',
                    name: 'dashboard.logistics',
                    component: LogisticsDashboard,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN, ROLE_ADMIN, ROLE_RECRUITER, ROLE_LOGISTICS] },
                },

                {
                    path: 'logistics/:id',
                    name: 'dashboard.logistics_detail',
                    component: LogisticsDetailView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN, ROLE_ADMIN, ROLE_RECRUITER, ROLE_LOGISTICS] },
                },

                {
                    path: 'finance',
                    name: 'dashboard.finance',
                    component: FinancialOverview,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN, ROLE_FINANCE] },
                },

                {
                    path: 'invoices',
                    name: 'dashboard.invoices',
                    component: InvoiceListView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN, ROLE_FINANCE] },
                },
                {
                    path: 'invoices/:id',
                    name: 'dashboard.invoice_detail',
                    component: InvoiceDetailView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN, ROLE_FINANCE] },
                },

                {
                    path: 'accounts-receivable',
                    name: 'dashboard.accounts_receivable',
                    component: AccountsReceivableView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN, ROLE_FINANCE] },
                },

                {
                    path: 'platform-health',
                    name: 'dashboard.platform_health',
                    component: PlatformHealthView,
                    meta: { allowedRoles: [ROLE_PLATFORM_ADMIN] },
                },

                {
                    path: 'broadcast',
                    name: 'dashboard.broadcast',
                    component: BroadcastCenterView,
                    meta: { allowedRoles: [ROLE_PLATFORM_ADMIN] },
                },

                {
                    path: 'candidates',
                    name: 'dashboard.candidates',
                    component: CandidateListView,
                    meta: { allowedRoles: [ROLE_PLATFORM_ADMIN, ROLE_ORG_SUPER_ADMIN, ROLE_ADMIN, ROLE_RECRUITER, ROLE_COMPLIANCE, ROLE_SCHEDULER, ROLE_FINANCE, ROLE_LOGISTICS] },
                },

                {
                    path: 'candidate-search',
                    name: 'dashboard.candidate_search',
                    component: CandidateSearch,
                    meta: { allowedRoles: [ROLE_PLATFORM_ADMIN, ROLE_ORG_SUPER_ADMIN, ROLE_ADMIN, ROLE_RECRUITER, ROLE_COMPLIANCE, ROLE_SCHEDULER, ROLE_FINANCE, ROLE_LOGISTICS] },
                },

                {
                    path: 'intake-feed',
                    name: 'dashboard.intake_feed',
                    component: IntakeFeedView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN, ROLE_ADMIN, ROLE_RECRUITER] },
                },

                {
                    path: 'intake-admin',
                    name: 'dashboard.intake_admin',
                    component: AdminIntakeView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN, ROLE_ADMIN, ROLE_RECRUITER] },
                },

                {
                    path: 'intake-external',
                    name: 'dashboard.intake_external',
                    component: ExternalIntakeView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN, ROLE_ADMIN, ROLE_RECRUITER] },
                },

                {
                    path: 'candidates/:id',
                    name: 'dashboard.candidate_profile',
                    component: CandidateProfileView,
                    meta: { allowedRoles: [ROLE_PLATFORM_ADMIN, ROLE_ORG_SUPER_ADMIN, ROLE_ADMIN, ROLE_RECRUITER, ROLE_COMPLIANCE, ROLE_SCHEDULER, ROLE_FINANCE, ROLE_LOGISTICS] },
                },

                {
                    path: 'candidates/:id/credentials',
                    name: 'dashboard.candidate_credentials',
                    component: CandidateCredentialsView,
                    meta: { allowedRoles: [ROLE_PLATFORM_ADMIN, ROLE_ORG_SUPER_ADMIN, ROLE_ADMIN, ROLE_RECRUITER, ROLE_COMPLIANCE, ROLE_SCHEDULER, ROLE_FINANCE, ROLE_LOGISTICS] },
                },

                {
                    path: 'job-orders',
                    name: 'dashboard.job_orders',
                    component: JobManagementView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN, ROLE_ADMIN, ROLE_RECRUITER] },
                },

                {
                    path: 'job-sources',
                    name: 'dashboard.job_sources',
                    component: JobSourcesView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN, ROLE_ADMIN, ROLE_RECRUITER] },
                },

                {
                    path: 'agency-settings',
                    name: 'dashboard.agency_settings',
                    component: AgencySettingsView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN] },
                },
                {
                    path: 'integrations',
                    name: 'dashboard.integrations',
                    component: IntegrationsView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN] },
                },
                {
                    path: 'integrations/:key',
                    name: 'dashboard.integrations.detail',
                    component: IntegrationDetailView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN] },
                },

                {
                    path: 'personnel',
                    name: 'dashboard.personnel',
                    component: PersonnelDatabaseView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN, ROLE_ADMIN, ROLE_RECRUITER] },
                },
                {
                    path: 'pipeline',
                    name: 'dashboard.pipeline',
                    component: PipelineView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN, ROLE_ADMIN, ROLE_RECRUITER] },
                },
                {
                    path: 'analytics',
                    name: 'dashboard.analytics',
                    redirect: { name: 'dashboard.finance' },
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN] },
                },
                {
                    path: 'config',
                    name: 'dashboard.config',
                    component: ConfigurationView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN] },
                },
                {
                    path: 'access',
                    name: 'dashboard.access',
                    component: AccessControlsView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN] },
                },
                {
                    path: 'background_checks',
                    name: 'dashboard.background_checks',
                    component: BackgroundChecksView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN, ROLE_ADMIN, ROLE_RECRUITER, ROLE_COMPLIANCE] },
                },
                {
                    path: 'health_records',
                    name: 'dashboard.health_records',
                    component: HealthRecordsView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN, ROLE_ADMIN, ROLE_RECRUITER, ROLE_COMPLIANCE] },
                },
                {
                    path: 'work_authorizations',
                    name: 'dashboard.work_authorizations',
                    component: WorkAuthorizationsView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN, ROLE_ADMIN, ROLE_RECRUITER, ROLE_COMPLIANCE] },
                },
                {
                    path: 'activity_logs',
                    name: 'dashboard.activity_logs',
                    component: ActivityLogsView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN] },
                },
                {
                    path: 'email_settings',
                    name: 'dashboard.email_settings',
                    component: EmailSettingsView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN] },
                },
                {
                    path: 'admin_users',
                    name: 'dashboard.admin_users',
                    component: AdminUsersView,
                    meta: { allowedRoles: [ROLE_PLATFORM_ADMIN] },
                },
                {
                    path: 'platform_organizations',
                    name: 'dashboard.platform_organizations',
                    component: PlatformOrganizationsView,
                    meta: { allowedRoles: [ROLE_PLATFORM_ADMIN] },
                },
                {
                    path: 'org_users',
                    name: 'dashboard.org_users',
                    component: OrgUsersView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN] },
                },

                {
                    path: 'facilities',
                    name: 'dashboard.facilities',
                    redirect: { name: 'dashboard.facilities.list' },
                    meta: { allowedRoles: [ROLE_PLATFORM_ADMIN, ROLE_ORG_SUPER_ADMIN] },
                },
                {
                    path: 'facilities/dashboard',
                    name: 'dashboard.facilities.dashboard',
                    component: FacilitiesView,
                    meta: { allowedRoles: [ROLE_PLATFORM_ADMIN, ROLE_ORG_SUPER_ADMIN] },
                },
                {
                    path: 'facilities/list',
                    name: 'dashboard.facilities.list',
                    component: FacilitiesView,
                    meta: { allowedRoles: [ROLE_PLATFORM_ADMIN, ROLE_ORG_SUPER_ADMIN] },
                },
                {
                    path: 'facilities/create',
                    name: 'dashboard.facilities.create',
                    meta: { allowedRoles: [ROLE_PLATFORM_ADMIN, ROLE_ORG_SUPER_ADMIN] },
                    component: FacilitiesView,
                },

                {
                    path: 'facilities/:id',
                    name: 'dashboard.facilities.detail',
                    component: FacilityDetailView,
                    meta: { allowedRoles: [ROLE_PLATFORM_ADMIN, ROLE_ORG_SUPER_ADMIN] },
                },

                {
                    path: 'messages',
                    name: 'dashboard.messages',
                    component: MessagesInboxView,
                    meta: { allowedRoles: STAFF_CHAT_ROLES },
                },

                {
                    path: 'notifications',
                    name: 'dashboard.notifications',
                    component: NotificationCenterView,
                    meta: { allowedRoles: STAFF_CHAT_ROLES },
                },
                {
                    path: 'profile',
                    name: 'dashboard.profile',
                    component: ProfileView,
                },
                {
                    path: 'templates',
                    name: 'dashboard.templates',
                    component: TemplatesView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN] },
                },
                {
                    path: 'filters',
                    name: 'dashboard.filters',
                    component: SavedFiltersView,
                    meta: { allowedRoles: [ROLE_ORG_SUPER_ADMIN] },
                },
                {
                    path: 'change_password',
                    name: 'dashboard.change_password',
                    component: ChangePasswordView,
                },
            ],
        },
        {
            path: '/:pathMatch(.*)*',
            redirect: { name: 'landing' },
        },
    ],
});

router.beforeEach(async (to, from) => {
    const { useAuthStore } = await import('../stores/auth');
    const auth = useAuthStore();

    if (!auth.hydrated) {
        auth.initFromStorage();
    }

    const toName = String(to.name || '');
    
    // PRIORITY ORDER FOR REDIRECTS:
    // 1. Not authenticated -> /login
    // 2. needs_onboarding -> /onboarding
    // 3. Role-based redirects
    // 4. Allow navigation
    // NOTE: must_change_password is handled by ForcePasswordChangeModal, not a route redirect

    // 1. NOT AUTHENTICATED - redirect to login
    if (to.meta?.requiresAuth && !auth.isAuthenticated) {
        console.log('[ROUTER] not authenticated -> login');
        return { name: 'login' };
    }

    // 1.5. AUTHENTICATED ON TENANT PUBLIC ROUTES - allow navigation
    // If user is on a tenant subdomain and visiting a tenantPublic route, let them stay
    const currentHost = window.location.hostname;
    const isOnTenantSubdomain = currentHost.endsWith('.agenchq.com') && 
                                 currentHost !== 'agenchq.com' && 
                                 currentHost !== 'www.agenchq.com';
    
    if (to.meta?.tenantPublic && isOnTenantSubdomain) {
        console.log('[ROUTER] tenant public route on tenant subdomain - allow navigation');
        return true;
    }

    // 2. NEEDS_ONBOARDING - highest priority for authenticated users
    if (auth.isAuthenticated && auth.user?.needs_onboarding && toName !== 'onboarding') {
        console.log('[ROUTER] needs_onboarding=true -> onboarding');
        return { name: 'onboarding' };
    }

    // 3. ROLE-BASED REDIRECTS
    if (auth.isAuthenticated && to.meta?.requiresAuth) {
        const role = auth.user?.role;
        
        // SUNDOMAIN ENFORCEMENT: Org users on apex must redirect to their tenant subdomain
        const currentHost = window.location.hostname;
        const isOnApex = currentHost === 'agenchq.com' || currentHost === 'www.agenchq.com';
        const isOrgUser = ![ROLE_CANDIDATE, ROLE_FACILITY, ROLE_PLATFORM_ADMIN].includes(role);
        
        if (isOnApex && isOrgUser) {
            const { useBrandStore } = await import('../stores/brand');
            const brand = useBrandStore();
            
            if (!brand.loaded) {
                brand.initFromStorage();
            }
            
            console.log('[ROUTER] apex dashboard check - brand.subdomain:', brand.subdomain);
            
            if (brand.subdomain) {
                const tenantUrl = `https://${brand.subdomain}.agenchq.com${to.fullPath}`;
                console.log('[ROUTER] redirecting apex user to tenant subdomain:', tenantUrl);
                window.location.href = tenantUrl;
                return false;
            }
        }

        // Candidate trying to access dashboard routes -> portal
        if (role === ROLE_CANDIDATE && toName.startsWith('dashboard.') && toName !== 'dashboard.change_password') {
            console.log('[ROUTER] candidate on dashboard -> portal.dashboard');
            return { name: 'portal.dashboard' };
        }

        // Facility user on dashboard or portal -> facility.dashboard
        if (role === ROLE_FACILITY) {
            if (toName.startsWith('dashboard.') && toName !== 'dashboard.change_password') {
                console.log('[ROUTER] facility on dashboard -> facility.dashboard');
                return { name: 'facility.dashboard' };
            }
            if (toName.startsWith('portal.')) {
                console.log('[ROUTER] facility on portal -> facility.dashboard');
                return { name: 'facility.dashboard' };
            }
        }

        // Role-based access control for allowedRoles
        if (Array.isArray(to.meta?.allowedRoles) && to.meta.allowedRoles.length > 0) {
            // Bypass role check for onboarding if needs_onboarding
            if (auth.user?.needs_onboarding && toName === 'onboarding') {
                // Allow
            } else if (!role || !to.meta.allowedRoles.includes(role)) {
                console.log('[ROUTER] role not allowed -> dashboard.index');
                return { name: 'dashboard.index' };
            }
        }
    }

    // 5. GUEST-ONLY ROUTES
    if (to.meta?.guestOnly && auth.isAuthenticated) {
        // must_change_password already handled above
        if (auth.user?.needs_onboarding) {
            console.log('[ROUTER] authenticated guest-only, needs_onboarding -> onboarding');
            return { name: 'onboarding' };
        }
        if (toName.startsWith('portal.') && auth.user?.role === ROLE_CANDIDATE) {
            console.log('[ROUTER] authenticated candidate on guest-only -> portal.credentials');
            return { name: 'portal.credentials' };
        }
        
        // For org users on apex login page, redirect to tenant dashboard
        const currentHost = window.location.hostname;
        const isOnApex = currentHost === 'agenchq.com' || currentHost === 'www.agenchq.com';
        const isOrgUser = ![ROLE_CANDIDATE, ROLE_FACILITY, ROLE_PLATFORM_ADMIN].includes(auth.user?.role);
        
        if (isOnApex && isOrgUser && toName === 'login') {
            // Authenticated org user on apex login page - redirect to tenant dashboard
            const { useBrandStore } = await import('../stores/brand');
            const brand = useBrandStore();
            
            // Initialize brand from storage if not already loaded
            if (!brand.loaded) {
                brand.initFromStorage();
            }
            
            console.log('[ROUTER] apex login check - brand.subdomain:', brand.subdomain, 'brand.loaded:', brand.loaded);
            
            if (brand.subdomain) {
                const tenantUrl = `https://${brand.subdomain}.agenchq.com/dashboard`;
                console.log('[ROUTER] authenticated org user on apex login -> tenant dashboard:', tenantUrl);
                window.location.href = tenantUrl;
                return false;
            }
        }
        
        console.log('[ROUTER] authenticated on guest-only -> dashboard.index');
        return { name: 'dashboard.index' };
    }

    // 6. PREVENT ONBOARDING ACCESS IF NOT NEEDED
    if (auth.isAuthenticated && !auth.user?.needs_onboarding && toName === 'onboarding') {
        console.log('[ROUTER] onboarding not needed -> dashboard.index');
        return { name: 'dashboard.index' };
    }

    if (to.meta?.title) {
        document.title = `${to.meta.title} | ${auth.user?.organization?.name || 'AgencyHQ'}`;
    } else {
        document.title = auth.user?.organization?.name || 'AgencyHQ';
    }

    console.log('[ROUTER] allow navigation to:', to.fullPath);
    return true;
});

router.beforeResolve((to, from) => {
    return true;
});

router.afterEach((to, from, failure) => {
    if (failure) {
        console.log('[ROUTER] navigation failed:', { to: to.fullPath, failure });
    }
});

export default router;
