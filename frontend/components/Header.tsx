
import React from 'react';

interface HeaderProps {
  title: string;
}

const Header: React.FC<HeaderProps> = ({ title }) => {
  return (
    <header className="p-8 pb-4 flex justify-between items-end">
      <div>
        <h1 className="font-display text-4xl mb-1 text-white">{title}</h1>
        <div className="flex items-center gap-2">
          <div className="w-2 h-2 rounded-full bg-green-500 pulsing-dot"></div>
          <span className="text-xs text-slate-400 font-medium uppercase tracking-wider">Live System Sync Active</span>
        </div>
      </div>
      <div className="flex gap-4">
        <div className="relative">
          <span className="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-xl">search</span>
          <input 
            className="bg-white/5 border border-white/10 rounded-full py-2 pl-10 pr-6 text-sm focus:ring-primary focus:border-primary transition-all w-64 placeholder-slate-600 text-white focus:outline-none" 
            placeholder="Global search personnel..." 
            type="text"
          />
        </div>
        <button className="bg-primary hover:bg-primary/80 text-white px-6 py-2 rounded-full text-sm font-semibold transition-all shadow-lg shadow-primary/20">
          Export Report
        </button>
      </div>
    </header>
  );
};

export default Header;
