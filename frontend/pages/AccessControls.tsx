
import React, { useEffect, useMemo, useState } from 'react';
import { useAuth } from '../context/AuthContext';
import { useApi } from '../lib/api';

const AccessControls: React.FC = () => {
  const { user } = useAuth();
  const { request } = useApi();

  const canManage = user?.role === 'org_super_admin' || user?.role === 'platform_admin';

  const [rows, setRows] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [tab, setTab] = useState<'admins' | 'candidates'>('admins');

  useEffect(() => {
    if (user?.role === 'org_super_admin' && tab !== 'admins') {
      setTab('admins');
    }
  }, [tab, user?.role]);

  const load = async () => {
    try {
      setLoading(true);
      setError('');
      const res: any = await request('/admin/users');
      setRows(res?.users || []);
    } catch (e: any) {
      setRows([]);
      setError(e?.message || 'Failed to load users');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const admins = useMemo(() => {
    const base = rows.filter((r) => r.role === 'admin' || r.role === 'org_super_admin');
    if (user?.role === 'org_super_admin') {
      return base.filter((r) => Number(r.id) !== Number(user.id));
    }
    return base;
  }, [rows, user?.id, user?.role]);
  const candidates = useMemo(() => rows.filter((r) => r.role === 'candidate'), [rows]);

  const setAccess = async (id: number, nextStatus: 'active' | 'suspended' | 'terminated') => {
    if (!canManage) return;

    if (user?.role === 'org_super_admin' && Number(id) === Number(user.id)) {
      setError('You cannot change your own access status.');
      return;
    }

    const label = nextStatus === 'active' ? 'activate' : nextStatus;
    if (!window.confirm(`Are you sure you want to ${label} this user?`)) return;

    try {
      await request(`/admin/users/${id}`, {
        method: 'PUT',
        body: JSON.stringify({ access_status: nextStatus }),
      });
      await load();
    } catch (e: any) {
      setError(e?.message || 'Failed to update user access');
    }
  };

  return (
    <div className="space-y-6">
      <div className="glass-dark p-8 rounded-[32px] border border-white/5">
        <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
          <div>
            <h2 className="font-display text-2xl text-white">Access Controls</h2>
            <p className="text-slate-500 text-sm">Activate, suspend, or terminate access for team members in your organization.</p>
          </div>
          <div className="text-right">
            <div className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Current role</div>
            <div className="text-slate-200 text-sm font-semibold">{user?.role || '—'}</div>
          </div>
        </div>
      </div>

      {error && (
        <div className="glass-dark rounded-2xl border border-red-500/20 bg-red-500/10 p-4 text-sm text-red-300">
          {error}
        </div>
      )}

      <div className="glass-dark rounded-[32px] border border-white/5 overflow-hidden">
        <div className="px-6 py-4 border-b border-white/5 flex items-center justify-between">
          <div className="flex gap-2">
            <button
              type="button"
              onClick={() => setTab('admins')}
              className={`px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest border ${tab === 'admins' ? 'bg-white text-black border-white' : 'bg-white/5 text-slate-200 border-white/10 hover:bg-white/10'}`}
            >
              Admins ({admins.length})
            </button>
            {user?.role !== 'org_super_admin' && (
              <button
                type="button"
                onClick={() => setTab('candidates')}
                className={`px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest border ${tab === 'candidates' ? 'bg-white text-black border-white' : 'bg-white/5 text-slate-200 border-white/10 hover:bg-white/10'}`}
              >
                Candidates ({candidates.length})
              </button>
            )}
          </div>

          <button onClick={load} className="text-xs font-black uppercase tracking-widest text-slate-400 hover:text-white">Refresh</button>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left">
            <thead>
              <tr className="border-b border-white/5 bg-white/5">
                <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Name</th>
                <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Email</th>
                <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Role</th>
                <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Access</th>
                <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-white/5">
              {loading ? (
                <tr><td className="px-6 py-6 text-slate-500" colSpan={5}>Loading…</td></tr>
              ) : (tab === 'admins' ? admins : candidates).length === 0 ? (
                <tr><td className="px-6 py-6 text-slate-500" colSpan={5}>No users</td></tr>
              ) : (
                (tab === 'admins' ? admins : candidates).map((u: any) => (
                  <tr key={u.id} className="hover:bg-white/5 transition-colors">
                    <td className="px-6 py-4 font-medium text-slate-200">{u.name}</td>
                    <td className="px-6 py-4 text-slate-400">{u.email}</td>
                    <td className="px-6 py-4 text-slate-200">{u.role}</td>
                    <td className="px-6 py-4">
                      <span className={`text-[10px] font-black uppercase tracking-widest px-2 py-1 rounded-full border ${
                        (u.access_status || 'active') === 'active'
                          ? 'bg-green-500/10 text-green-400 border-green-500/20'
                          : (u.access_status === 'suspended'
                            ? 'bg-yellow-500/10 text-yellow-300 border-yellow-500/20'
                            : 'bg-red-500/10 text-red-300 border-red-500/20')
                      }`}>
                        {u.access_status || 'active'}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-right">
                      {!canManage ? (
                        <span className="text-xs text-slate-500">No access</span>
                      ) : (
                        (() => {
                          const isSelf = Number(u.id) === Number(user?.id);
                          const disableSelf = user?.role === 'org_super_admin' && isSelf;
                          return (
                        <div className="flex items-center justify-end gap-2">
                          <button
                            type="button"
                            onClick={() => setAccess(u.id, 'active')}
                            disabled={disableSelf}
                            className="px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-200 text-xs font-bold uppercase tracking-widest hover:bg-white/10"
                          >
                            Activate
                          </button>
                          <button
                            type="button"
                            onClick={() => setAccess(u.id, 'suspended')}
                            disabled={disableSelf}
                            className="px-3 py-2 rounded-xl bg-yellow-500/10 border border-yellow-500/20 text-yellow-200 text-xs font-bold uppercase tracking-widest hover:bg-yellow-500/15"
                          >
                            Suspend
                          </button>
                          <button
                            type="button"
                            onClick={() => setAccess(u.id, 'terminated')}
                            disabled={disableSelf}
                            className="px-3 py-2 rounded-xl bg-red-500/10 border border-red-500/20 text-red-200 text-xs font-bold uppercase tracking-widest hover:bg-red-500/15"
                          >
                            Terminate
                          </button>
                        </div>
                          );
                        })()
                      )}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {!canManage && (
        <div className="text-xs text-slate-500">
          Only <span className="text-slate-300 font-semibold">org_super_admin</span> can change user access.
        </div>
      )}
    </div>
  );
};

export default AccessControls;
