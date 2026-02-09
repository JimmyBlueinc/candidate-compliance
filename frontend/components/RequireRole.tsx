import React from 'react';
import { Navigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

type RequireRoleProps = {
  allow: Array<'platform_admin' | 'org_super_admin' | 'admin' | 'candidate'>;
  children: React.ReactNode;
};

const RequireRole: React.FC<RequireRoleProps> = ({ allow, children }) => {
  const { user, isAuthenticated } = useAuth();

  if (!isAuthenticated) return <Navigate to="/login" replace />;

  const role = user?.role;
  if (!role || !allow.includes(role)) {
    return <Navigate to="/dashboard/compliance" replace />;
  }

  return <>{children}</>;
};

export default RequireRole;
