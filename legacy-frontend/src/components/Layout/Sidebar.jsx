import { Link, useLocation } from 'react-router-dom';
import { useState, useEffect } from 'react';
import { 
  LayoutDashboard, Users, Shield, User, FileText, Calendar, 
  BarChart3, Bell, Settings, History, Calendar as CalendarIcon, 
  FolderOpen, FileCheck, Upload, Download, Search, HelpCircle,
  Mail, Archive, Filter, TrendingUp, Clock, AlertCircle, ChevronLeft, ChevronRight
} from 'lucide-react';
import { useAuth } from '../../contexts/AuthContext';

const Sidebar = () => {
  const location = useLocation();
  const { isSuperAdmin, isAdmin, isRecruiter, isCandidate } = useAuth();
  const [isCollapsed, setIsCollapsed] = useState(() => {
    const saved = localStorage.getItem('sidebarCollapsed');
    return saved ? JSON.parse(saved) : false;
  });

  useEffect(() => {
    localStorage.setItem('sidebarCollapsed', JSON.stringify(isCollapsed));
  }, [isCollapsed]);

  const toggleCollapse = () => {
    setIsCollapsed(!isCollapsed);
  };

  // Main Navigation Items
  const mainItems = [
    {
      name: 'Dashboard',
      path: '/dashboard',
      icon: <LayoutDashboard className="w-4 h-4 text-current" strokeWidth={2} />,
      visible: true,
    },
  ];

  // Credentials Section
  const credentialItems = [
    // Credential Management - Admin/Super Admin only
    ...((isAdmin || isSuperAdmin) ? [
      {
        name: 'Credential Management',
        path: '/credentials/tracker',
        icon: <Calendar className="w-4 h-4 text-current" strokeWidth={2} />,
        visible: true,
      },
    ] : []),
    // My Credentials - Candidates only
    ...(isCandidate ? [
      {
        name: 'My Credentials',
        path: '/credentials/my-credentials',
        icon: <FileText className="w-4 h-4 text-current" strokeWidth={2} />,
        visible: true,
      },
    ] : []),
    // Calendar View - All users (but data filtered by role)
    {
      name: 'Calendar View',
      path: '/calendar',
      icon: <CalendarIcon className="w-4 h-4 text-current" strokeWidth={2} />,
      visible: true,
    },
    // Documents - All users (but data filtered by role)
    {
      name: 'Documents',
      path: '/documents',
      icon: <FolderOpen className="w-4 h-4 text-current" strokeWidth={2} />,
      visible: true,
    },
    // Templates - Admin/Super Admin only
    {
      name: 'Templates',
      path: '/templates',
      icon: <FileCheck className="w-4 h-4 text-current" strokeWidth={2} />,
      visible: isAdmin || isSuperAdmin,
    },
  ];

  // Analytics & Reports Section - Admin/Super Admin only
  const analyticsItems = [
    {
      name: 'Compliance Dashboard',
      path: '/compliance',
      icon: <Shield className="w-4 h-4 text-current" strokeWidth={2} />,
      visible: isAdmin || isSuperAdmin,
    },
    {
      name: 'Analytics',
      path: '/analytics',
      icon: <BarChart3 className="w-4 h-4 text-current" strokeWidth={2} />,
      visible: isAdmin || isSuperAdmin,
    },
    {
      name: 'Reports',
      path: '/reports',
      icon: <TrendingUp className="w-4 h-4 text-current" strokeWidth={2} />,
      visible: isAdmin || isSuperAdmin,
    },
    {
      name: 'Activity Log',
      path: '/activity',
      icon: <History className="w-4 h-4 text-current" strokeWidth={2} />,
      visible: isAdmin || isSuperAdmin,
    },
  ];

  // Communication Section
  const communicationItems = [
    {
      name: 'Notifications',
      path: '/notifications',
      icon: <Bell className="w-4 h-4 text-current" strokeWidth={2} />,
      visible: true, // All users
    },
    {
      name: 'Email Settings',
      path: '/email-settings',
      icon: <Mail className="w-4 h-4 text-current" strokeWidth={2} />,
      visible: isAdmin || isSuperAdmin, // Admin/Super Admin only
    },
    {
      name: 'Reminders',
      path: '/reminders',
      icon: <Clock className="w-4 h-4 text-current" strokeWidth={2} />,
      visible: true, // All users (data filtered by role)
    },
  ];

  // Tools Section
  const toolsItems = [
    {
      name: 'Search',
      path: '/search',
      icon: <Search className="w-4 h-4 text-current" strokeWidth={2} />,
      visible: true, // All users
    },
    {
      name: 'Import/Export',
      path: '/import-export',
      icon: <Upload className="w-4 h-4 text-current" strokeWidth={2} />,
      visible: isAdmin || isSuperAdmin, // Admin/Super Admin only
    },
    {
      name: 'Bulk Operations',
      path: '/bulk-operations',
      icon: <Archive className="w-4 h-4 text-current" strokeWidth={2} />,
      visible: isAdmin || isSuperAdmin, // Admin/Super Admin only
    },
    {
      name: 'Advanced Filters',
      path: '/filters',
      icon: <Filter className="w-4 h-4 text-current" strokeWidth={2} />,
      visible: true, // All users (saved filters are user-specific)
    },
  ];

  // Administration Section
  const adminItems = [
    ...(isSuperAdmin ? [
      {
        name: 'User Management',
        path: '/admin/users',
        icon: <Users className="w-4 h-4 text-current" strokeWidth={2} />,
        visible: true,
      },
    ] : []),
    {
      name: 'Settings',
      path: '/settings',
      icon: <Settings className="w-4 h-4 text-current" strokeWidth={2} />,
      visible: true, // All users (personal settings)
    },
    {
      name: 'System Alerts',
      path: '/alerts',
      icon: <AlertCircle className="w-4 h-4 text-current" strokeWidth={2} />,
      visible: isAdmin || isSuperAdmin,
    },
  ];

  // Help Section
  const helpItems = [
    {
      name: 'Help & Support',
      path: '/help',
      icon: <HelpCircle className="w-4 h-4 text-current" strokeWidth={2} />,
      visible: true,
    },
  ];

  // Combine all items with sections
  const allSections = [
    { title: null, items: mainItems },
    { title: 'Credentials', items: credentialItems },
    { title: 'Analytics', items: analyticsItems },
    { title: 'Communication', items: communicationItems },
    { title: 'Tools', items: toolsItems },
    { title: 'Administration', items: adminItems },
    { title: 'Support', items: helpItems },
  ];

  // Filter out sections with no visible items
  const visibleSections = allSections
    .map(section => ({
      ...section,
      items: section.items.filter(item => item.visible)
    }))
    .filter(section => section.items.length > 0);

  return (
    <div className={`${isCollapsed ? 'w-16' : 'w-52'} min-h-screen border-r border-blue-700/30 shadow-large flex flex-col transition-all duration-300 relative`} style={{ background: 'linear-gradient(to bottom, #02646f, #02646f, #015a64)' }}>
      {/* Toggle Button */}
      <button
        onClick={toggleCollapse}
        className="absolute -right-3 top-1/2 -translate-y-1/2 z-50 w-6 h-6 rounded-full bg-goodwill-primary border-2 border-white shadow-lg flex items-center justify-center hover:bg-goodwill-primary/90 transition-all duration-200 hover:scale-110"
        aria-label={isCollapsed ? 'Expand sidebar' : 'Collapse sidebar'}
      >
        {isCollapsed ? (
          <ChevronRight className="w-3 h-3 text-white" strokeWidth={3} />
        ) : (
          <ChevronLeft className="w-3 h-3 text-white" strokeWidth={3} />
        )}
      </button>

      <div className={`p-4 border-b border-white/10 flex-shrink-0 ${isCollapsed ? 'px-2' : ''}`}>
        <div className="flex items-center gap-2">
          <div className="h-8 w-8 rounded-lg bg-white/20 backdrop-blur-sm shadow-medium flex items-center justify-center border border-white/30 flex-shrink-0">
            <LayoutDashboard className="w-4 h-4 text-white" strokeWidth={2} />
          </div>
          {!isCollapsed && (
            <div className="min-w-0">
              <h1 className="text-sm font-bold tracking-tight text-white truncate">Goodwill Staffing</h1>
              <p className="text-white/80 text-xs mt-0.5 font-medium truncate">Security & Compliance</p>
            </div>
          )}
        </div>
      </div>
      <nav className={`mt-4 ${isCollapsed ? 'px-2' : 'px-2'} space-y-4 overflow-y-auto flex-1 sidebar-scroll pb-4`}>
        {visibleSections.map((section, sectionIndex) => (
          <div key={sectionIndex}>
            {section.title && !isCollapsed && (
              <div className="px-5 py-1.5 mb-1.5 sticky top-0 z-10 bg-[#02646f] backdrop-blur-sm -mx-2">
                <h3 className="text-xs font-semibold text-white/70 uppercase tracking-wider flex items-center gap-1.5">
                  <span className="h-px flex-1 bg-white/20"></span>
                  <span>{section.title}</span>
                  <span className="h-px flex-1 bg-white/20"></span>
                </h3>
              </div>
            )}
            <div className="space-y-0.5">
              {section.items.map((item) => {
                const isActive = location.pathname === item.path;
                return (
                  <Link
                    key={item.path}
                    to={item.path}
                    className={`flex items-center ${isCollapsed ? 'justify-center px-2' : 'px-3'} py-1.5 rounded-md text-xs font-medium transition-all duration-200 group relative ${
                      isActive 
                        ? 'bg-white/20 backdrop-blur-sm text-white shadow-md shadow-white/20 border border-white/30' 
                        : 'text-white/80 hover:bg-white/10 hover:text-white'
                    }`}
                    title={isCollapsed ? item.name : ''}
                  >
                    <span className={`${isCollapsed ? '' : 'mr-2'} group-hover:scale-110 transition-transform duration-200 flex-shrink-0`}>{item.icon}</span>
                    {!isCollapsed && <span className="truncate">{item.name}</span>}
                    {isCollapsed && (
                      <span className="absolute left-full ml-2 px-2 py-1 bg-goodwill-primary text-white text-xs rounded shadow-lg whitespace-nowrap z-50 opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none">
                        {item.name}
                      </span>
                    )}
                  </Link>
                );
              })}
            </div>
          </div>
        ))}
      </nav>
    </div>
  );
};

export default Sidebar;

