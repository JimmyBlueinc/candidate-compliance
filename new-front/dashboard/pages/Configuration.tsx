
import React from 'react';

const Configuration: React.FC = () => {
  return (
    <div className="max-w-4xl mx-auto space-y-8">
      <div className="glass-dark p-8 rounded-3xl border border-white/5">
        <h3 className="font-display text-2xl mb-6 text-white">System Preferences</h3>
        <div className="space-y-6">
          <ConfigToggle 
            title="Auto-Sync External Records" 
            desc="Automatically pull credential data from state licensing boards every 24 hours." 
            active={true}
          />
          <ConfigToggle 
            title="Predictive Staffing Alerts" 
            desc="Notify administrators when AI predicts a personnel shortage threshold." 
            active={true}
          />
          <ConfigToggle 
            title="Two-Factor Authentication" 
            desc="Require 2FA for all administrative accounts for compliance safety." 
            active={false}
          />
        </div>
      </div>

      <div className="glass-dark p-8 rounded-3xl border border-white/5">
        <h3 className="font-display text-2xl mb-6 text-white">Facility Management</h3>
        <div className="grid grid-cols-2 gap-4">
          {['City Central', 'Mercy General', 'St. Marys', 'Health Hub'].map(facility => (
            <div key={facility} className="p-4 rounded-2xl bg-white/5 flex items-center justify-between">
              <span className="text-slate-200 font-medium">{facility}</span>
              <button className="material-symbols-outlined text-slate-500 hover:text-white transition-colors">edit</button>
            </div>
          ))}
          <button className="p-4 rounded-2xl border-2 border-dashed border-white/10 text-slate-500 hover:border-primary hover:text-primary transition-all flex items-center justify-center gap-2">
            <span className="material-symbols-outlined">add</span> Add New Facility
          </button>
        </div>
      </div>
    </div>
  );
};

const ConfigToggle = ({ title, desc, active }: { title: string, desc: string, active: boolean }) => (
  <div className="flex items-center justify-between gap-8 p-4 rounded-2xl bg-white/5">
    <div>
      <h4 className="text-slate-200 font-semibold">{title}</h4>
      <p className="text-sm text-slate-500">{desc}</p>
    </div>
    <div className={`w-12 h-6 rounded-full relative cursor-pointer transition-all ${active ? 'bg-primary' : 'bg-slate-700'}`}>
      <div className={`absolute top-1 w-4 h-4 rounded-full bg-white transition-all ${active ? 'right-1' : 'left-1'}`}></div>
    </div>
  </div>
);

export default Configuration;
