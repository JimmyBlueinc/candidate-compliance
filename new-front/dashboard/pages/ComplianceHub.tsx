
import React from 'react';
import { ActionItem } from '../types';

const ComplianceHub: React.FC = () => {
  const criticalActions: ActionItem[] = [
    { id: '1', title: 'RN License Renewal', deadline: '2 days left', assignee: 'Dr. Michael Chen', facility: 'Mercy General', urgent: true },
    { id: '2', title: 'BLS Certification', deadline: '5 days left', assignee: 'Nurse Lisa Ray', facility: 'St. Marys', urgent: true },
    { id: '3', title: 'Background Check', deadline: '7 days left', assignee: 'Admin Staff', facility: 'Central Clinic' },
    { id: '4', title: 'NP Malpractice', deadline: '9 days left', assignee: "Sarah O'Connel", facility: 'Health Hub' },
  ];

  return (
    <div className="grid grid-cols-12 gap-6">
      {/* Main Health Card */}
      <div className="col-span-8 glass-dark rounded-3xl p-8 relative overflow-hidden group border border-white/5 h-[380px]">
        <div className="absolute top-0 right-0 p-4">
          <span className="material-symbols-outlined text-slate-500 opacity-50 group-hover:opacity-100 transition-opacity cursor-pointer">info</span>
        </div>
        <div className="flex h-full items-center">
          <div className="flex-1">
            <h2 className="text-2xl font-display mb-2 text-white">Global Compliance Health</h2>
            <p className="text-slate-400 text-sm max-w-xs mb-8 leading-relaxed">System-wide monitoring across all healthcare facilities in real-time.</p>
            <div className="space-y-4">
              <div className="flex items-center gap-3">
                <div className="w-3 h-3 rounded-full bg-primary shadow-[0_0_10px_rgba(139,92,246,0.5)]"></div>
                <span className="text-sm font-medium text-slate-200">Fully Credentialed (8,432)</span>
              </div>
              <div className="flex items-center gap-3">
                <div className="w-3 h-3 rounded-full bg-slate-700"></div>
                <span className="text-sm font-medium text-slate-200">Verification Pending (412)</span>
              </div>
              <div className="mt-8">
                <span className="text-5xl font-bold text-white tracking-tight">95.3%</span>
                <span className="text-green-500 text-sm font-medium ml-3">+0.4% from last month</span>
              </div>
            </div>
          </div>
          
          <div className="relative w-64 h-64 flex items-center justify-center shrink-0">
            <svg className="w-full h-full transform -rotate-90">
              <circle 
                className="text-white/5" 
                cx="50%" cy="50%" fill="transparent" r="95" stroke="currentColor" strokeWidth="16"
              ></circle>
              <circle 
                className="text-primary drop-shadow-[0_0_15px_rgba(139,92,246,0.6)]" 
                cx="50%" cy="50%" fill="transparent" r="95" 
                stroke="currentColor" strokeDasharray="596" strokeDashoffset="150" 
                strokeWidth="16" strokeLinecap="round"
              ></circle>
            </svg>
            <div className="absolute flex flex-col items-center">
              <span className="material-symbols-outlined text-4xl text-primary">verified_user</span>
              <span className="text-xs uppercase tracking-widest text-slate-500 mt-2 font-bold">Optimal</span>
            </div>
          </div>
        </div>
      </div>

      {/* Critical Actions */}
      <div className="col-span-4 glass-dark rounded-3xl p-6 flex flex-col border border-amber-500/10 h-[380px]">
        <div className="flex justify-between items-center mb-6">
          <h3 className="font-display text-xl text-amber-500/90">Critical Action Items</h3>
          <span className="bg-amber-500/10 text-amber-500 text-[10px] font-bold px-2 py-1 rounded border border-amber-500/20">12 ALERTING</span>
        </div>
        <div className="flex-1 overflow-y-auto space-y-3 pr-2 scrollbar-thin">
          {criticalActions.map(item => (
            <div key={item.id} className="p-3 rounded-xl bg-white/5 border border-white/5 hover:bg-white/10 transition-colors cursor-pointer group">
              <div className="flex justify-between mb-1">
                <span className="text-sm font-semibold text-slate-200">{item.title}</span>
                <span className={`text-[10px] font-bold uppercase ${item.urgent ? 'text-amber-500' : 'text-slate-500'}`}>
                  {item.deadline}
                </span>
              </div>
              <p className="text-xs text-slate-500 group-hover:text-slate-400 transition-colors">{item.assignee} • {item.facility}</p>
            </div>
          ))}
        </div>
      </div>

      {/* Personnel Onboarding */}
      <div className="col-span-6 glass-dark rounded-3xl p-8 flex flex-col justify-center h-[180px]">
        <div className="flex justify-between items-end mb-4">
          <div>
            <p className="text-[10px] text-slate-500 font-bold uppercase tracking-widest mb-1">Active Pipeline</p>
            <h3 className="font-display text-2xl text-white">Personnel Onboarding</h3>
          </div>
          <div className="text-right">
            <span className="text-3xl font-bold text-white">128</span>
            <p className="text-[10px] text-slate-500 uppercase font-bold">In-process</p>
          </div>
        </div>
        <div className="w-full h-2.5 bg-white/5 rounded-full overflow-hidden">
          <div className="h-full bg-gradient-to-r from-primary to-accent-blue rounded-full w-[72%]"></div>
        </div>
        <div className="flex justify-between mt-3 text-[10px] text-slate-500 font-bold uppercase tracking-tighter">
          <span>Background Docs</span>
          <span>Interviewing</span>
          <span>Finalized</span>
        </div>
      </div>

      {/* Facility Utilization */}
      <div className="col-span-6 glass-dark rounded-3xl p-8 flex items-center justify-between h-[180px]">
        <div>
          <h3 className="font-display text-2xl mb-1 text-white">Facility Utilization</h3>
          <p className="text-sm text-slate-400">Optimal staffing distribution</p>
          <div className="flex gap-1.5 mt-5">
            {[0.2, 0.4, 1.0, 0.6, 0.3, 0.8].map((opacity, i) => (
              <div 
                key={i} 
                className={`w-7 h-7 rounded-lg bg-primary ${opacity === 1 ? 'shadow-[0_0_12px_rgba(139,92,246,0.4)]' : ''}`}
                style={{ opacity }}
              ></div>
            ))}
          </div>
        </div>
        <div className="text-right">
          <div className="text-4xl font-bold text-accent-blue">88%</div>
          <p className="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1">Peak Efficiency</p>
        </div>
      </div>

      {/* AI Recommendation Banner */}
      <div className="col-span-12 glass-dark rounded-3xl p-6 flex items-center gap-8 border-l-4 border-primary shadow-2xl">
        <div className="p-4 bg-primary/10 rounded-2xl shrink-0">
          <span className="material-symbols-outlined text-4xl text-primary">auto_graph</span>
        </div>
        <div className="flex-1">
          <h3 className="font-display text-xl mb-1 text-white">Predictive Staffing Intelligence</h3>
          <p className="text-slate-400 text-sm leading-relaxed">
            Our AI models predict a <span className="text-primary font-bold">12% increase</span> in specialized RN demand at City Central for Q3. 
            We recommend initiating credentialing for 15 additional personnel now to maintain 100% compliance.
          </p>
        </div>
        <div className="flex gap-3 shrink-0">
          <button className="px-6 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-sm font-semibold transition-all border border-white/10 text-slate-200">Dismiss</button>
          <button className="px-6 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary/80 transition-all shadow-lg shadow-primary/30">Optimize Now</button>
        </div>
      </div>
    </div>
  );
};

export default ComplianceHub;
