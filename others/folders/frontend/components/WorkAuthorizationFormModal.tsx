import React, { useEffect, useState } from 'react';
import { X, Upload, Loader2 } from 'lucide-react';

interface WorkAuthorizationFormModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
  initialData?: any;
  targetUserId?: string;
  targetUserName?: string;
  request: (endpoint: string, options?: RequestInit) => Promise<any>;
}

const WorkAuthorizationFormModal: React.FC<WorkAuthorizationFormModalProps> = ({
  isOpen,
  onClose,
  onSuccess,
  initialData,
  targetUserId,
  targetUserName,
  request,
}) => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [formData, setFormData] = useState({
    candidate_name: initialData?.candidate_name || targetUserName || '',
    authorization_type: initialData?.authorization_type || 'work_permit',
    issue_date: initialData?.issue_date || '',
    expiry_date: initialData?.expiry_date || '',
    status: initialData?.status || 'pending',
    notes: initialData?.notes || '',
  });
  const [file, setFile] = useState<File | null>(null);

  useEffect(() => {
    if (!isOpen) return;
    if (initialData?.id) return;
    if (!targetUserName) return;

    setFormData((prev) => ({
      ...prev,
      candidate_name: targetUserName,
    }));
  }, [isOpen, initialData?.id, targetUserName]);

  if (!isOpen) return null;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      const data = new FormData();
      Object.entries(formData).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          data.append(key, String(value));
        }
      });

      if (!initialData?.id && targetUserId) {
        data.append('user_id', String(targetUserId));
      }

      if (file) {
        data.append('document', file);
      }

      const isEdit = !!initialData?.id;
      const endpoint = isEdit ? `/work-authorizations/${initialData.id}` : '/work-authorizations';
      
      if (isEdit) {
        data.append('_method', 'PUT');
      }

      await request(endpoint, {
        method: 'POST',
        body: data,
      });

      onSuccess();
      onClose();
    } catch (err: any) {
      setError(err.message || 'Failed to save work authorization');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div className="glass-dark w-full max-w-2xl rounded-3xl border border-white/10 shadow-2xl overflow-hidden animate-[fadeInUp_0.3s_ease-out]">
        <div className="flex items-center justify-between p-6 border-b border-white/5">
          <h3 className="font-display text-xl text-white">
            {initialData ? 'Edit Work Authorization' : 'Add Work Authorization'}
          </h3>
          <button onClick={onClose} className="text-slate-400 hover:text-white transition-colors">
            <X size={20} />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          {error && (
            <div className="p-4 bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-xl">
              {error}
            </div>
          )}

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Field
              label="Admin"
              value={formData.candidate_name}
              onChange={(v) => setFormData({ ...formData, candidate_name: v })}
              required
              readOnly={!initialData?.id && !!targetUserName}
            />
            <div className="space-y-1.5">
              <label className="text-xs font-bold uppercase tracking-widest text-slate-500">Authorization Type</label>
              <select
                value={formData.authorization_type}
                onChange={(e) => setFormData({...formData, authorization_type: e.target.value})}
                className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-primary/50 transition-all appearance-none"
              >
                <option value="work_permit">Work Permit</option>
                <option value="study_permit">Study Permit</option>
                <option value="permanent_resident">Permanent Resident</option>
                <option value="citizen">Citizen</option>
                <option value="other">Other</option>
              </select>
            </div>
            <Field label="Issue Date" type="date" value={formData.issue_date} onChange={(v) => setFormData({...formData, issue_date: v})} />
            <Field label="Expiry Date" type="date" value={formData.expiry_date} onChange={(v) => setFormData({...formData, expiry_date: v})} />
            <div className="space-y-1.5">
              <label className="text-xs font-bold uppercase tracking-widest text-slate-500">Status</label>
              <select
                value={formData.status}
                onChange={(e) => setFormData({...formData, status: e.target.value})}
                className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-primary/50 transition-all appearance-none"
              >
                <option value="pending">Pending</option>
                <option value="verified">Verified</option>
                <option value="failed">Failed</option>
                <option value="expired">Expired</option>
              </select>
            </div>
          </div>

          <div className="space-y-1.5">
            <label className="text-xs font-bold uppercase tracking-widest text-slate-500">Notes</label>
            <textarea
              value={formData.notes}
              onChange={(e) => setFormData({...formData, notes: e.target.value})}
              className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white h-24 focus:outline-none focus:border-primary/50 transition-all"
            />
          </div>

          <div className="space-y-2">
            <label className="text-xs font-bold uppercase tracking-widest text-slate-500">Document (PDF/Image)</label>
            <div className="relative">
              <input
                type="file"
                onChange={(e) => setFile(e.target.files?.[0] || null)}
                className="hidden"
                id="wa-file-upload"
                accept=".pdf,.jpg,.jpeg,.png"
              />
              <label
                htmlFor="wa-file-upload"
                className="flex items-center justify-center gap-2 w-full py-4 border-2 border-dashed border-white/10 rounded-2xl cursor-pointer hover:border-primary/50 hover:bg-white/5 transition-all group"
              >
                <Upload size={20} className="text-slate-400 group-hover:text-primary transition-colors" />
                <span className="text-sm text-slate-400 group-hover:text-slate-200">
                  {file ? file.name : (initialData?.document_path ? 'Replace existing document' : 'Upload work auth document')}
                </span>
              </label>
            </div>
          </div>

          <div className="flex gap-3 pt-4">
            <button
              type="submit"
              disabled={loading}
              className="flex-1 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-primary/80 transition-all disabled:opacity-50 flex items-center justify-center gap-2"
            >
              {loading && <Loader2 size={18} className="animate-spin" />}
              {initialData ? 'Update Record' : 'Create Record'}
            </button>
            <button
              type="button"
              onClick={onClose}
              className="flex-1 py-3 rounded-xl bg-white/5 border border-white/10 text-slate-200 font-semibold hover:bg-white/10 transition-all"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

const Field: React.FC<{ label: string; value: string; onChange: (v: string) => void; type?: string; required?: boolean; readOnly?: boolean }> = ({
  label,
  value,
  onChange,
  type = 'text',
  required = false,
  readOnly = false,
}) => (
  <div className="space-y-1.5">
    <label className="text-xs font-bold uppercase tracking-widest text-slate-500">{label}</label>
    <input
      type={type}
      value={value}
      onChange={(e) => onChange(e.target.value)}
      required={required}
      readOnly={readOnly}
      className={`w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:outline-none focus:border-primary/50 transition-all ${readOnly ? 'opacity-80 cursor-not-allowed' : ''}`}
    />
  </div>
);

export default WorkAuthorizationFormModal;
