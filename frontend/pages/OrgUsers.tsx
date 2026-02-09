import React, { useEffect, useMemo, useState } from 'react';
import { useApi } from '../lib/api';
import { useAuth } from '../context/AuthContext';
import { useNavigate } from 'react-router-dom';

type UserRow = {
  id: number;
  name: string;
  email: string;
  role: string;
  access_status?: string;
  credentials_count?: number;
};

const OrgUsers: React.FC = () => {
  const { request } = useApi();
  const { user } = useAuth();
  const navigate = useNavigate();
  const [users, setUsers] = useState<UserRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [role] = useState<'admin'>('admin');
  const [creating, setCreating] = useState(false);

  const [createdCredentials, setCreatedCredentials] = useState<null | {
    name: string;
    email: string;
    tempPassword: string;
  }>(null);

  const [isEditOpen, setIsEditOpen] = useState(false);
  const [editSearch, setEditSearch] = useState('');
  const [editUserId, setEditUserId] = useState('');
  const [editName, setEditName] = useState('');
  const [editEmail, setEditEmail] = useState('');
  const [editPassword, setEditPassword] = useState('');
  const [editPasswordConfirm, setEditPasswordConfirm] = useState('');
  const [savingEdit, setSavingEdit] = useState(false);

  const canManage = user?.role === 'org_super_admin';

  const visibleUsers = useMemo(() => {
    if (canManage) {
      return users.filter((u) => u.role === 'admin');
    }
    return users;
  }, [users, canManage]);

  const load = async () => {
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

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const createUser = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!canManage) return;

    try {
      setCreating(true);
      setError('');
      const res: any = await request('/admin/users', {
        method: 'POST',
        body: JSON.stringify({
          name,
          email,
          role,
        }),
      });

      if (res?.credentials?.email && res?.credentials?.temp_password) {
        setCreatedCredentials({
          name: res?.user?.name || name,
          email: res.credentials.email,
          tempPassword: res.credentials.temp_password,
        });
      }

      setName('');
      setEmail('');
      await load();
    } catch (e: any) {
      setError(e?.message || 'Failed to create user');
    } finally {
      setCreating(false);
    }
  };

  const teamSummary = useMemo(() => {
    const byRole = users.reduce<Record<string, number>>((acc, u) => {
      acc[u.role] = (acc[u.role] || 0) + 1;
      return acc;
    }, {});

    return {
      total: users.length,
      orgSuperAdmins: byRole['org_super_admin'] || 0,
      admins: byRole['admin'] || 0,
      candidates: byRole['candidate'] || 0,
    };
  }, [users]);

  const admins = useMemo(() => users.filter((u) => u.role === 'admin'), [users]);

  const filteredAdmins = useMemo(() => {
    const q = editSearch.trim().toLowerCase();
    if (!q) return admins;
    return admins.filter((a) => {
      const name = String(a?.name || '').toLowerCase();
      const email = String(a?.email || '').toLowerCase();
      return name.includes(q) || email.includes(q);
    });
  }, [admins, editSearch]);

  const currentEditUser = useMemo(() => {
    return admins.find((a) => String(a.id) === String(editUserId));
  }, [admins, editUserId]);

  const openEdit = (targetId?: number) => {
    if (!canManage) return;
    const idToEdit = targetId ? String(targetId) : (admins.length ? String(admins[0].id) : '');
    setEditUserId(idToEdit);
    const selected = admins.find((a) => String(a.id) === idToEdit);
    setEditName(selected?.name || '');
    setEditEmail(selected?.email || '');
    setEditPassword('');
    setEditPasswordConfirm('');
    setEditSearch('');
    setIsEditOpen(true);
  };

  const submitEdit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!canManage) return;
    if (!editUserId) return;

    try {
      setSavingEdit(true);
      setError('');
      const payload: any = {
        name: editName,
        email: editEmail,
      };
      if (editPassword) {
        payload.password = editPassword;
        payload.password_confirmation = editPasswordConfirm;
      }

      await request(`/admin/users/${encodeURIComponent(String(editUserId))}`, {
        method: 'PUT',
        body: JSON.stringify(payload),
      });

      setIsEditOpen(false);
      await load();
    } catch (e: any) {
      setError(e?.message || 'Failed to update user');
    } finally {
      setSavingEdit(false);
    }
  };

  useEffect(() => {
    if (!isEditOpen) return;
    if (!editUserId) return;
    const selected = admins.find((a) => String(a.id) === String(editUserId));
    if (!selected) return;
    setEditName(selected.name);
    setEditEmail(selected.email);
    setEditPassword('');
    setEditPasswordConfirm('');
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [editUserId, isEditOpen]);

  return (
    <div className="space-y-8">
      <div className="glass-dark rounded-[32px] border border-white/5 p-8">
        <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
          <div>
            <h2 className="font-display text-2xl text-white">Organization Users</h2>
            <p className="text-slate-500 text-sm">
              Create and manage your organization team. Use <span className="text-slate-300 font-semibold">Admin</span> for recruiters.
            </p>
          </div>

          <div className="grid grid-cols-3 gap-3 w-full md:w-auto">
            <div className="rounded-2xl border border-white/5 bg-white/[0.03] px-4 py-3">
              <div className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Total</div>
              <div className="text-white text-xl font-black mt-1">{teamSummary.total}</div>
            </div>
            <div className="rounded-2xl border border-white/5 bg-white/[0.03] px-4 py-3">
              <div className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Admins</div>
              <div className="text-white text-xl font-black mt-1">{teamSummary.admins}</div>
            </div>
            <div className="rounded-2xl border border-white/5 bg-white/[0.03] px-4 py-3">
              <div className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Org Admins</div>
              <div className="text-white text-xl font-black mt-1">{teamSummary.orgSuperAdmins}</div>
            </div>
          </div>
        </div>
      </div>

      {error && (
        <div className="glass-dark rounded-2xl border border-red-500/20 bg-red-500/10 p-4 text-sm text-red-300">
          {error}
        </div>
      )}

      {canManage && (
        <div className="glass-dark rounded-[32px] border border-white/5 p-8">
          <div className="flex items-start justify-between gap-6">
            <div>
              <h3 className="font-display text-xl text-white">Add Team Member</h3>
              <p className="text-slate-500 text-sm">Provision new admins (recruiters) for your org.</p>
            </div>
          </div>

          <form onSubmit={createUser} className="mt-6 space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <label className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Name</label>
                <input className="w-full bg-white/[0.03] border border-white/10 rounded-2xl px-4 py-3 text-white focus:outline-none focus:border-primary/50" value={name} onChange={(e) => setName(e.target.value)} required />
              </div>
              <div className="space-y-2">
                <label className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Email</label>
                <input type="email" className="w-full bg-white/[0.03] border border-white/10 rounded-2xl px-4 py-3 text-white focus:outline-none focus:border-primary/50" value={email} onChange={(e) => setEmail(e.target.value)} required />
              </div>
            </div>

            <button disabled={creating} className="w-full bg-white text-black font-black py-3 rounded-2xl hover:scale-[1.01] active:scale-[0.99] transition-all disabled:opacity-50">
              {creating ? 'Creating…' : 'Create User'}
            </button>
          </form>
        </div>
      )}

      <div className="glass-dark rounded-[32px] border border-white/5 overflow-hidden">
        <div className="px-6 py-4 border-b border-white/5 flex items-center justify-between">
          <h3 className="font-display text-xl text-white">Team</h3>
          <div className="flex items-center gap-3">
            {canManage && admins.length > 0 && (
              <button
                onClick={() => openEdit()}
                className="text-xs font-black uppercase tracking-widest text-slate-200 hover:text-white px-3 py-2 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all"
              >
                Edit Admin
              </button>
            )}
            <button onClick={load} className="text-xs font-black uppercase tracking-widest text-slate-400 hover:text-white">Refresh</button>
          </div>
        </div>

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
              <tr><td className="px-6 py-6 text-slate-500" colSpan={5}>Loading...</td></tr>
            ) : visibleUsers.length === 0 ? (
              <tr><td className="px-6 py-6 text-slate-500" colSpan={5}>No users</td></tr>
            ) : (
              visibleUsers.map((u) => (
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
                    <div className="flex items-center justify-end gap-2 flex-wrap">
                      {canManage && u.role === 'admin' && (
                        <button
                          type="button"
                          onClick={() => openEdit(u.id)}
                          className="px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-200 text-xs font-bold uppercase tracking-widest hover:bg-white/10 transition-all"
                        >
                          Edit
                        </button>
                      )}
                      {u.role === 'admin' && (
                        <>
                          <button
                            type="button"
                            onClick={() => navigate(`/dashboard/background_checks?user_id=${encodeURIComponent(String(u.id))}`)}
                            className="px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-200 text-xs font-bold uppercase tracking-widest hover:bg-white/10 transition-all"
                          >
                            Background Checks
                          </button>
                          <button
                            type="button"
                            onClick={() => navigate(`/dashboard/health_records?user_id=${encodeURIComponent(String(u.id))}`)}
                            className="px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-200 text-xs font-bold uppercase tracking-widest hover:bg-white/10 transition-all"
                          >
                            Health Records
                          </button>
                          <button
                            type="button"
                            onClick={() => navigate(`/dashboard/work_authorizations?user_id=${encodeURIComponent(String(u.id))}`)}
                            className="px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-200 text-xs font-bold uppercase tracking-widest hover:bg-white/10 transition-all"
                          >
                            Work Auth
                          </button>
                          <button
                            type="button"
                            onClick={() => navigate(`/dashboard/activity_logs?user_id=${encodeURIComponent(String(u.id))}`)}
                            className="px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-slate-200 text-xs font-bold uppercase tracking-widest hover:bg-white/10 transition-all"
                          >
                            Activity Logs
                          </button>
                        </>
                      )}
                    </div>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {!canManage && (
        <div className="text-xs text-slate-500">
          Only <span className="text-slate-300 font-semibold">org_super_admin</span> can create new users.
        </div>
      )}

      {isEditOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
          <div className="glass-dark w-full max-w-2xl rounded-3xl border border-white/10 shadow-2xl overflow-hidden">
            <div className="flex items-center justify-between p-6 border-b border-white/5">
              <h3 className="font-display text-xl text-white">Edit Admin (Recruiter)</h3>
              <button onClick={() => setIsEditOpen(false)} className="text-slate-400 hover:text-white transition-colors">
                ✕
              </button>
            </div>

            <form onSubmit={submitEdit} className="p-6 space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <label className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Search Admin</label>
                  <input
                    className="w-full bg-white/[0.03] border border-white/10 rounded-2xl px-4 py-3 text-white focus:outline-none focus:border-primary/50"
                    value={editSearch}
                    onChange={(e) => setEditSearch(e.target.value)}
                    placeholder="Search by name or email"
                  />
                </div>
                <div className="space-y-2">
                  <label className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Select Admin</label>
                  <select
                    className="w-full bg-white/[0.03] border border-white/10 rounded-2xl px-4 py-3 text-white focus:outline-none focus:border-primary/50"
                    value={editUserId}
                    onChange={(e) => setEditUserId(e.target.value)}
                  >
                    {filteredAdmins.map((a) => (
                      <option key={a.id} value={String(a.id)}>
                        {a.name} ({a.email})
                      </option>
                    ))}
                  </select>
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <label className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Name</label>
                  <input
                    className="w-full bg-white/[0.03] border border-white/10 rounded-2xl px-4 py-3 text-white focus:outline-none focus:border-primary/50"
                    value={editName}
                    onChange={(e) => setEditName(e.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <label className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Email</label>
                  <input
                    type="email"
                    className="w-full bg-white/[0.03] border border-white/10 rounded-2xl px-4 py-3 text-white focus:outline-none focus:border-primary/50"
                    value={editEmail}
                    onChange={(e) => setEditEmail(e.target.value)}
                    required
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <label className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">New Password (optional)</label>
                  <input
                    type="password"
                    className="w-full bg-white/[0.03] border border-white/10 rounded-2xl px-4 py-3 text-white focus:outline-none focus:border-primary/50"
                    value={editPassword}
                    onChange={(e) => setEditPassword(e.target.value)}
                  />
                </div>
                <div className="space-y-2">
                  <label className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Confirm</label>
                  <input
                    type="password"
                    className="w-full bg-white/[0.03] border border-white/10 rounded-2xl px-4 py-3 text-white focus:outline-none focus:border-primary/50"
                    value={editPasswordConfirm}
                    onChange={(e) => setEditPasswordConfirm(e.target.value)}
                    disabled={!editPassword}
                  />
                </div>
              </div>

              {currentEditUser && (
                <div className="text-xs text-slate-500">
                  Editing: <span className="text-slate-300 font-semibold">{currentEditUser.name}</span>
                </div>
              )}

              <div className="flex gap-3 pt-2">
                <button
                  type="button"
                  onClick={() => setIsEditOpen(false)}
                  className="flex-1 bg-white/5 border border-white/10 text-white font-black py-3 rounded-2xl hover:bg-white/10 transition-all"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={savingEdit}
                  className="flex-1 bg-white text-black font-black py-3 rounded-2xl hover:scale-[1.01] active:scale-[0.99] transition-all disabled:opacity-50"
                >
                  {savingEdit ? 'Saving…' : 'Save Changes'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {createdCredentials && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
          <div className="glass-dark w-full max-w-lg rounded-3xl border border-white/10 shadow-2xl overflow-hidden">
            <div className="flex items-center justify-between p-6 border-b border-white/5">
              <h3 className="font-display text-xl text-white">Admin Login Details</h3>
              <button onClick={() => setCreatedCredentials(null)} className="text-slate-400 hover:text-white transition-colors">
                ✕
              </button>
            </div>

            <div className="p-6 space-y-4">
              <div className="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                <div className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Email</div>
                <div className="mt-1 text-slate-100 font-semibold break-all">{createdCredentials.email}</div>
              </div>
              <div className="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                <div className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Temporary Password</div>
                <div className="mt-1 text-slate-100 font-mono break-all">{createdCredentials.tempPassword}</div>
              </div>

              <div className="text-xs text-slate-500">
                This password is shown once. The admin will be prompted to change it after login.
              </div>

              <div className="flex gap-3 pt-2">
                <button
                  type="button"
                  onClick={async () => {
                    await navigator.clipboard.writeText(
                      `Email: ${createdCredentials.email}\nPassword: ${createdCredentials.tempPassword}`
                    );
                  }}
                  className="flex-1 bg-white text-black font-black py-3 rounded-2xl hover:scale-[1.01] active:scale-[0.99] transition-all"
                >
                  Copy
                </button>
                <button
                  type="button"
                  onClick={() => setCreatedCredentials(null)}
                  className="flex-1 bg-white/5 border border-white/10 text-white font-black py-3 rounded-2xl hover:bg-white/10 transition-all"
                >
                  Done
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default OrgUsers;
