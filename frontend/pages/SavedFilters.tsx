import React, { useEffect, useState, useCallback } from 'react';
import { Plus, Trash2, Search, Filter as FilterIcon } from 'lucide-react';
import { useApi } from '../lib/api';

type SavedFilter = {
  id: string | number;
  name: string;
  criteria: any;
  created_at: string;
};

const SavedFilters: React.FC = () => {
  const { request, normalize } = useApi();
  const [rows, setRows] = useState<SavedFilter[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [initialLoad, setInitialLoad] = useState(true);

  const fetchFilters = useCallback(async () => {
    try {
      setLoading(true);
      setError('');
      const res: any = await request('/filters');
      const data = normalize(res);
      setRows(prev => JSON.stringify(prev) === JSON.stringify(data) ? prev : data);
    } catch (e: any) {
      setRows([]);
      setError(e?.message || 'Failed to load filters');
    } finally {
      setLoading(false);
    }
  }, [request, normalize]);

  useEffect(() => {
    if (initialLoad) {
      fetchFilters();
      setInitialLoad(false);
    }
  }, [initialLoad]);

  useEffect(() => {
    if (!initialLoad) {
      fetchFilters();
    }
  }, [fetchFilters]);

  const handleDelete = async (id: string | number) => {
    if (!window.confirm('Are you sure you want to delete this filter?')) return;
    try {
      await request(`/filters/${id}`, { method: 'DELETE' });
      fetchFilters();
    } catch (e: any) {
      alert(e.message || 'Failed to delete filter');
    }
  };

  return (
    <div className="space-y-6">
      <div className="glass-dark rounded-3xl border border-white/5 p-6 flex items-center justify-between">
        <div>
          <h2 className="font-display text-2xl text-white">Saved Filters</h2>
          <p className="text-slate-500 text-sm">Reusable search criteria for advanced credentialing reports.</p>
        </div>
        <button
          disabled
          className="flex items-center gap-2 px-4 py-2.5 bg-primary/50 text-white rounded-xl font-semibold cursor-not-allowed opacity-50"
        >
          <Plus size={18} />
          <span>New Filter</span>
        </button>
      </div>

      {error && (
        <div className="glass-dark rounded-2xl border border-red-500/20 bg-red-500/10 p-4 text-sm text-red-300">
          {error}
        </div>
      )}

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {loading ? (
          <p className="text-slate-500 p-6">Loading filters...</p>
        ) : rows.length === 0 ? (
          <p className="text-slate-500 p-6">No saved filters found.</p>
        ) : (
          rows.map((f) => (
            <div key={f.id} className="glass-dark rounded-3xl border border-white/5 p-6 hover:border-primary/20 transition-all flex items-center justify-between group">
              <div className="flex items-center gap-4">
                <div className="p-3 bg-accent-blue/10 rounded-2xl text-accent-blue">
                  <FilterIcon size={20} />
                </div>
                <div>
                  <h3 className="text-white font-semibold">{f.name}</h3>
                  <p className="text-slate-500 text-xs font-bold uppercase tracking-widest mt-1">
                    Saved {new Date(f.created_at).toLocaleDateString()}
                  </p>
                </div>
              </div>
              <div className="flex items-center gap-2">
                <button className="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-white text-xs font-bold uppercase tracking-widest transition-all">Apply</button>
                <button onClick={() => handleDelete(f.id)} className="p-2 text-slate-400 hover:text-red-400 transition-colors">
                  <Trash2 size={18} />
                </button>
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  );
};

export default SavedFilters;
