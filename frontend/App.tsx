
import React from 'react';
import { Navigate, Route, Routes, useNavigate } from 'react-router-dom';
import Sidebar from './components/Sidebar';
import Header from './components/Header';
import ComplianceHub from './pages/ComplianceHub';
import PersonnelDatabase from './pages/PersonnelDatabase';
import Pipeline from './pages/Pipeline';
import Analytics from './pages/Analytics';
import Configuration from './pages/Configuration';
import AccessControls from './pages/AccessControls';
import Navbar from './components/Navbar';
import Hero from './components/Hero';
import Features from './components/Features';
import Logos from './components/Logos';
import Testimonial from './components/Testimonial';
import CTA from './components/CTA';
import Footer from './components/Footer';
import Login from './pages/Login';
import { AuthProvider, useAuth } from './context/AuthContext';
import { PageId } from './types';
import RequireRole from './components/RequireRole';
import Credentials from './pages/Credentials';
import BackgroundChecks from './pages/BackgroundChecks';
import HealthRecords from './pages/HealthRecords';
import WorkAuthorizations from './pages/WorkAuthorizations';
import ActivityLogs from './pages/ActivityLogs';
import EmailSettings from './pages/EmailSettings';
import AdminUsers from './pages/AdminUsers';
import PlatformOrganizations from './pages/PlatformOrganizations';
import OrgUsers from './pages/OrgUsers';
import Register from './pages/Register';
import ForgotPassword from './pages/ForgotPassword';
import ResetPassword from './pages/ResetPassword';
import Profile from './pages/Profile';
import SavedFilters from './pages/SavedFilters';
import Templates from './pages/Templates';
import ChangePassword from './pages/ChangePassword';

const App: React.FC = () => {
  return (
    <AuthProvider>
      <Routes>
        <Route path="/" element={<LandingPage />} />
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route path="/forgot-password" element={<ForgotPassword />} />
        <Route path="/reset-password" element={<ResetPassword />} />
        <Route path="/dashboard/*" element={<DashboardShell />} />
        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </AuthProvider>
  );
};

const LandingPage: React.FC = () => {
  return (
    <div className="relative bg-[#050507] text-white selection:bg-purple-500/30 overflow-hidden">
      {/* Background Orbs */}
      <div className="orb absolute w-[500px] h-[500px] bg-purple-900/20 -top-[100px] -left-[100px] rounded-full blur-[80px] pointer-events-none" />
      <div className="orb absolute w-[600px] h-[600px] bg-blue-900/10 -bottom-[200px] -right-[100px] rounded-full blur-[80px] pointer-events-none" />

      <Navbar />

      <main className="relative max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 pt-32 space-y-32">
        <Hero />
        <Features />
        <Logos />
        <Testimonial />
        <CTA />
      </main>

      <Footer />
    </div>
  );
};

