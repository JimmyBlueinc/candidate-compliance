import { Navigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

const ProtectedRoute = ({ 
  children, 
  requireAdmin = false,
  requireSuperAdmin = false,
  allowedRoles = null // Array of allowed roles, e.g., ['admin', 'super_admin']
}) => {
  const { isAuthenticated, loading, isAdmin, isSuperAdmin, user } = useAuth();

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-goodwill-light">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-goodwill-primary mx-auto"></div>
          <p className="mt-4 text-goodwill-text">Loading...</p>
        </div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  // Check role-based access
  if (requireSuperAdmin && !isSuperAdmin) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-goodwill-light">
        <div className="text-center">
          <h1 className="text-2xl font-bold text-goodwill-dark mb-2">Access Denied</h1>
          <p className="text-goodwill-text">Super Admin access required.</p>
        </div>
      </div>
    );
  }

  if (requireAdmin && !isAdmin) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-goodwill-light">
        <div className="text-center">
          <h1 className="text-2xl font-bold text-goodwill-dark mb-2">Access Denied</h1>
          <p className="text-goodwill-text">Admin access required.</p>
        </div>
      </div>
    );
  }

  // Check if user's role is in allowed roles array
  if (allowedRoles && user?.role && !allowedRoles.includes(user.role)) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-goodwill-light">
        <div className="text-center">
          <h1 className="text-2xl font-bold text-goodwill-dark mb-2">Access Denied</h1>
          <p className="text-goodwill-text">You don't have permission to access this page.</p>
        </div>
      </div>
    );
  }

  return children;
};

export default ProtectedRoute;


