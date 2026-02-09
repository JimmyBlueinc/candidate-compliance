import React, { useState } from 'react';
import { X, Upload, Loader2 } from 'lucide-react';

interface CredentialFormModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
  initialData?: any;
  targetUserId?: string;
  targetEmail?: string;
  request: (endpoint: string, options?: RequestInit) => Promise<any>;
}

const CredentialFormModal: React.FC<CredentialFormModalProps> = ({
  isOpen,
  onClose,
  onSuccess,
  initialData,
  targetUserId,
  targetEmail,
  request,
}) => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [formData, setFormData] = useState({
    candidate_name: initialData?.candidate_name || '',
    position: initialData?.position || '',
    specialty: initialData?.specialty || '',
    credential_type: initialData?.credential_type || '',
    email: initialData?.email || targetEmail || '',
    issue_date: initialData?.issue_date || '',
    expiry_date: initialData?.expiry_date || '',
    status: initialData?.status || 'pending',
    province: initialData?.province || '',
  });
  const [file, setFile] = useState<File | null>(null);

  if (!isOpen) return null;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      const data = new FormData();
      Object.entries(formData).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          data.append(key, String(value) as string); // explicitly cast to string
        }
      });

      // When creating a credential from OrgUsers context, allow admins to attach it to that user.
      // Backend must enforce org scoping + role permissions.
      if (!initialData?.id && targetUserId) {
        data.append('user_id', String(targetUserId));
      }

      if (file) {
        data.append('document', file);
      }

      const isEdit = !!initialData?.id;
      const endpoint = isEdit ? `/credentials/${initialData.id}` : '/credentials';
      const method = isEdit ? 'POST' : 'POST'; // Laravel often requires POST with _method=PUT for FormData
      
      if (isEdit) {
        data.append('_method', 'PUT');
      }

      await request(endpoint, {
        method,
        body: data,
        // useApi will handle Authorization header, but we must NOT set Content-Type header manually for FormData
      });

      onSuccess();
      onClose();
    } catch (err: any) {
      setError(err.message || 'Failed to save credential');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div className="glass-dark w-full max-w-2xl rounded-3xl border border-white/10 shadow-2xl overflow-hidden animate-[fadeInUp_0.3s_ease-out]">
        <div className="flex items-center justify-between p-6 border-b border-white/5">
          <h3 className="font-display text-xl text-white">
            {initialData ? 'Edit Credential' : 'Add New Credential'}
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
            <Field label="Candidate Name" value={formData.candidate_name} onChange={(v) => setFormData({...formData, candidate_name: v})} required />
            <Field label="Email" type="email" value={formData.email} onChange={(v) => setFormData({...formData, email: v})} required />
            <Field label="Position" value={formData.position} onChange={(v) => setFormData({...formData, position: v})} required />
            <Field label="Specialty" value={formData.specialty} onChange={(v) => setFormData({...formData, specialty: v})} />
            <Field label="Credential Type" value={formData.credential_type} onChange={(v) => setFormData({...formData, credential_type: v})} required />
            <Field label="Province" value={formData.province} onChange={(v) => setFormData({...formData, province: v})} />
            <Field label="Issue Date" type="date" value={formData.issue_date} onChange={(v) => setFormData({...formData, issue_date: v})} required />
            <Field label="Expiry Date" type="date" value={formData.expiry_date} onChange={(v) => setFormData({...formData, expiry_date: v})} required />
          </div>

          <div className="space-y-2">
            <label className="text-xs font-bold uppercase tracking-widest text-slate-500">Document (PDF/DOC)</label>
            <div className="relative">
              <input
                type="file"
                onChange={(e) => setFile(e.target.files?.[0] || null)}
                className="hidden"
                id="file-upload"
                accept=".pdf,.doc,.docx"
              />
              <label
                htmlFor="file-upload"
                className="flex items-center justify-center gap-2 w-full py-4 border-2 border-dashed border-white/10 rounded-2xl cursor-pointer hover:border-primary/50 hover:bg-white/5 transition-all group"
              >
                <Upload size={20} className="text-slate-400 group-hover:text-primary transition-colors" />
                <span className="text-sm text-slate-400 group-hover:text-slate-200">
                  {file ? file.name : (initialData?.document_url ? 'Replace existing document' : 'Upload credential document')}
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
              {initialData ? 'Update Credential' : 'Create Credential'}
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

const Field: React.FC<{ label: string; value: string; onChange: (v: string) => void; type?: string; required?: boolean }> = ({
  label,
  value,
  onChange,
  type = 'text',
  required = false,
}) => (
  <div className="space-y-1.5">
    <label className="text-xs font-bold uppercase tracking-widest text-slate-500">{label}</label>
    <input
      type={type}
      value={value}
      onChange={(e) => onChange(e.target.value)}
      required={required}
      className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:outline-none focus:border-primary/50 transition-all"
    />
  </div>
);

export default CredentialFormModal;
