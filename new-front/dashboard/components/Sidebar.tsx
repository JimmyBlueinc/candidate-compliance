
import React from 'react';
import { PageId } from '../types';

interface SidebarProps {
  activePage: PageId;
  onPageChange: (id: PageId) => void;
}

const Sidebar: React.FC<SidebarProps> = ({ activePage, onPageChange }) => {
  const navItems = [
    { id: 'compliance', label: 'Compliance Hub', icon: 'dashboard' },
    { id: 'personnel', label: 'Personnel Database', icon: 'groups' },
    { id: 'pipeline', label: 'Credentialing Pipeline', icon: 'account_tree' },
    { id: 'analytics', label: 'Predictive Analytics', icon: 'analytics' },
  ];

  const adminItems = [
    { id: 'config', label: 'Global Configuration', icon: 'settings' },
    { id: 'access', label: 'Access Controls', icon: 'security' },
  ];

  // Fix: Explicitly type NavLink as React.FC to handle React-specific props like 'key'
  const NavLink: React.FC<{ item: typeof navItems[0] }> = ({ item }) => {
    const isActive = activePage === item.id;
    return (
      <button
        onClick={() => onPageChange(item.id as PageId)}
        className={`w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all border ${
          isActive 
            ? 'bg-white/5 text-primary border-primary/20' 
            : 'text-slate-400 hover:text-white hover:bg-white/5 border-transparent'
        }`}
      >
        <span className="material-symbols-outlined">{item.icon}</span>
        <span className="font-medium text-sm">{item.label}</span>
      </button>
    );
  };

  return (
    <aside className="w-64 glass-dark border-r border-white/5 flex flex-col z-20">
      <div className="p-6 mb-4">
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 bg-gradient-to-br from-primary to-accent-blue rounded-lg flex items-center justify-center shadow-lg shadow-primary/20">
            <span className="material-symbols-outlined text-white text-xl">health_metrics</span>
          </div>
          <span className="font-display text-xl tracking-tight text-white">HealthFlow</span>
        </div>
      </div>

      <nav className="flex-1 px-4 space-y-2">
        {navItems.map((item) => <NavLink key={item.id} item={item} />)}
        
        <div className="pt-6 pb-2 px-4 uppercase text-[10px] tracking-widest text-slate-500 font-bold">Admin Settings</div>
        
        {adminItems.map((item) => <NavLink key={item.id} item={item} />)}
      </nav>

      <div className="p-6 border-t border-white/5 mt-auto">
        <div className="flex items-center gap-3 p-2 rounded-xl hover:bg-white/5 transition-colors cursor-pointer">
          <div className="w-10 h-10 rounded-full overflow-hidden border border-white/10 shrink-0">
            <img 
              alt="Admin Profile" 
              className="w-full h-full object-cover" 
              src="https://picsum.photos/seed/doc1/200/200"
            />
          </div>
          <div className="flex-1 min-w-0">
            <p className="text-sm font-semibold truncate text-white">Dr. Sarah Jenkins</p>
            <p className="text-[10px] text-slate-500 uppercase font-bold truncate">Chief Compliance Officer</p>
          </div>
        </div>
      </div>
    </aside>
  );
};

export default Sidebar;
