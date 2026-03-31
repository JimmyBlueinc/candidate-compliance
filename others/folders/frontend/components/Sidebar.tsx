
import React from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { PageId } from '../types';

interface SidebarProps {
  activePage: PageId;
  onPageChange: (id: PageId) => void;
}

const Sidebar: React.FC<SidebarProps> = ({ activePage, onPageChange }) => {
  const navigate = useNavigate();
  const { user, logout, isAdmin, isSuperAdmin, isCandidate } = useAuth();
  const isOrgSuperAdmin = user?.role === 'org_super_admin';

  const handleLogout = () => {
    logout();
    navigate('/login');
  };

  const navItems = isSuperAdmin
    ? [
        { id: 'platform_organizations', label: 'Organizations', icon: 'apartment' },
        { id: 'admin_users', label: 'Platform Users', icon: 'manage_accounts' },
      ]
    : isCandidate
      ? [
          { id: 'compliance', label: 'My Compliance', icon: 'verified_user' },
        ]
      : [
          ...(!isOrgSuperAdmin ? [{ id: 'compliance', label: 'Compliance Hub', icon: 'dashboard' }] : []),
          ...(!isOrgSuperAdmin ? [
            { id: 'personnel', label: 'Personnel Database', icon: 'groups' },
            { id: 'pipeline', label: 'Credentialing Pipeline', icon: 'account_tree' },
          ] : []),
          { id: 'analytics', label: 'Predictive Analytics', icon: 'analytics' },
          ...((isAdmin || isOrgSuperAdmin) ? [{ id: 'org_users', label: 'Organization Users', icon: 'group' }] : []),
        ];

  const operationsItems = isSuperAdmin || isCandidate
    ? []
    : [
        ...(!isOrgSuperAdmin ? [{ id: 'credentials', label: 'Credentials', icon: 'verified' }] : []),
        { id: 'background_checks', label: 'Background Checks', icon: 'fact_check' },
        { id: 'health_records', label: 'Health Records', icon: 'medical_information' },
        { id: 'work_authorizations', label: 'Work Authorizations', icon: 'badge' },
        { id: 'activity_logs', label: 'Activity Logs', icon: 'history' },
      ];

  const adminItems = isSuperAdmin || isCandidate
    ? []
    : [
        ...(isOrgSuperAdmin ? [
          { id: 'access', label: 'Access Controls', icon: 'security' },
        ] : []),
        ...(isAdmin ? [
          { id: 'config', label: 'Configuration', icon: 'settings' },
          { id: 'templates', label: 'Document Templates', icon: 'description' },
          { id: 'filters', label: 'Saved Filters', icon: 'filter_list' },
          { id: 'email_settings', label: 'Email Settings', icon: 'mail' },
        ] : []),
      ];

  const NavLink: React.FC<{ item: typeof navItems[0] }> = ({ item }) => {
    const isActive = activePage === item.id;
    return (
      <button
        onClick={() => onPageChange(item.id as PageId)}
        className={`w-full flex items-center gap-3 px-4 py-2 rounded-xl transition-all border ${
          isActive 
            ? 'bg-white/5 text-primary border-primary/20' 
            : 'text-slate-400 hover:text-white hover:bg-white/5 border-transparent'
        }`}
      >
        <span className="material-symbols-outlined text-xl">{item.icon}</span>
        <span className="font-medium text-sm">{item.label}</span>
      </button>
    );
  };

  return (
    <aside className="w-64 glass-dark border-r border-white/5 flex flex-col z-20 h-full overflow-hidden">
      <div className="p-6 shrink-0">
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 bg-gradient-to-br from-primary to-accent-blue rounded-lg flex items-center justify-center shadow-lg shadow-primary/20">
            <span className="material-symbols-outlined text-white text-xl">health_metrics</span>
          </div>
          <span className="font-display text-xl tracking-tight text-white">HealthFlow</span>
        </div>
      </div>

      <nav className="flex-1 px-4 space-y-1 overflow-y-auto custom-scrollbar pb-6">
        {navItems.map((item) => <NavLink key={item.id} item={item} />)}

        {!isSuperAdmin && !isCandidate && (
          <>
            <div className="pt-4 pb-2 px-4 uppercase text-[10px] tracking-widest text-slate-500 font-bold">Operations</div>
            {operationsItems.map((item) => <NavLink key={item.id} item={item} />)}
          </>
        )}

        {!isSuperAdmin && adminItems.length > 0 && (
          <>
            <div className="pt-4 pb-2 px-4 uppercase text-[10px] tracking-widest text-slate-500 font-bold">Admin Settings</div>
            {adminItems.map((item) => <NavLink key={item.id} item={item} />)}
          </>
        )}
        
        <div className="pt-4 mt-4 border-t border-white/5">
          <button
            onClick={handleLogout}
            className="w-full flex items-center gap-3 px-4 py-2 rounded-xl text-red-400 hover:text-red-300 hover:bg-red-500/10 transition-all text-sm"
          >
            <span className="material-symbols-outlined text-xl">logout</span>
            <span className="font-medium">Sign Out</span>
          </button>
        </div>
      </nav>

      <div className="p-4 border-t border-white/5 shrink-0 bg-black/20">
        <div className="flex items-center gap-3 p-2 rounded-xl hover:bg-white/5 transition-colors cursor-pointer" onClick={() => onPageChange('profile')}>
          <div className="w-10 h-10 rounded-full overflow-hidden border border-white/10 shrink-0">
            <img 
              alt="Admin Profile" 
              className="w-full h-full object-cover" 
              src={`https://ui-avatars.com/api/?name=${user?.name || 'User'}&background=8B5CF6&color=fff`}
            />
          </div>
          <div className="flex-1 min-w-0">
            <p className="text-sm font-semibold truncate text-white">{user?.name || 'Administrator'}</p>
            <p className="text-[10px] text-slate-500 uppercase font-bold truncate">{user?.role || 'Compliance Officer'}</p>
          </div>
        </div>
      </div>
    </aside>
  );
};

export default Sidebar;