const DashboardShell: React.FC = () => {
  const navigate = useNavigate();
  const activePage = getDashboardActivePage();
  const { isAuthenticated, user } = useAuth();

  React.useEffect(() => {
    if (!isAuthenticated) {
      navigate('/login');
    }
  }, [isAuthenticated, navigate]);

  if (!isAuthenticated) return null;

  return (
    <div className="flex h-screen overflow-hidden bg-background-dark text-slate-100">
      <Sidebar activePage={activePage} onPageChange={(id) => navigate(toDashboardPath(id))} />

      <main className="flex-1 overflow-y-auto relative flex flex-col">
        <div className="absolute top-0 right-0 w-[500px] h-[500px] bg-primary/10 blur-[150px] -z-10 rounded-full pointer-events-none"></div>
        <div className="absolute bottom-0 left-0 w-[400px] h-[400px] bg-accent-blue/10 blur-[120px] -z-10 rounded-full pointer-events-none"></div>

        <Header title={getPageTitle(activePage)} />

        <div className="flex-1 px-8 pb-8">
          <Routes>
            <Route
              index
              element={
                user?.role === 'platform_admin'
                  ? <Navigate to="platform_organizations" replace />
                  : user?.role === 'org_super_admin'
                    ? <Navigate to="org_users" replace />
                  : <Navigate to="compliance" replace />
              }
            />
            <Route
              path="compliance"
              element={
                user?.role === 'platform_admin'
                  ? <Navigate to="/dashboard/platform_organizations" replace />
                  : (
                    <RequireRole allow={['admin', 'candidate']}>
                      <ComplianceHub />
                    </RequireRole>
                  )
              }
            />
            <Route
              path="platform_organizations"
              element={
                <RequireRole allow={['platform_admin']}>
                  <PlatformOrganizations />
                </RequireRole>
              }
            />
            <Route
              path="org_users"
              element={
                <RequireRole allow={['org_super_admin', 'admin']}>
                  <OrgUsers />
                </RequireRole>
              }
            />
            <Route
              path="personnel"
              element={
                <RequireRole allow={['admin']}>
                  <PersonnelDatabase />
                </RequireRole>
              }
            />
            <Route
              path="pipeline"
              element={
                <RequireRole allow={['admin']}>
                  <Pipeline />
                </RequireRole>
              }
            />
            <Route
              path="analytics"
              element={
                <RequireRole allow={['org_super_admin', 'admin']}>
                  <Analytics />
                </RequireRole>
              }
            />
            <Route
              path="config"
              element={
                <RequireRole allow={['org_super_admin', 'admin']}>
                  <Configuration />
                </RequireRole>
              }
            />
            <Route
              path="access"
              element={
                <RequireRole allow={['org_super_admin']}>
                  <AccessControls />
                </RequireRole>
              }
            />
            <Route path="change_password" element={<ChangePassword />} />
            <Route path="profile" element={<Profile />} />
            <Route
              path="templates"
              element={
                <RequireRole allow={['org_super_admin', 'admin']}>
                  <Templates />
                </RequireRole>
              }
            />
            <Route
              path="filters"
              element={
                <RequireRole allow={['org_super_admin', 'admin']}>
                  <SavedFilters />
                </RequireRole>
              }
            />
            <Route
              path="credentials"
              element={
                <RequireRole allow={['admin', 'candidate']}>
                  <Credentials />
                </RequireRole>
              }
            />
            <Route
              path="background_checks"
              element={
                <RequireRole allow={['org_super_admin', 'admin']}>
                  <BackgroundChecks />
                </RequireRole>
              }
            />
            <Route
              path="health_records"
              element={
                <RequireRole allow={['org_super_admin', 'admin']}>
                  <HealthRecords />
                </RequireRole>
              }
            />
            <Route
              path="work_authorizations"
              element={
                <RequireRole allow={['org_super_admin', 'admin']}>
                  <WorkAuthorizations />
                </RequireRole>
              }
            />
            <Route
              path="activity_logs"
              element={
                <RequireRole allow={['org_super_admin', 'admin']}>
                  <ActivityLogs />
                </RequireRole>
              }
            />
            <Route
              path="email_settings"
              element={
                <RequireRole allow={['org_super_admin', 'admin']}>
                  <EmailSettings />
                </RequireRole>
              }
            />
            <Route
              path="admin_users"
              element={
                <RequireRole allow={['platform_admin']}>
                  <AdminUsers />
                </RequireRole>
              }
            />
            <Route path="*" element={<Navigate to="compliance" replace />} />
          </Routes>
        </div>
      </main>

      <div className="fixed top-0 left-0 w-full h-full pointer-events-none -z-20">
        <div className="absolute top-[10%] left-[20%] w-64 h-64 bg-primary/5 rounded-full blur-3xl"></div>
        <div className="absolute bottom-[20%] right-[10%] w-96 h-96 bg-accent-blue/5 rounded-full blur-3xl"></div>
      </div>
    </div>
  );
};

const toDashboardPath = (id: PageId): string => {
  return `/dashboard/${id}`;
};

const getDashboardActivePage = (): PageId => {
  const path = window.location.pathname;
  const prefix = '/dashboard/';
  if (!path.startsWith(prefix)) return 'compliance';
  const rest = path.slice(prefix.length);
  const first = rest.split('/')[0];
  const allowed: PageId[] = [
    'compliance',
    'personnel',
    'pipeline',
    'analytics',
    'platform_organizations',
    'org_users',
    'config',
    'access',
    'change_password',
    'profile',
    'templates',
    'filters',
    'credentials',
    'background_checks',
    'health_records',
    'work_authorizations',
    'activity_logs',
    'email_settings',
    'admin_users',
  ];
  return (allowed as string[]).includes(first) ? (first as PageId) : 'compliance';
};

const getPageTitle = (id: PageId): string => {
  switch (id) {
    case 'compliance': return 'Staffing Intelligence';
    case 'personnel': return 'Personnel Database';
    case 'pipeline': return 'Credentialing Pipeline';
    case 'analytics': return 'Predictive Analytics';
    case 'platform_organizations': return 'Organizations';
    case 'org_users': return 'Organization Users';
    case 'config': return 'Global Configuration';
    case 'access': return 'Access Controls';
    case 'change_password': return 'Change Password';
    case 'profile': return 'User Profile';
    case 'templates': return 'Document Templates';
    case 'filters': return 'Saved Filters';
    case 'credentials': return 'Credentials';
    case 'background_checks': return 'Background Checks';
    case 'health_records': return 'Health Records';
    case 'work_authorizations': return 'Work Authorizations';
    case 'activity_logs': return 'Activity Logs';
    case 'email_settings': return 'Email Settings';
    case 'admin_users': return 'Platform Users';
    default: return 'Dashboard';
  }
};

export default App;
