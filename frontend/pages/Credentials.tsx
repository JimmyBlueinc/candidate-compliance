import React, { useEffect, useMemo, useState, useCallback } from 'react';
import { Plus, Edit2, Trash2, FileText } from 'lucide-react';
import { useLocation } from 'react-router-dom';
import { useApi } from '../lib/api';
import { useAuth } from '../context/AuthContext';
import CredentialFormModal from '../components/CredentialFormModal';

type CredentialRow = {
  id: string | number;
  user_id?: number | string | null;
  candidate_name: string;
  position: string | null;
  specialty?: string | null;
  province?: string | null;
  credential_type: string | null;
  issue_date?: string | null;
  expiry_date?: string | null;
  status?: string | null;
  status_color?: string | null;
};

type CredentialsResponse = {
  data?: CredentialRow[];
  meta?: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
};

const Credentials: React.FC = () => {
  const { request, normalize } = useApi();
  const location = useLocation();
  const { user } = useAuth();
  const [rows, setRows] = useState<CredentialRow[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [page, setPage] = useState(1);
  const [searchName, setSearchName] = useState('');
  const [searchType, setSearchType] = useState('');
  const [searchEmail, setSearchEmail] = useState('');
  const [selectedUserId, setSelectedUserId] = useState<string>('');
  const [admins, setAdmins] = useState<any[]>([]);
  const [selectedAdminEmail, setSelectedAdminEmail] = useState<string>('');
  const [meta, setMeta] = useState<CredentialsResponse['meta'] | null>(null);

  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingRow, setEditingRow] = useState<CredentialRow | null>(null);

  useEffect(() => {
    const params = new URLSearchParams(location.search);
    const email = params.get('email') || '';
    const userId = params.get('user_id') || '';
    const openModal = params.get('open_modal') || '';
    const openFirst = params.get('open_first') || '';
    if (email && email !== searchEmail) {
      setPage(1);
      setSearchEmail(email);
    }
    if (userId && userId !== selectedUserId) {
      setSelectedUserId(userId);
    }

    // Store open flags in state-less way by opening modal here.
    if (openModal === '1') {
      if (openFirst === '1') {
        // If rows already loaded, pick first. Otherwise, open create now and later user can edit.
        setEditingRow((prev) => prev);
      }
      setIsModalOpen(true);
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

        // If no explicit user_id in URL, default the Credentials page to the first admin.
        const params = new URLSearchParams(location.search);
        const urlUserId = params.get('user_id') || '';
        if (!urlUserId && !selectedUserId && list.length > 0) {
          setSelectedUserId(String(list[0].id));
          setSelectedAdminEmail(String(list[0].email || ''));
          setPage(1);
        }
      } catch {
        setAdmins([]);
      }
    };

    run();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [user]);

  useEffect(() => {
    if (!admins.length) return;
    if (!selectedUserId) {
      setSelectedAdminEmail('');
      return;
    }
    const match = admins.find((a) => String(a.id) === String(selectedUserId));
    setSelectedAdminEmail(match?.email ? String(match.email) : '');
  }, [admins, selectedUserId]);

  useEffect(() => {
    const params = new URLSearchParams(location.search);
    const openModal = params.get('open_modal') || '';
    const openFirst = params.get('open_first') || '';
    if (openModal !== '1' || openFirst !== '1') return;
    if (!selectedUserId) return;
    if (!isModalOpen) return;

    const run = async () => {
      try {
        const res: any = await request(`/credentials?per_page=1&page=1&user_id=${encodeURIComponent(selectedUserId)}`);
        const data = normalize(res);
        if (Array.isArray(data) && data.length > 0) {
          setEditingRow(data[0]);
        } else {
          setEditingRow(null);
        }
      } catch {
        setEditingRow(null);
      }
    };

    run();
  }, [location.search, selectedUserId, isModalOpen, request, normalize]);

  const query = useMemo(() => {
    const params = new URLSearchParams();
    params.set('per_page', '10');
    params.set('page', String(page));
    if (searchName.trim()) params.set('name', searchName.trim());
    if (searchType.trim()) params.set('type', searchType.trim());
    if (searchEmail.trim()) params.set('email', searchEmail.trim());
    if (selectedUserId.trim()) params.set('user_id', selectedUserId.trim());
    return params.toString();
  }, [page, searchName, searchType, searchEmail, selectedUserId]);

  useEffect(() => {
    let active = true;

    const run = async () => {
      try {
        setLoading(true);
        setError('');
        const response: CredentialsResponse = await request(`/credentials?${query}`);
        const data = normalize(response);
        if (!active) return;
        setRows((prev) => (JSON.stringify(prev) === JSON.stringify(data) ? prev : data));
        setMeta(response.meta || null);
      } catch (e: any) {
        if (!active) return;
        setRows([]);
        setMeta(null);
        setError(e?.message || 'Failed to load credentials');
      } finally {
        if (!active) return;
        setLoading(false);
      }
    };

    run();
    return () => {
      active = false;
    };
  }, [query, request, normalize]);

  const handleAdd = () => {
    setEditingRow(null);
    setIsModalOpen(true);
  };

  const handleEdit = (row: CredentialRow) => {
    setEditingRow(row);
    setIsModalOpen(true);
  };

  const handleDelete = async (id: string | number) => {
    if (!window.confirm('Are you sure you want to delete this credential?')) return;
    try {
      await request(`/credentials/${id}`, { method: 'DELETE' });
      const response: CredentialsResponse = await request(`/credentials?${query}`);
      const data = normalize(response);
      setRows(data);
      setMeta(response.meta || null);
    } catch (e: any) {
      alert(e.message || 'Failed to delete credential');
    }
  };

  return (
    <div className="space-y-6">
      <div className="glass-dark rounded-3xl border border-white/5 p-6">
        <div className="flex flex-col md:flex-row md:items-end gap-4 justify-between">
          <div className="flex items-center justify-between w-full">
            <div>
              <h2 className="font-display text-2xl text-white">Credentials</h2>
              <p className="text-slate-500 text-sm">Search and review credentials in the system.</p>
            </div>
            <button
              onClick={handleAdd}
              className="flex items-center gap-2 px-4 py-2.5 bg-primary text-white rounded-xl font-semibold hover:bg-primary/80 transition-all shadow-lg shadow-primary/20"
            >
              <Plus size={18} />
              <span>Add Credential</span>
            </button>
          </div>

          <div className="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <input
              value={searchName}
              onChange={(e) => {
                setPage(1);
                setSearchName(e.target.value);
              }}
              placeholder="Search name"
              className="bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:outline-none focus:border-primary/50"
            />
            <input
              value={searchType}
              onChange={(e) => {
                setPage(1);
                setSearchType(e.target.value);
              }}
              placeholder="Search type"
              className="bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:outline-none focus:border-primary/50"
            />
            <input
              value={searchEmail}
              onChange={(e) => {
                setPage(1);
                setSearchEmail(e.target.value);
              }}
              placeholder="Filter email"
              className="bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:outline-none focus:border-primary/50"
            />
          </div>
        </div>
      </div>

      {selectedUserId && (
        <div className="glass-dark rounded-2xl border border-white/10 bg-white/[0.03] p-4 text-sm text-slate-300">
          Managing credentials for selected admin: <span className="font-semibold text-white">{selectedAdminEmail || `User #${selectedUserId}`}</span>
        </div>
      )}

      {admins.length > 0 && (
        <div className="glass-dark rounded-2xl border border-white/5 p-4 flex flex-col md:flex-row gap-3 md:items-center md:justify-between">
          <div className="text-sm text-slate-300">
            Viewing credentials for admin (recruiter)
          </div>
          <select
            value={selectedUserId}
            onChange={(e) => {
              setPage(1);
              setSelectedUserId(e.target.value);
            }}
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
              <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Candidate</th>
              <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Position</th>
              <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Type</th>
              <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Expiry</th>
              <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Status</th>
              <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-white/5">
            {loading ? (
              <tr>
                <td className="px-6 py-6 text-slate-500" colSpan={5}>Loading...</td>
              </tr>
            ) : rows.length === 0 ? (
              <tr>
                <td className="px-6 py-6 text-slate-500" colSpan={5}>No results</td>
              </tr>
            ) : (
              rows.map((row) => (
                <tr key={row.id} className="hover:bg-white/5 transition-colors">
                  <td className="px-6 py-4 font-medium text-slate-200">{row.candidate_name}</td>
                  <td className="px-6 py-4 text-slate-400">{row.position || '—'}</td>
                  <td className="px-6 py-4 text-slate-400">{row.credential_type || '—'}</td>
                  <td className="px-6 py-4 text-slate-400">{row.expiry_date || '—'}</td>
                  <td className="px-6 py-4 text-slate-200">{row.status || '—'}</td>
                  <td className="px-6 py-4 text-right">
                    <div className="flex items-center justify-end gap-2">
                      {row.id && (
                        <>
                          <button
                            onClick={() => handleEdit(row)}
                            className="p-2 text-slate-400 hover:text-white transition-colors"
                            title="Edit"
                          >
                            <Edit2 size={16} />
                          </button>
                          <button
                            onClick={() => handleDelete(row.id)}
                            className="p-2 text-slate-400 hover:text-red-400 transition-colors"
                            title="Delete"
                          >
                            <Trash2 size={16} />
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

      <CredentialFormModal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        onSuccess={async () => {
          try {
            const response: CredentialsResponse = await request(`/credentials?${query}`);
            const data = normalize(response);
            setRows(data);
            setMeta(response.meta || null);
          } catch {
            setRows([]);
            setMeta(null);
          }
        }}
        initialData={editingRow}
        targetUserId={selectedUserId || undefined}
        targetEmail={searchEmail || undefined}
        request={request}
      />
    </div>
  );
};

export default Credentials;
