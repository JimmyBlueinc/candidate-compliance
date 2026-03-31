import React, { useEffect, useState, useCallback, useMemo } from 'react';
import { Plus, Edit2, Trash2 } from 'lucide-react';
import { useLocation } from 'react-router-dom';
import { useApi } from '../lib/api';
import { useAuth } from '../context/AuthContext';
import BackgroundCheckFormModal from '../components/BackgroundCheckFormModal';

type Row = {
  id: string | number;
  candidate_name?: string | null;
  check_type?: string | null;
  issue_date?: string | null;
  expiry_date?: string | null;
  verification_status?: string | null;
  created_at?: string | null;
};

const BackgroundChecks: React.FC = () => {
  const { request, normalize } = useApi();
  const { user } = useAuth();
  const location = useLocation();
  const [rows, setRows] = useState<Row[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const [admins, setAdmins] = useState<any[]>([]);
  const [selectedAdminId, setSelectedAdminId] = useState('');
  const [adminSearch, setAdminSearch] = useState('');

  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingRow, setEditingRow] = useState<Row | null>(null);
  const [initialLoad, setInitialLoad] = useState(true);

  const query = useMemo(() => {
    const params = new URLSearchParams();
    if (selectedAdminId) params.set('user_id', selectedAdminId);
    return params.toString();
  }, [selectedAdminId]);

  const selectedAdmin = useMemo(() => {
    return admins.find((a) => String(a.id) === String(selectedAdminId));
  }, [admins, selectedAdminId]);

  const filteredAdmins = useMemo(() => {
    const q = adminSearch.trim().toLowerCase();
    if (!q) return admins;
    return admins.filter((a) => {
      const name = String(a?.name || '').toLowerCase();
      const email = String(a?.email || '').toLowerCase();
      return name.includes(q) || email.includes(q);
    });
  }, [admins, adminSearch]);

  const fetchRecords = useCallback(async () => {
    try {
      setLoading(true);
      setError('');
      const res: any = await request(`/background-checks${query ? `?${query}` : ''}`);
      const data = normalize(res);
      setRows(prev => JSON.stringify(prev) === JSON.stringify(data) ? prev : data);
    } catch (e: any) {
      setRows([]);
      setError(e?.message || 'Failed to load background checks');
    } finally {
      setLoading(false);
      setInitialLoad(false);
    }
  }, [request, normalize, query]);

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

  useEffect(() => {
    fetchRecords();
  }, [fetchRecords]);

  const handleAdd = () => {
    setEditingRow(null);
    setIsModalOpen(true);
  };

  const handleEdit = (row: Row) => {
    setEditingRow(row);
    setIsModalOpen(true);
  };

  const handleDelete = async (id: string | number) => {
    if (!window.confirm('Are you sure you want to delete this record?')) return;
    try {
      await request(`/background-checks/${id}`, { method: 'DELETE' });
      fetchRecords();
    } catch (e: any) {
      alert(e.message || 'Failed to delete record');
    }
  };

  return (
    <div className="space-y-6">
      <div className="glass-dark rounded-3xl border border-white/5 p-6 flex items-center justify-between">
        <div>
          <h2 className="font-display text-2xl text-white">Background Checks</h2>
          <p className="text-slate-500 text-sm">Review and manage background check records.</p>
        </div>
        <button
          onClick={handleAdd}
          className="flex items-center gap-2 px-4 py-2.5 bg-primary text-white rounded-xl font-semibold hover:bg-primary/80 transition-all shadow-lg shadow-primary/20"
        >
          <Plus size={18} />
          <span>Add Record</span>
        </button>
      </div>

      {admins.length > 0 && (
        <div className="glass-dark rounded-2xl border border-white/5 p-4 flex flex-col md:flex-row gap-3 md:items-center md:justify-between">
          <div className="text-sm text-slate-300">
            Viewing records for admin (recruiter)
          </div>
          <div className="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
            <input
              value={adminSearch}
              onChange={(e) => setAdminSearch(e.target.value)}
              placeholder="Search admin..."
              className="bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-primary/50 w-full sm:w-64"
            />
            <select
              value={selectedAdminId}
              onChange={(e) => setSelectedAdminId(e.target.value)}
              className="bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-primary/50 w-full sm:w-auto"
            >
              {filteredAdmins.map((a) => (
                <option key={a.id} value={String(a.id)}>
                  {a.name} ({a.email})
                </option>
              ))}
            </select>
          </div>
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
              <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Admin</th>
              <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Type</th>
              <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Status</th>
              <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">Expiry</th>
              <th className="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-white/5">
            {loading ? (
              <tr><td className="px-6 py-6 text-slate-500" colSpan={5}>Loading...</td></tr>
            ) : rows.length === 0 ? (
              <tr><td className="px-6 py-6 text-slate-500" colSpan={5}>No records</td></tr>
            ) : (
              rows.map((r) => (
                <tr key={r.id} className="hover:bg-white/5 transition-colors">
                  <td className="px-6 py-4 font-medium text-slate-200">{r.candidate_name || '—'}</td>
                  <td className="px-6 py-4 text-slate-400">{r.check_type || '—'}</td>
                  <td className="px-6 py-4 text-slate-200">{r.verification_status || '—'}</td>
                  <td className="px-6 py-4 text-slate-400">{r.expiry_date || '—'}</td>
                  <td className="px-6 py-4 text-right">
                    <div className="flex items-center justify-end gap-2">
                      <button
                        onClick={() => handleEdit(r)}
                        className="p-2 text-slate-400 hover:text-white transition-colors"
                        title="Edit"
                      >
                        <Edit2 size={16} />
                      </button>
                      <button
                        onClick={() => handleDelete(r.id)}
                        className="p-2 text-slate-400 hover:text-red-400 transition-colors"
                        title="Delete"
                      >
                        <Trash2 size={16} />
                      </button>
                    </div>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      <BackgroundCheckFormModal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        onSuccess={fetchRecords}
        initialData={editingRow}
        targetUserId={selectedAdminId || undefined}
        targetUserName={selectedAdmin?.name || ''}
        request={request}
      />
    </div>
  );
};

export default BackgroundChecks;
