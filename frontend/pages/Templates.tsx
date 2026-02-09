import React, { useEffect, useState, useCallback } from 'react';
import { Plus, Edit2, Trash2 } from 'lucide-react';
import { useApi } from '../lib/api';

type Template = {
  id: string | number;
  name: string;
  type: string;
  content: string;
  created_at: string;
};

const Templates: React.FC = () => {
  const { request, normalize } = useApi();
  const [rows, setRows] = useState<Template[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [initialLoad, setInitialLoad] = useState(true);

  const fetchTemplates = useCallback(async () => {
    try {
      setLoading(true);
      setError('');
      const res: any = await request('/templates');
      const data = normalize(res);
      setRows(prev => JSON.stringify(prev) === JSON.stringify(data) ? prev : data);
    } catch (e: any) {
      setRows([]);
      setError(e?.message || 'Failed to load templates');
    } finally {
      setLoading(false);
      setInitialLoad(false);
    }
  }, [request, normalize]);

  useEffect(() => {
    fetchTemplates();
  }, [fetchTemplates]);

  const handleDelete = async (id: string | number) => {
    if (!window.confirm('Are you sure you want to delete this template?')) return;
    try {
      await request(`/templates/${id}`, { method: 'DELETE' });
      fetchTemplates();
    } catch (e: any) {
      alert(e.message || 'Failed to delete template');
    }
  };

  return (
    <div className="space-y-6">
      <div className="glass-dark rounded-3xl border border-white/5 p-6 flex items-center justify-between">
        <div>
          <h2 className="font-display text-2xl text-white">Document Templates</h2>
          <p className="text-slate-500 text-sm">Manage standardized credentialing document templates.</p>
        </div>
        <button
          disabled
          className="flex items-center gap-2 px-4 py-2.5 bg-primary/50 text-white rounded-xl font-semibold cursor-not-allowed transition-all opacity-50"
        >
          <Plus size={18} />
          <span>New Template</span>
        </button>
      </div>

      {error && (
        <div className="glass-dark rounded-2xl border border-red-500/20 bg-red-500/10 p-4 text-sm text-red-300">
          {error}
        </div>
      )}

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {loading ? (
          <p className="text-slate-500 p-6">Loading templates...</p>
        ) : rows.length === 0 ? (
          <p className="text-slate-500 p-6">No templates found.</p>
        ) : (
          rows.map((t) => (
            <div key={t.id} className="glass-dark rounded-3xl border border-white/5 p-6 hover:border-primary/20 transition-all group relative">
              <div className="flex justify-between items-start mb-4">
                <div className="p-3 bg-primary/10 rounded-2xl text-primary">
                  <span className="material-symbols-outlined">description</span>
                </div>
                <div className="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button onClick={() => handleDelete(t.id)} className="p-2 text-slate-400 hover:text-red-400 transition-colors">
                    <Trash2 size={16} />
                  </button>
                </div>
              </div>
              <h3 className="text-white font-semibold text-lg mb-1">{t.name}</h3>
              <p className="text-slate-500 text-xs uppercase font-bold tracking-widest mb-4">{t.type}</p>
              <div className="text-slate-400 text-sm line-clamp-3 mb-6 bg-white/5 p-3 rounded-xl italic">
                {t.content || 'No preview available'}
              </div>
              <div className="flex items-center justify-between pt-4 border-t border-white/5">
                <span className="text-[10px] text-slate-500 font-bold uppercase">Created {new Date(t.created_at).toLocaleDateString()}</span>
                <button className="text-primary text-xs font-bold uppercase tracking-widest hover:text-white transition-colors">Edit Template</button>
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  );
};

export default Templates;
