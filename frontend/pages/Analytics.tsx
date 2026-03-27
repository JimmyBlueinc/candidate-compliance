import React, { useEffect, useMemo, useState } from 'react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, AreaChart, Area } from 'recharts';
import { useApi } from '../lib/api';

type ChartMode = 'by_status' | 'by_type' | 'expiring_soon';

type AnalyticsResponse = {
  total?: number;
  by_status?: Record<string, number>;
  by_type?: Record<string, number>;
  expiring_next_30?: number;
  average_days_to_expiry?: number;
};

const Analytics: React.FC = () => {
  const { request } = useApi();
  const [mode, setMode] = useState<ChartMode>('by_status');
  const [analytics, setAnalytics] = useState<AnalyticsResponse | null>(null);
  const [loading, setLoading] = useState(true);
  useEffect(() => {
    const run = async () => {
      try {
        const response = await request('/analytics');
        setAnalytics(response || null);
      } catch {
        setAnalytics(null);
      } finally {
        setLoading(false);
      }
    };

    run();
  }, [request]);

  const data = useMemo((): Array<{ name: string; value: number }> => {
    if (!analytics) return [];

    if (mode === 'by_status') {
      const byStatus = analytics.by_status || {};
      return Object.entries(byStatus).map(([name, value]) => ({ name, value: Number(value) }));
    }

    if (mode === 'by_type') {
      const byType = analytics.by_type || {};
      return Object.entries(byType).map(([name, value]) => ({ name, value: Number(value) }));
    }

    const expiring = analytics.expiring_next_30 ?? 0;
    const notExpiring = Math.max(0, (analytics.total ?? 0) - expiring);
    return [
      { name: 'Expiring (30d)', value: expiring },
      { name: 'Not expiring', value: notExpiring },
    ];
  }, [analytics, mode]);

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-12 gap-6">
        <div className="col-span-8 glass-dark p-8 rounded-3xl border border-white/5 h-[400px]">
          <div className="flex items-center justify-between gap-4 mb-6">
            <h3 className="font-display text-2xl text-white">Compliance Trend Analysis</h3>
            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={() => setMode('by_status')}
                className={`px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-widest border transition-all ${
                  mode === 'by_status'
                    ? 'bg-white/10 text-white border-white/10'
                    : 'bg-transparent text-slate-400 border-white/5 hover:bg-white/5'
                }`}
              >
                By status
              </button>
              <button
                type="button"
                onClick={() => setMode('by_type')}
                className={`px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-widest border transition-all ${
                  mode === 'by_type'
                    ? 'bg-white/10 text-white border-white/10'
                    : 'bg-transparent text-slate-400 border-white/5 hover:bg-white/5'
                }`}
              >
                By type
              </button>
              <button
                type="button"
                onClick={() => setMode('expiring_soon')}
                className={`px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-widest border transition-all ${
                  mode === 'expiring_soon'
                    ? 'bg-white/10 text-white border-white/10'
                    : 'bg-transparent text-slate-400 border-white/5 hover:bg-white/5'
                }`}
              >
                Expiring soon
              </button>
            </div>
          </div>
          <div className="h-64">
            {loading ? (
              <p className="text-slate-500">Loading analytics...</p>
            ) : data.length === 0 ? (
              <p className="text-slate-500">No analytics data</p>
            ) : (
              <ResponsiveContainer width="100%" height="100%">
                <AreaChart data={data}>
                  <defs>
                    <linearGradient id="colorValue" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="#8B5CF6" stopOpacity={0.3}/>
                      <stop offset="95%" stopColor="#8B5CF6" stopOpacity={0}/>
                    </linearGradient>
                  </defs>
                  <XAxis dataKey="name" stroke="#64748b" fontSize={12} tickLine={false} axisLine={false} />
                  <YAxis stroke="#64748b" fontSize={12} tickLine={false} axisLine={false} />
                  <Tooltip 
                    contentStyle={{ backgroundColor: '#1e1e2d', border: 'none', borderRadius: '12px' }}
                    itemStyle={{ color: '#8B5CF6' }}
                  />
                  <Area type="monotone" dataKey="value" stroke="#8B5CF6" strokeWidth={3} fillOpacity={1} fill="url(#colorValue)" />
                </AreaChart>
              </ResponsiveContainer>
            )}
          </div>
        </div>
        <div className="col-span-4 glass-dark p-8 rounded-3xl border border-white/5 h-[400px] flex flex-col justify-between">
          <div>
            <h3 className="font-display text-2xl mb-2 text-white">Risk Exposure</h3>
            <p className="text-slate-500 text-sm">Quantified compliance risk across departments.</p>
          </div>
          <div className="space-y-6">
            <RiskMetric label="Emergency Dept" value={12} color="text-green-500" />
            <RiskMetric label="Pediatrics" value={45} color="text-amber-500" />
            <RiskMetric label="Radiology" value={8} color="text-green-500" />
            <RiskMetric label="Surgical" value={68} color="text-red-500" />
          </div>
          <button className="w-full py-3 rounded-xl bg-white/5 text-slate-400 font-bold text-xs uppercase tracking-widest hover:bg-white/10 transition-all">
            Generate Risk Report
          </button>
        </div>
      </div>
    </div>
  );
};

const RiskMetric = ({ label, value, color }: { label: string, value: number, color: string }) => (
  <div>
    <div className="flex justify-between items-center mb-2">
      <span className="text-sm font-medium text-slate-400">{label}</span>
      <span className={`text-sm font-bold ${color}`}>{value}% Risk</span>
    </div>
    <div className="w-full h-1.5 bg-white/5 rounded-full overflow-hidden">
      <div className={`h-full bg-current ${color}`} style={{ width: `${value}%` }}></div>
    </div>
  </div>
);

export default Analytics;
