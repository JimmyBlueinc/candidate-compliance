import React, { useMemo } from 'react';

function clsx(...parts) {
  return parts.filter(Boolean).join(' ');
}

function iconStyle(active) {
  return {
    fontSize: 22,
    lineHeight: '22px',
    opacity: active ? 1 : 0.85,
  };
}

export default function DashboardSidebar({
  brand,
  user,
  roleLabel,
  theme,
  sidebarCollapsed,
  currentRouteName,
  onToggleSidebar,
  onToggleTheme,
  onNavigate,
  onLogout,
}) {
  const role = user?.role || null;

  const groups = useMemo(() => {
    const ROLE_PLATFORM_ADMIN = 'platform_admin';
    const ROLE_ORG_SUPER_ADMIN = 'org_super_admin';
    const ROLE_ADMIN = 'admin';
    const ROLE_RECRUITER = 'recruiter';
    const ROLE_SCHEDULER = 'scheduler';
    const ROLE_COMPLIANCE = 'compliance';
    const ROLE_FINANCE = 'finance';
    const ROLE_LOGISTICS = 'logistics';
    const ROLE_CANDIDATE = 'candidate';
    const ROLE_FACILITY = 'facility';

    const isPlatformAdmin = role === ROLE_PLATFORM_ADMIN;
    const isOrgSuperAdmin = role === ROLE_ORG_SUPER_ADMIN;
    const isRecruiter = role === ROLE_ADMIN || role === ROLE_RECRUITER;
    const isScheduler = role === ROLE_SCHEDULER;
    const isCompliance = role === ROLE_COMPLIANCE;
    const isFinance = role === ROLE_FINANCE;
    const isLogistics = role === ROLE_LOGISTICS;
    const isCandidate = role === ROLE_CANDIDATE;
    const isFacility = role === ROLE_FACILITY;

    return [
      {
        id: 'admin',
        label: 'Admin',
        show: isOrgSuperAdmin,
        items: [
          { id: 'dashboard', label: 'Dashboard', icon: 'grid_view', routeName: 'dashboard.finance' },
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
        show: isFinance,
        items: [
          { id: 'dashboard', label: 'Overview', icon: 'grid_view', routeName: 'dashboard.finance' },
          { id: 'invoices', label: 'Invoices', icon: 'request_quote', routeName: 'dashboard.invoices' },
          { id: 'accounts_receivable', label: 'A/R', icon: 'account_balance_wallet', routeName: 'dashboard.accounts_receivable' },
        ],
      },
      {
        id: 'talent',
        label: 'Talent',
        show: isOrgSuperAdmin || isRecruiter,
        items: [
          { id: 'candidates', label: 'Candidates', icon: 'person_search', routeName: 'dashboard.candidates' },
          { id: 'intake_feed', label: 'Intake', icon: 'rss_feed', routeName: 'dashboard.intake_feed' },
          { id: 'intake_external', label: 'External', icon: 'cloud_upload', routeName: 'dashboard.intake_external' },
        ],
      },
      {
        id: 'compliance_group',
        label: 'Compliance',
        show: isCompliance,
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
        show: isOrgSuperAdmin || isRecruiter || isScheduler || isLogistics,
        items: [
          { id: 'jobs', label: 'Jobs', icon: 'work_outline', routeName: 'dashboard.job_orders', show: isOrgSuperAdmin || isRecruiter },
          { id: 'job_sources', label: 'Sources', icon: 'hub', routeName: 'dashboard.job_sources', show: isOrgSuperAdmin || isRecruiter },
          { id: 'placements', label: 'Placements', icon: 'swap_horiz', routeName: 'dashboard.placements', show: isOrgSuperAdmin || isRecruiter || isLogistics },
          { id: 'shifts', label: 'Shifts', icon: 'calendar_today', routeName: 'dashboard.shifts', show: isOrgSuperAdmin || isRecruiter || isScheduler },
          { id: 'timesheets', label: 'Timesheets', icon: 'timer', routeName: 'dashboard.timesheets', show: isOrgSuperAdmin || isRecruiter },
          { id: 'compliance', label: 'Compliance', icon: 'verified', routeName: 'dashboard.compliance', show: isOrgSuperAdmin || isRecruiter },
          { id: 'logistics', label: 'Logistics', icon: 'local_shipping', routeName: 'dashboard.logistics', show: isOrgSuperAdmin || isRecruiter || isLogistics },
          { id: 'messages', label: 'Messages', icon: 'chat_bubble', routeName: 'dashboard.messages' },
          { id: 'notifications', label: 'Notifications', icon: 'notifications', routeName: 'dashboard.notifications' },
        ].filter((i) => i.show !== false),
      },
      {
        id: 'godmode',
        label: 'Platform',
        show: isPlatformAdmin,
        items: [
          { id: 'health', label: 'Health', icon: 'monitor_heart', routeName: 'dashboard.platform_health' },
          { id: 'tenants', label: 'Orgs', icon: 'domain', routeName: 'dashboard.platform_organizations' },
          { id: 'broadcast', label: 'Broadcast', icon: 'campaign', routeName: 'dashboard.broadcast' },
        ],
      },
      {
        id: 'candidate',
        label: 'Candidate',
        show: isCandidate,
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
        show: isFacility,
        items: [
          { id: 'facility_dashboard', label: 'Home', icon: 'grid_view', routeName: 'facility.dashboard' },
          { id: 'facility_workers', label: 'Workers', icon: 'groups', routeName: 'facility.workers' },
          { id: 'facility_shifts', label: 'Shifts', icon: 'calendar_today', routeName: 'facility.shifts' },
          { id: 'facility_timesheets', label: 'Time', icon: 'timer', routeName: 'facility.timesheets' },
          { id: 'facility_invoices', label: 'Invoices', icon: 'request_quote', routeName: 'facility.invoices' },
        ],
      },
    ].filter((g) => g.show !== false);
  }, [role]);

  const primaryColor = brand?.primaryColor || 'var(--brand-primary, var(--p-primary-color))';

  const asideStyle = {
    width: sidebarCollapsed ? 76 : 288,
    background: 'var(--p-surface-card)',
    borderRight: '1px solid var(--p-surface-border)',
    height: '100vh',
    position: 'sticky',
    top: 0,
    overflow: 'hidden',
    display: 'flex',
    flexDirection: 'column',
    transition: 'width 200ms ease',
  };

  const activeStyle = {
    backgroundColor: `color-mix(in srgb, ${primaryColor} 12%, transparent)`,
    color: primaryColor,
    borderColor: `color-mix(in srgb, ${primaryColor} 35%, transparent)`,
  };

  const inactiveStyle = {
    color: 'var(--p-text-color)',
    borderColor: 'transparent',
  };

  return (
    <aside style={asideStyle}>
      <div style={{ padding: '16px 20px', flexShrink: 0 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 12, minWidth: 0 }}>
          <div
            style={{
              width: 40,
              height: 40,
              borderRadius: 16,
              overflow: 'hidden',
              border: '1px solid var(--p-surface-border)',
              background: 'var(--p-surface-0)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              flexShrink: 0,
            }}
          >
            {brand?.logoUrl ? (
              <img src={brand.logoUrl} alt="Logo" style={{ width: '100%', height: '100%', objectFit: 'contain', padding: 8 }} />
            ) : (
              <span className="material-symbols-outlined" style={{ color: 'var(--p-text-muted-color)', fontSize: 20 }}>
                health_metrics
              </span>
            )}
          </div>

          {!sidebarCollapsed ? (
            <div style={{ minWidth: 0 }}>
              <div
                style={{
                  fontSize: 18,
                  fontWeight: 700,
                  letterSpacing: '-0.02em',
                  color: 'var(--p-text-color)',
                  whiteSpace: 'nowrap',
                  overflow: 'hidden',
                  textOverflow: 'ellipsis',
                }}
                title={brand?.name || 'AgencyHQ'}
              >
                {brand?.name || 'AgencyHQ'}
              </div>
              <div style={{ fontSize: 10, fontWeight: 900, letterSpacing: '0.22em', textTransform: 'uppercase', color: 'var(--p-text-muted-color)' }}>
                Dashboard
              </div>
            </div>
          ) : null}

          <button
            type="button"
            onClick={onToggleSidebar}
            title={sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'}
            style={{
              marginLeft: 'auto',
              width: 36,
              height: 36,
              borderRadius: 12,
              border: '1px solid var(--p-surface-border)',
              background: 'transparent',
              display: 'inline-flex',
              alignItems: 'center',
              justifyContent: 'center',
              cursor: 'pointer',
            }}
          >
            <span className="material-symbols-outlined" style={{ fontSize: 18, color: 'var(--p-text-muted-color)' }}>
              {sidebarCollapsed ? 'chevron_right' : 'chevron_left'}
            </span>
          </button>
        </div>
      </div>

      <nav style={{ flex: 1, padding: '0 16px 20px', overflowY: 'auto' }}>
        {groups.map((group) => (
          <div key={group.id} style={{ marginBottom: 20 }}>
            {!sidebarCollapsed ? (
              <div
                style={{
                  padding: '4px 8px 8px',
                  fontSize: 10,
                  fontWeight: 900,
                  letterSpacing: '0.22em',
                  textTransform: 'uppercase',
                  color: 'var(--p-text-muted-color)',
                }}
              >
                {group.label}
              </div>
            ) : null}

            <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
              {group.items.map((item) => {
                const active = String(currentRouteName || '') === String(item.routeName || '');
                const style = active ? activeStyle : inactiveStyle;
                return (
                  <button
                    key={item.id}
                    type="button"
                    title={item.label}
                    onClick={() => onNavigate?.(item.routeName)}
                    style={{
                      width: '100%',
                      display: 'flex',
                      alignItems: 'center',
                      gap: 12,
                      padding: '10px 12px',
                      borderRadius: 16,
                      border: '1px solid',
                      background: 'transparent',
                      cursor: 'pointer',
                      transition: 'background-color 150ms ease, border-color 150ms ease, color 150ms ease',
                      ...style,
                    }}
                  >
                    <span className="material-symbols-outlined" style={iconStyle(active)}>
                      {item.icon}
                    </span>
                    {!sidebarCollapsed ? <span style={{ fontSize: 14, fontWeight: 600, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>{item.label}</span> : null}
                  </button>
                );
              })}
            </div>
          </div>
        ))}

        <div style={{ paddingTop: 16, marginTop: 16, borderTop: '1px solid var(--p-surface-border)' }}>
          <button
            type="button"
            onClick={onLogout}
            title="Sign Out"
            style={{
              width: '100%',
              display: 'flex',
              alignItems: 'center',
              gap: 12,
              padding: '10px 12px',
              borderRadius: 16,
              border: '1px solid transparent',
              background: 'transparent',
              cursor: 'pointer',
              color: '#ef4444',
            }}
          >
            <span className="material-symbols-outlined" style={{ fontSize: 22 }}>
              logout
            </span>
            {!sidebarCollapsed ? <span style={{ fontSize: 14, fontWeight: 600 }}>Sign Out</span> : null}
          </button>
        </div>
      </nav>

      <div style={{ padding: 16, borderTop: '1px solid var(--p-surface-border)', flexShrink: 0, background: 'var(--p-surface-0)' }}>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
          <button
            type="button"
            onClick={onToggleTheme}
            title="Theme"
            style={{
              width: '100%',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              gap: 12,
              padding: 8,
              borderRadius: 16,
              border: '1px solid var(--p-surface-border)',
              background: 'transparent',
              cursor: 'pointer',
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', gap: 12, minWidth: 0 }}>
              <span className="material-symbols-outlined" style={{ fontSize: 22, color: 'var(--p-text-muted-color)' }}>
                {theme === 'light' ? 'dark_mode' : 'light_mode'}
              </span>
              {!sidebarCollapsed ? (
                <div style={{ minWidth: 0, textAlign: 'left' }}>
                  <div style={{ fontSize: 14, fontWeight: 600 }}>Theme</div>
                  <div style={{ fontSize: 10, fontWeight: 900, letterSpacing: '0.22em', textTransform: 'uppercase', color: 'var(--p-text-muted-color)' }}>
                    {theme === 'light' ? 'Light' : 'Dark'}
                  </div>
                </div>
              ) : null}
            </div>
            {!sidebarCollapsed ? (
              <span className="material-symbols-outlined" style={{ color: 'var(--p-text-muted-color)' }}>
                swap_horiz
              </span>
            ) : null}
          </button>

          <button
            type="button"
            onClick={() => onNavigate?.('dashboard.profile')}
            title="Profile"
            style={{
              width: '100%',
              display: 'flex',
              alignItems: 'center',
              gap: 12,
              padding: 8,
              borderRadius: 16,
              border: '1px solid transparent',
              background: 'transparent',
              cursor: 'pointer',
            }}
          >
            <div
              style={{
                width: 40,
                height: 40,
                borderRadius: 999,
                overflow: 'hidden',
                border: '1px solid var(--p-surface-border)',
                flexShrink: 0,
              }}
            >
              <img
                alt="Admin Profile"
                style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                src={`https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name || 'User')}&background=8B5CF6&color=fff`}
              />
            </div>
            {!sidebarCollapsed ? (
              <div style={{ minWidth: 0 }}>
                <div style={{ fontSize: 14, fontWeight: 600, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>{user?.name || 'Administrator'}</div>
                <div style={{ fontSize: 10, fontWeight: 900, letterSpacing: '0.22em', textTransform: 'uppercase', color: 'var(--p-text-muted-color)' }}>{roleLabel || ''}</div>
              </div>
            ) : null}
          </button>
        </div>
      </div>
    </aside>
  );
}
