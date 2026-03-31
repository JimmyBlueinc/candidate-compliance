import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import { ThemeProvider } from './contexts/ThemeContext';
import ProtectedRoute from './components/ProtectedRoute';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import Profile from './pages/Profile';
import ForgotPassword from './pages/ForgotPassword';
import ResetPassword from './pages/ResetPassword';
import AdminUsers from './pages/AdminUsers';
import CreateSuperAdmin from './pages/CreateSuperAdmin';
import CredentialTracker from './pages/CredentialTracker';
import MyCredentials from './pages/MyCredentials';
import CandidateRegistration from './pages/CandidateRegistration';
import Settings from './pages/Settings';
import Notifications from './pages/Notifications';
import CalendarView from './pages/CalendarView';
import Search from './pages/Search';
import Analytics from './pages/Analytics';
import Reports from './pages/Reports';
import ActivityLog from './pages/ActivityLog';
import Documents from './pages/Documents';
import Templates from './pages/Templates';
import EmailSettings from './pages/EmailSettings';
import Reminders from './pages/Reminders';
import ImportExport from './pages/ImportExport';
import BulkOperations from './pages/BulkOperations';
import AdvancedFilters from './pages/AdvancedFilters';
import SystemAlerts from './pages/SystemAlerts';
import HelpSupport from './pages/HelpSupport';
import StaffProfile from './pages/StaffProfile';
import ComplianceDashboard from './pages/ComplianceDashboard';

function App() {
  return (
    <ThemeProvider>
      <AuthProvider>
        <Router>
        <Routes>
          <Route path="/login" element={<Login />} />
          <Route path="/forgot-password" element={<ForgotPassword />} />
          <Route path="/reset-password" element={<ResetPassword />} />
          <Route path="/create-super-admin" element={<CreateSuperAdmin />} />
          <Route path="/candidate/register" element={<CandidateRegistration />} />
          <Route
            path="/dashboard"
            element={
              <ProtectedRoute>
                <Dashboard />
              </ProtectedRoute>
            }
          />
          <Route
            path="/profile"
            element={
              <ProtectedRoute>
                <Navigate to="/settings?tab=profile" replace />
              </ProtectedRoute>
            }
          />
          <Route
            path="/admin/users"
            element={
              <ProtectedRoute requireSuperAdmin={true}>
                <AdminUsers />
              </ProtectedRoute>
            }
          />
          <Route
            path="/credentials/tracker"
            element={
              <ProtectedRoute requireAdmin={true}>
                <CredentialTracker />
              </ProtectedRoute>
            }
          />
          <Route
            path="/credentials/my-credentials"
            element={
              <ProtectedRoute allowedRoles={['candidate']}>
                <MyCredentials />
              </ProtectedRoute>
            }
          />
          <Route
            path="/settings"
            element={
              <ProtectedRoute>
                <Settings />
              </ProtectedRoute>
            }
          />
          <Route
            path="/notifications"
            element={
              <ProtectedRoute>
                <Notifications />
              </ProtectedRoute>
            }
          />
          <Route
            path="/calendar"
            element={
              <ProtectedRoute>
                <CalendarView />
              </ProtectedRoute>
            }
          />
          <Route
            path="/search"
            element={
              <ProtectedRoute>
                <Search />
              </ProtectedRoute>
            }
          />
          <Route
            path="/staff/:candidateName"
            element={
              <ProtectedRoute requireAdmin={true}>
                <StaffProfile />
              </ProtectedRoute>
            }
          />
          <Route
            path="/compliance"
            element={
              <ProtectedRoute requireAdmin={true}>
                <ComplianceDashboard />
              </ProtectedRoute>
            }
          />
          <Route
            path="/analytics"
            element={
              <ProtectedRoute requireAdmin={true}>
                <Analytics />
              </ProtectedRoute>
            }
          />
          <Route
            path="/reports"
            element={
              <ProtectedRoute requireAdmin={true}>
                <Reports />
              </ProtectedRoute>
            }
          />
          <Route
            path="/activity"
            element={
              <ProtectedRoute requireAdmin={true}>
                <ActivityLog />
              </ProtectedRoute>
            }
          />
          <Route
            path="/documents"
            element={
              <ProtectedRoute>
                <Documents />
              </ProtectedRoute>
            }
          />
          <Route
            path="/templates"
            element={
              <ProtectedRoute requireAdmin={true}>
                <Templates />
              </ProtectedRoute>
            }
          />
          <Route
            path="/email-settings"
            element={
              <ProtectedRoute requireAdmin={true}>
                <EmailSettings />
              </ProtectedRoute>
            }
          />
          <Route
            path="/reminders"
            element={
              <ProtectedRoute>
                <Reminders />
              </ProtectedRoute>
            }
          />
          <Route
            path="/import-export"
            element={
              <ProtectedRoute requireAdmin={true}>
                <ImportExport />
              </ProtectedRoute>
            }
          />
          <Route
            path="/bulk-operations"
            element={
              <ProtectedRoute requireAdmin={true}>
                <BulkOperations />
              </ProtectedRoute>
            }
          />
          <Route
            path="/filters"
            element={
              <ProtectedRoute>
                <AdvancedFilters />
              </ProtectedRoute>
            }
          />
          <Route
            path="/alerts"
            element={
              <ProtectedRoute requireAdmin={true}>
                <SystemAlerts />
              </ProtectedRoute>
            }
          />
          <Route
            path="/help"
            element={
              <ProtectedRoute>
                <HelpSupport />
              </ProtectedRoute>
            }
          />
          <Route path="/" element={<Navigate to="/dashboard" replace />} />
        </Routes>
      </Router>
      </AuthProvider>
    </ThemeProvider>
  );
}

export default App;
