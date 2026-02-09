import React, { useEffect, useState, useCallback, useMemo } from 'react';
import { Search, Filter, Download, Eye, Trash2 } from 'lucide-react';
import { useLocation } from 'react-router-dom';
import { useApi } from '../lib/api';
import { useAuth } from '../context/AuthContext';

type ActivityLog = {
  id: string | number;
  user_name?: string;
  action: string;
  entity_type?: string;
  entity_id?: string | number;
  old_values?: any;
  new_values?: any;
  ip_address?: string;
  created_at: string;
};

const ActivityLogs: React.FC = () => {
  const { request, normalize } = useApi();
  const { user } = useAuth();
  const location = useLocation();
  const [rows, setRows] = useState<ActivityLog[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [filter, setFilter] = useState('');
  const [meta, setMeta] = useState<any>(null);
  const [initialLoad, setInitialLoad] = useState(true);

  const [admins, setAdmins] = useState<any[]>([]);
  const [selectedAdminId, setSelectedAdminId] = useState('');

  const query = useMemo(() => {
    const params = new URLSearchParams();
    params.set('per_page', '20');
    params.set('page', String(page));
    if (search.trim()) params.set('search', search.trim());
    if (filter.trim()) params.set('action', filter.trim());
    if (selectedAdminId) params.set('user_id', selectedAdminId);
    return params.toString();
  }, [page, search, filter, selectedAdminId]);

  useEffect(() => {
    const params = new URLSearchParams(location.search);
    const userId = params.get('user_id') || '';
    if (userId && userId !== selectedAdminId) {
      setSelectedAdminId(userId);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [location.search]);

  useEffect(() => {
    const run = async () => {
      if (!user || !['org_super_admin', 'admin'].includes(user.role)) return;
      try {
        const res: any = await request('/admin/users');
        const list = (res?.users || []).filter((u: any) => u.role === 'admin');
        setAdmins(list);
        const params = new URLSearchParams(location.search);
        const urlUserId = params.get('user_id') || '';
        if (!urlUserId && !selectedAdminId && list.length) {
          setSelectedAdminId(String(list[0].id));
        }
      } catch {
        setAdmins([]);
      }
    };
    run();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const fetchLogs = useCallback(async () => {
    try {
      setLoading(true);
      setError('');
      const res: any = await request(`/activity-logs?${query}`);
      const data = normalize(res);
      setRows(prev => JSON.stringify(prev) === JSON.stringify(data) ? prev : data);
      setMeta(res.meta || null);
    } catch (e: any) {
      setRows([]);
      setMeta(null);
      setError(e?.message || 'Failed to load activity logs');
    } finally {
      setLoading(false);
      setInitialLoad(false);
    }
  }, [query, request, normalize]);

  useEffect(() => {
    fetchLogs();
  }, [fetchLogs]);

  const handleViewDetails = (log: ActivityLog) => {
    // TODO: Implement modal to show old_values vs new_values
    alert(`Action: ${log.action}\nEntity: ${log.entity_type} (${log.entity_id})`);
  };

  return (
    <div className="space-y-6">
      <div className="glass-dark rounded-3xl border border-white/5 p-6">
        <h2 className="font-display text-2xl text-white">Activity Logs</h2>
        <p className="text-slate-500 text-sm">Audit trail of system actions.</p>
      </div>

      {admins.length > 0 && (
        <div className="glass-dark rounded-2xl border border-white/5 p-4 flex flex-col md:flex-row gap-3 md:items-center md:justify-between">
          <div className="text-sm text-slate-300">
            Viewing logs for admin (recruiter)
          </div>
          <select
            value={selectedAdminId}
            onChange={(e) => setSelectedAdminId(e.target.value)}
            className="bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-primary/50"
          >
            {admins.map((a) => (
              <option key={a.id} value={String(a.id)}>
                {a.name} ({a.email})
              </option>
            ))}
          </select>
        </div>
      )}

      {error && (
        <div className="glass-dark rounded-2xl border border-red-500/20 bg-red-500/10 p-4 text-sm text-red-300">
          {error}
        </div>
      )}

      <div className="glass-dark rounded-3xl overflow-hidden border border-white/5">
        <table className="w-full text-left">
          <thead>
            <tr className="border-b border-white/5 bg-white/5">
              <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">When</th>
              <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">User</th>
              <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Action</th>
              <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Entity</th>
              <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Description</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-white/5">
            {loading ? (
              <tr><td className="px-6 py-6 text-slate-500" colSpan={5}>Loading...</td></tr>
            ) : rows.length === 0 ? (
              <tr><td className="px-6 py-6 text-slate-500" colSpan={5}>No logs</td></tr>
            ) : (
              rows.map((r, idx) => (
                <tr key={String(r.id ?? idx)} className="hover:bg-white/5 transition-colors">
                  <td className="px-6 py-4 text-slate-400">{r.created_at || '—'}</td>
                  <td className="px-6 py-4 text-slate-200">{(r as any).user?.name || (r as any).user?.email || '—'}</td>
                  <td className="px-6 py-4 text-slate-200">{r.action || '—'}</td>
                  <td className="px-6 py-4 text-slate-400">{r.entity_name || r.entity || '—'}</td>
                  <td className="px-6 py-4 text-slate-400">{r.description || '—'}</td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {meta && (
        <div className="flex items-center justify-between text-sm text-slate-500">
          <div>
            Page {meta.current_page} of {meta.last_page} • {meta.total} total
          </div>
          <div className="flex gap-2">
            <button
              type="button"
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              disabled={meta.current_page <= 1}
              className="px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-300 disabled:opacity-40"
            >
              Prev
            </button>
            <button
              type="button"
              onClick={() => setPage((p) => Math.min(meta.last_page, p + 1))}
              disabled={meta.current_page >= meta.last_page}
              className="px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-300 disabled:opacity-40"
            >
              Next
            </button>
          </div>
        </div>
      )}
    </div>
  );
};

export default ActivityLogs;
