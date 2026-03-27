import React, { useEffect, useMemo, useState } from 'react';
import { ActionItem } from '../types';
import { useApi } from '../lib/api';

type CredentialRow = {
  id: string | number;
  candidate_name: string;
  facility?: string | null;
  credential_type?: string | null;
  expiry_date?: string | null;
  status?: string | null;
};

const ComplianceHub: React.FC = () => {
  const { request } = useApi();
  const [loading, setLoading] = useState(true);
  const [credentials, setCredentials] = useState<CredentialRow[]>([]);
  const [backgroundChecks, setBackgroundChecks] = useState<any[]>([]);
  const [healthRecords, setHealthRecords] = useState<any[]>([]);
  const [workAuths, setWorkAuths] = useState<any[]>([]);
  const [initialLoad, setInitialLoad] = useState(true);

  useEffect(() => {
    if (!initialLoad) return;

    const run = async () => {
      try {
        const normalize = (res: any) => {
          if (Array.isArray(res)) return res;
          if (res && Array.isArray(res.data)) return res.data;
          return [];
        };

        const results = await Promise.allSettled([
          request('/credentials'),
          request('/background-checks'),
          request('/health-records'),
          request('/work-authorizations'),
        ]);

        const getVal = (i: number) => (results[i].status === 'fulfilled' ? normalize(results[i].value) : []);

        setCredentials(getVal(0));
        setBackgroundChecks(getVal(1));
        setHealthRecords(getVal(2));
        setWorkAuths(getVal(3));
      } catch {
        setCredentials([]);
        setBackgroundChecks([]);
        setHealthRecords([]);
        setWorkAuths([]);
      } finally {
        setLoading(false);
        setInitialLoad(false);
      }
    };

    run();
  }, [request, initialLoad]);

  const criticalActions: ActionItem[] = useMemo(() => {
    const items: ActionItem[] = [];

    const now = new Date();
    const in30Days = new Date(now);
    in30Days.setDate(in30Days.getDate() + 30);

    const expiringCreds = credentials
      .filter(c => c.expiry_date)
      .map(c => {
        const expiry = new Date(String(c.expiry_date));
        return { c, expiry };
      })
      .filter(({ expiry }) => !Number.isNaN(expiry.getTime()) && expiry >= now && expiry <= in30Days)
      .sort((a, b) => a.expiry.getTime() - b.expiry.getTime())
      .slice(0, 6);

    for (const { c, expiry } of expiringCreds) {
      const diffDays = Math.max(0, Math.ceil((expiry.getTime() - now.getTime()) / (1000 * 60 * 60 * 24)));
      items.push({
        id: `cred-${c.id}`,
        title: c.credential_type ? `${c.credential_type} Expiry` : 'Credential Expiry',
        deadline: `${diffDays} days left`,
        assignee: c.candidate_name || 'Unknown',
        facility: (c.facility as string) || '—',
        urgent: diffDays <= 7,
      });
    }

    const addGeneric = (arr: any[], prefix: string, title: string) => {
      for (const row of arr.slice(0, Math.max(0, 6 - items.length))) {
        const id = row?.id ?? Math.random().toString(36).slice(2);
        items.push({
          id: `${prefix}-${id}`,
          title,
          deadline: 'Pending',
          assignee: row?.candidate_name || row?.user?.name || 'Admin',
          facility: row?.facility || '—',
        });
      }
    };

    if (items.length < 6) addGeneric(backgroundChecks, 'bg', 'Background Check');
    if (items.length < 6) addGeneric(healthRecords, 'health', 'Health Record');
    if (items.length < 6) addGeneric(workAuths, 'wa', 'Work Authorization');

    return items.slice(0, 6);
  }, [backgroundChecks, credentials, healthRecords, workAuths]);

  return (
    <div className="space-y-8 animate-[fadeInUp_0.5s_ease-out_both]">
      <div className="grid grid-cols-12 gap-8">
        {/* Main Health Card */}
        <div className="col-span-12 lg:col-span-8 glass-dark rounded-[32px] p-10 relative overflow-hidden group border border-white/5 min-h-[400px] flex flex-col justify-center">
          <div className="absolute top-0 left-0 z-20 px-3 py-1 m-6 rounded-full text-[10px] font-black uppercase tracking-[0.25em] bg-red-500/10 text-red-400 border border-red-500/20">
            UI-MARKER: NEW
          </div>
          <div className="absolute top-0 right-0 p-6">
            <button className="p-2 rounded-full hover:bg-white/5 transition-colors group/info">
              <span className="material-symbols-outlined text-slate-500 opacity-50 group-hover/info:opacity-100 transition-opacity">info</span>
            </button>
          </div>
          <div className="flex flex-col md:flex-row h-full items-center gap-10">
            <div className="flex-1 text-center md:text-left">
              <div className="inline-block px-3 py-1 rounded-full bg-primary/10 border border-primary/20 text-primary text-[10px] font-bold uppercase tracking-widest mb-4">System Status: Optimal</div>
              <h2 className="text-4xl font-display mb-3 text-white tracking-tight leading-tight">Global Compliance Health</h2>
              <p className="text-slate-400 text-base max-w-sm mb-10 leading-relaxed mx-auto md:mx-0">Real-time monitoring across all active healthcare facilities and personnel.</p>
              
              <div className="grid grid-cols-2 gap-6 mb-10">
                <div className="space-y-1">
                  <div className="flex items-center gap-2 justify-center md:justify-start">
                    <div className="w-2 h-2 rounded-full bg-primary shadow-[0_0_8px_rgba(139,92,246,0.5)]"></div>
                    <span className="text-xs font-bold text-slate-500 uppercase tracking-wider">Credentialed</span>
                  </div>
                  <p className="text-2xl font-bold text-white">8,432</p>
                </div>
                <div className="space-y-1">
                  <div className="flex items-center gap-2 justify-center md:justify-start">
                    <div className="w-2 h-2 rounded-full bg-slate-700"></div>
                    <span className="text-xs font-bold text-slate-500 uppercase tracking-wider">Pending</span>
                  </div>
                  <p className="text-2xl font-bold text-white">412</p>
                </div>
              </div>

              <div className="flex items-end gap-4 justify-center md:justify-start">
                <span className="text-6xl font-bold text-white tracking-tighter">95.3%</span>
                <div className="flex items-center gap-1 text-green-500 text-sm font-bold mb-2">
                  <span className="material-symbols-outlined text-sm">trending_up</span>
                  <span>+0.4%</span>
                </div>
              </div>
            </div>
            
            <div className="relative w-72 h-72 flex items-center justify-center shrink-0">
              <svg className="w-full h-full transform -rotate-90">
                <circle 
                  className="text-white/[0.03]" 
                  cx="50%" cy="50%" fill="transparent" r="110" stroke="currentColor" strokeWidth="20"
                ></circle>
                <circle 
                  className="text-primary drop-shadow-[0_0_20px_rgba(139,92,246,0.4)]" 
                  cx="50%" cy="50%" fill="transparent" r="110" 
                  stroke="currentColor" strokeDasharray="691" strokeDashoffset="173" 
                  strokeWidth="20" strokeLinecap="round"
                ></circle>
              </svg>
              <div className="absolute flex flex-col items-center">
                <div className="w-16 h-16 bg-white/5 rounded-2xl flex items-center justify-center border border-white/10 mb-3 shadow-2xl">
                  <span className="material-symbols-outlined text-4xl text-primary drop-shadow-[0_0_8px_rgba(139,92,246,0.5)]">verified_user</span>
                </div>
                <span className="text-[10px] uppercase tracking-[0.2em] text-slate-500 font-black">Score</span>
              </div>
            </div>
          </div>
        </div>

        {/* Critical Actions */}
        <div className="col-span-12 lg:col-span-4 glass-dark rounded-[32px] p-8 flex flex-col border border-white/5 min-h-[400px]">
          <div className="flex justify-between items-center mb-8">
            <div>
              <h3 className="font-display text-xl text-white">Critical Tasks</h3>
              <p className="text-xs text-slate-500 font-medium">Items requiring immediate action</p>
            </div>
            <span className="bg-red-500/10 text-red-500 text-[10px] font-black px-2.5 py-1 rounded-full border border-red-500/20 tracking-tighter">12 ALERTING</span>
          </div>
          <div className="flex-1 overflow-y-auto space-y-4 pr-2 custom-scrollbar">
            {criticalActions.map(item => (
              <div key={item.id} className="p-4 rounded-2xl bg-white/[0.03] border border-white/5 hover:bg-white/[0.06] hover:border-white/10 transition-all cursor-pointer group relative overflow-hidden">
                {item.urgent && (
                  <div className="absolute left-0 top-0 bottom-0 w-1 bg-red-500/50"></div>
                )}
                <div className="flex justify-between items-start mb-2">
                  <span className="text-sm font-bold text-slate-200 group-hover:text-white transition-colors">{item.title}</span>
                  <span className={`text-[9px] font-black tracking-widest uppercase px-2 py-0.5 rounded-md ${item.urgent ? 'bg-red-500/10 text-red-400' : 'bg-slate-500/10 text-slate-500'}`}>
                    {item.deadline}
                  </span>
                </div>
                <div className="flex items-center gap-2 text-xs text-slate-500">
                  <span className="material-symbols-outlined text-sm opacity-50">person</span>
                  <span className="truncate">{item.assignee}</span>
                  <span className="opacity-30">•</span>
                  <span className="truncate">{item.facility}</span>
                </div>
              </div>
            ))}
          </div>
          <button className="w-full mt-6 py-3 rounded-2xl bg-white/5 border border-white/5 text-slate-400 text-xs font-bold hover:bg-white/10 hover:text-white transition-all">View All Alerts</button>
        </div>

        {/* Secondary Cards Row */}
        <div className="col-span-12 md:col-span-6 glass-dark rounded-[32px] p-8 flex flex-col justify-center border border-white/5 min-h-[200px]">
          <div className="flex justify-between items-start mb-6">
            <div>
              <p className="text-[10px] text-slate-500 font-black uppercase tracking-[0.2em] mb-1">Active Pipeline</p>
              <h3 className="font-display text-2xl text-white tracking-tight">Personnel Onboarding</h3>
            </div>
            <div className="text-right bg-primary/10 px-4 py-2 rounded-2xl border border-primary/10">
              <span className="text-2xl font-black text-white">128</span>
              <p className="text-[9px] text-primary font-black uppercase tracking-widest">In-process</p>
            </div>
          </div>
          <div className="w-full h-3 bg-white/[0.03] rounded-full overflow-hidden mb-4 border border-white/5 p-0.5">
            <div className="h-full bg-gradient-to-r from-primary to-accent-blue rounded-full w-[72%] shadow-[0_0_10px_rgba(139,92,246,0.3)]"></div>
          </div>
          <div className="flex justify-between text-[9px] text-slate-500 font-black uppercase tracking-widest px-1">
            <span className="text-primary/70">Background</span>
            <span>Interviewing</span>
            <span>Finalized</span>
          </div>
        </div>

        <div className="col-span-12 md:col-span-6 glass-dark rounded-[32px] p-8 flex items-center justify-between border border-white/5 min-h-[200px]">
          <div className="flex-1">
            <p className="text-[10px] text-slate-500 font-black uppercase tracking-[0.2em] mb-1">Efficiency Metrics</p>
            <h3 className="font-display text-2xl text-white tracking-tight mb-6">Facility Utilization</h3>
            <div className="flex gap-2">
              {[0.2, 0.4, 1.0, 0.6, 0.3, 0.8, 0.5, 0.9].map((opacity, i) => (
                <div 
                  key={i} 
                  className={`flex-1 h-10 rounded-xl bg-primary transition-all duration-500 hover:scale-110 cursor-help ${opacity === 1 ? 'shadow-[0_0_15px_rgba(139,92,246,0.4)] border border-white/20' : 'opacity-20 hover:opacity-40'}`}
                  style={opacity === 1 ? { opacity: 1 } : {}}
                ></div>
              ))}
            </div>
          </div>
          <div className="text-right ml-8">
            <div className="text-5xl font-black text-accent-blue tracking-tighter">88%</div>
            <p className="text-[10px] text-slate-500 font-black uppercase tracking-widest mt-1">Peak Efficiency</p>
          </div>
        </div>

        {/* AI Recommendation Banner */}
        <div className="col-span-12 glass-dark rounded-[32px] p-8 flex flex-col md:flex-row items-center gap-8 border border-primary/20 bg-gradient-to-r from-primary/5 via-transparent to-accent-blue/5 shadow-2xl relative overflow-hidden">
          <div className="absolute top-0 left-0 w-full h-full bg-[radial-gradient(circle_at_30%_50%,rgba(139,92,246,0.1),transparent_50%)] pointer-events-none"></div>
          <div className="w-20 h-20 bg-primary/10 rounded-[24px] flex items-center justify-center border border-primary/20 shrink-0 shadow-inner">
            <span className="material-symbols-outlined text-5xl text-primary drop-shadow-[0_0_8px_rgba(139,92,246,0.5)]">auto_graph</span>
          </div>
          <div className="flex-1 text-center md:text-left z-10">
            <div className="flex items-center gap-2 justify-center md:justify-start mb-2">
              <span className="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
              <h3 className="font-display text-2xl text-white tracking-tight">Staffing Intelligence AI</h3>
            </div>
            <p className="text-slate-400 text-base leading-relaxed max-w-3xl">
              Predictive models indicate a <span className="text-white font-bold px-1.5 py-0.5 bg-primary/20 rounded-lg mx-1 border border-primary/20">12% increase</span> in specialized RN demand at City Central for Q3. 
              We recommend initiating credentialing for 15 additional personnel now to maintain 100% compliance levels.
            </p>
          </div>
          <div className="flex flex-col sm:flex-row gap-4 shrink-0 z-10 w-full md:w-auto">
            <button className="px-8 py-3.5 rounded-2xl bg-white/5 hover:bg-white/10 text-sm font-bold transition-all border border-white/5 text-slate-300">Dismiss</button>
            <button className="px-8 py-3.5 rounded-2xl bg-white text-black text-sm font-black hover:scale-[1.02] active:scale-[0.98] transition-all shadow-[0_0_20px_rgba(255,255,255,0.1)]">Optimize Now</button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ComplianceHub;
