
import React from 'react';

const Pipeline: React.FC = () => {
  const steps = [
    { label: 'Application Submitted', count: 45, color: 'bg-primary' },
    { label: 'Primary Source Verification', count: 32, color: 'bg-accent-blue' },
    { label: 'Committee Review', count: 18, color: 'bg-indigo-500' },
    { label: 'Final Approval', count: 12, color: 'bg-green-500' },
  ];

  return (
    <div className="grid grid-cols-4 gap-6">
      {steps.map(step => (
        <div key={step.label} className="glass-dark p-6 rounded-3xl border border-white/5 flex flex-col justify-between h-48">
          <div>
            <div className={`w-8 h-8 rounded-lg ${step.color} mb-4`}></div>
            <h4 className="text-sm font-bold text-slate-400 uppercase tracking-widest">{step.label}</h4>
          </div>
          <div className="flex items-end justify-between">
            <span className="text-4xl font-bold text-white">{step.count}</span>
            <span className="material-symbols-outlined text-slate-500">trending_up</span>
          </div>
        </div>
      ))}
      <div className="col-span-4 glass-dark p-8 rounded-3xl border border-white/5 mt-6">
        <h3 className="font-display text-2xl mb-6 text-white">Recent Pipeline Activity</h3>
        <div className="space-y-4">
          {[1, 2, 3].map(i => (
            <div key={i} className="flex items-center gap-4 p-4 rounded-2xl bg-white/5">
              <div className="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-primary">
                <span className="material-symbols-outlined">assignment_ind</span>
              </div>
              <div className="flex-1">
                <p className="text-slate-200 font-medium">New credentialing request for <span className="text-white font-bold">Dr. Amanda Lee</span></p>
                <p className="text-xs text-slate-500">Started 4 hours ago • Processing ID #CRD-892{i}</p>
              </div>
              <button className="px-4 py-2 rounded-xl border border-white/10 hover:bg-white/5 text-xs text-slate-400 transition-all">Review</button>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default Pipeline;
