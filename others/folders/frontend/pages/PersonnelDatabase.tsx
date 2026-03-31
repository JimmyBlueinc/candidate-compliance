import React, { useEffect, useState } from 'react';
import { useApi } from '../lib/api';

type CredentialRow = {
  id: string | number;
  candidate_name: string;
  position: string | null;
  credential_type: string | null;
  status: string | null;
};

const PersonnelDatabase: React.FC = () => {
  const { request } = useApi();
  const [rows, setRows] = useState<CredentialRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [initialLoad, setInitialLoad] = useState(true);

  useEffect(() => {
    const run = async () => {
      try {
        setLoading(true);
        const response = await request('/credentials');
        setRows(response.data || []);
      } catch {
        setRows([]);
      } finally {
        setLoading(false);
        setInitialLoad(false);
      }
    };

    if (initialLoad) {
      run();
    }
  }, [request, initialLoad]);

  return (
    <div className="glass-dark rounded-3xl overflow-hidden border border-white/5">
      <table className="w-full text-left">
        <thead>
          <tr className="border-b border-white/5 bg-white/5">
            <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Name</th>
            <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Position</th>
            <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Credential</th>
            <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Status</th>
            <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Actions</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-white/5">
          {loading ? (
            <tr>
              <td className="px-6 py-6 text-slate-500" colSpan={5}>Loading...</td>
            </tr>
          ) : rows.length === 0 ? (
            <tr>
              <td className="px-6 py-6 text-slate-500" colSpan={5}>No records found</td>
            </tr>
          ) : (
            rows.map(row => (
              <tr key={row.id} className="hover:bg-white/5 transition-colors group">
                <td className="px-6 py-4 font-medium text-slate-200">{row.candidate_name}</td>
                <td className="px-6 py-4 text-slate-400">{row.position || '—'}</td>
                <td className="px-6 py-4 text-slate-400">{row.credential_type || '—'}</td>
                <td className="px-6 py-4">
                  <span className={`px-2 py-1 rounded-full text-[10px] font-bold uppercase ${
                    (row.status || '').toLowerCase() === 'active'
                      ? 'bg-green-500/10 text-green-500'
                      : 'bg-amber-500/10 text-amber-500'
                  }`}>
                    {row.status || 'unknown'}
                  </span>
                </td>
                <td className="px-6 py-4">
                  <button className="text-primary hover:text-primary/80 text-sm font-semibold transition-colors">View Details</button>
                </td>
              </tr>
            ))
          )}
        </tbody>
      </table>
    </div>
  );
};

export default PersonnelDatabase;
