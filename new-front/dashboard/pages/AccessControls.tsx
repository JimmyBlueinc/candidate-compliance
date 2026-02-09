
import React from 'react';

const AccessControls: React.FC = () => {
  const roles = [
    { name: 'Super Admin', users: 3, permissions: 'Full System Access' },
    { name: 'Compliance Lead', users: 12, permissions: 'Audit, Reporting, Editing' },
    { name: 'Facility Manager', users: 45, permissions: 'Regional Personnel View Only' },
    { name: 'Viewer', users: 120, permissions: 'Read-only Dashboards' },
  ];

  return (
    <div className="space-y-8">
      <div className="grid grid-cols-4 gap-6">
        {roles.map(role => (
          <div key={role.name} className="glass-dark p-6 rounded-3xl border border-white/5">
            <h4 className="text-lg font-display text-white mb-1">{role.name}</h4>
            <p className="text-xs text-slate-500 uppercase font-bold tracking-widest mb-4">{role.users} Users</p>
            <p className="text-sm text-slate-400 border-t border-white/5 pt-4">{role.permissions}</p>
          </div>
        ))}
      </div>

      <div className="glass-dark rounded-3xl overflow-hidden border border-white/5">
        <div className="p-6 border-b border-white/5 flex justify-between items-center">
          <h3 className="font-display text-2xl text-white">Recent Security Logs</h3>
          <button className="text-xs font-bold uppercase tracking-widest text-primary hover:underline">View All Logs</button>
        </div>
        <div className="divide-y divide-white/5">
          {[
            { user: 'admin@healthflow.com', action: 'Modified Permissions', target: 'Facility Manager', time: '12 mins ago' },
            { user: 'support@healthflow.com', action: 'Failed Login Attempt', target: 'N/A', time: '1 hour ago' },
            { user: 'jane.doe@mercy.org', action: 'Exported Compliance Report', target: 'Regional Dashboard', time: '3 hours ago' },
          ].map((log, i) => (
            <div key={i} className="px-6 py-4 flex items-center justify-between text-sm">
              <div className="flex items-center gap-3">
                <div className="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center">
                  <span className="material-symbols-outlined text-xs text-slate-500">lock_clock</span>
                </div>
                <div>
                  <span className="text-slate-200 font-medium">{log.user}</span>
                  <span className="mx-2 text-slate-600">•</span>
                  <span className="text-slate-400">{log.action}</span>
                </div>
              </div>
              <span className="text-slate-500 italic">{log.time}</span>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default AccessControls;
