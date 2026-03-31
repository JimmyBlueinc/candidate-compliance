
import React from 'react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, AreaChart, Area } from 'recharts';

const data = [
  { name: 'Jan', value: 400 },
  { name: 'Feb', value: 300 },
  { name: 'Mar', value: 600 },
  { name: 'Apr', value: 800 },
  { name: 'May', value: 500 },
  { name: 'Jun', value: 900 },
  { name: 'Jul', value: 1100 },
];

const Analytics: React.FC = () => {
  return (
    <div className="space-y-6">
      <div className="grid grid-cols-12 gap-6">
        <div className="col-span-8 glass-dark p-8 rounded-3xl border border-white/5 h-[400px]">
          <h3 className="font-display text-2xl mb-6 text-white">Compliance Trend Analysis</h3>
          <div className="h-64">
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
