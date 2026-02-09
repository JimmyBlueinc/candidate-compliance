import React, { useEffect, useState } from 'react';
import { useApi } from '../lib/api';

type UserRow = {
  id: number;
  name: string;
  email: string;
  role: string;
  credentials_count?: number;
};

const AdminUsers: React.FC = () => {
  const { request } = useApi();
  const [users, setUsers] = useState<UserRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    const run = async () => {
      try {
        setLoading(true);
        setError('');
        const res: any = await request('/admin/users');
        setUsers(res?.users || []);
      } catch (e: any) {
        setUsers([]);
        setError(e?.message || 'Failed to load users');
      } finally {
        setLoading(false);
      }
    };

    run();
  }, [request]);

  return (
    <div className="space-y-6">
      <div className="glass-dark rounded-3xl border border-white/5 p-6">
        <h2 className="font-display text-2xl text-white">Admin Users</h2>
        <p className="text-slate-500 text-sm">Super admin user management.</p>
      </div>

      {error && (
        <div className="glass-dark rounded-2xl border border-red-500/20 bg-red-500/10 p-4 text-sm text-red-300">
          {error}
        </div>
      )}

      <div className="glass-dark rounded-3xl overflow-hidden border border-white/5">
        <table className="w-full text-left">
          <thead>
            <tr className="border-b border-white/5 bg-white/5">
              <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Name</th>
              <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Email</th>
              <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Role</th>
              <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Credentials</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-white/5">
            {loading ? (
              <tr><td className="px-6 py-6 text-slate-500" colSpan={4}>Loading...</td></tr>
            ) : users.length === 0 ? (
              <tr><td className="px-6 py-6 text-slate-500" colSpan={4}>No users</td></tr>
            ) : (
              users.map((u) => (
                <tr key={u.id} className="hover:bg-white/5 transition-colors">
                  <td className="px-6 py-4 font-medium text-slate-200">{u.name}</td>
                  <td className="px-6 py-4 text-slate-400">{u.email}</td>
                  <td className="px-6 py-4 text-slate-200">{u.role}</td>
                  <td className="px-6 py-4 text-slate-400">{u.credentials_count ?? '—'}</td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default AdminUsers;
