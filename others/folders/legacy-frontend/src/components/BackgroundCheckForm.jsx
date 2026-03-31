import { useState } from 'react';
import { X, Upload, Loader } from 'lucide-react';
import api from '../config/api';

const BackgroundCheckForm = ({ isOpen, onClose, onSuccess, backgroundCheck = null, candidateName = '' }) => {
  const [formData, setFormData] = useState({
    candidate_name: candidateName || backgroundCheck?.candidate_name || '',
    check_type: backgroundCheck?.check_type || 'criminal_record_check',
    issue_date: backgroundCheck?.issue_date || '',
    expiry_date: backgroundCheck?.expiry_date || '',
    verification_status: backgroundCheck?.verification_status || 'pending',
    notes: backgroundCheck?.notes || '',
  });
  const [documentFile, setDocumentFile] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  if (!isOpen) return null;

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleFileChange = (e) => {
    if (e.target.files && e.target.files[0]) {
      setDocumentFile(e.target.files[0]);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError(null);

    try {
      const fd = new FormData();
      Object.entries(formData).forEach(([key, value]) => {
        if (value) fd.append(key, value);
      });
      if (documentFile) {
        fd.append('document', documentFile);
      }

      if (backgroundCheck) {
        await api.post(`/background-checks/${backgroundCheck.id}?_method=PUT`, fd, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      } else {
        await api.post('/background-checks', fd, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      }

      onClose();
      setTimeout(() => {
        onSuccess();
      }, 100);
    } catch (err) {
      setError(
        err.response?.data?.message ||
        err.response?.data?.error ||
        err.message ||
        'Failed to save background check'
      );
      console.error('Error saving background check:', err);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div className="sticky top-0 bg-white border-b border-goodwill-border/50 p-4 flex items-center justify-between">
          <h2 className="text-lg font-bold text-goodwill-dark">
            {backgroundCheck ? 'Edit Background Check' : 'Add Background Check'}
          </h2>
          <button
            onClick={onClose}
            className="p-1 hover:bg-goodwill-light rounded transition-colors"
          >
            <X className="w-5 h-5 text-goodwill-text-muted" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          {error && (
            <div className="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
              {error}
            </div>
          )}

          <div>
            <label className="block text-sm font-semibold text-goodwill-dark mb-2">
              Candidate Name *
            </label>
            <input
              type="text"
              name="candidate_name"
              value={formData.candidate_name}
              onChange={handleChange}
              required
              className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-sm focus:ring-2 focus:ring-goodwill-primary focus:border-transparent"
            />
          </div>

          <div>
            <label className="block text-sm font-semibold text-goodwill-dark mb-2">
              Check Type *
            </label>
            <select
              name="check_type"
              value={formData.check_type}
              onChange={handleChange}
              required
              className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-sm focus:ring-2 focus:ring-goodwill-primary focus:border-transparent"
            >
              <option value="criminal_record_check">Criminal Record Check</option>
              <option value="vulnerable_sector_check">Vulnerable Sector Check</option>
              <option value="security_clearance">Security Clearance</option>
              <option value="other">Other</option>
            </select>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-semibold text-goodwill-dark mb-2">
                Issue Date
              </label>
              <input
                type="date"
                name="issue_date"
                value={formData.issue_date}
                onChange={handleChange}
                className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-sm focus:ring-2 focus:ring-goodwill-primary focus:border-transparent"
              />
            </div>

            <div>
              <label className="block text-sm font-semibold text-goodwill-dark mb-2">
                Expiry Date
              </label>
              <input
                type="date"
                name="expiry_date"
                value={formData.expiry_date}
                onChange={handleChange}
                min={formData.issue_date}
                className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-sm focus:ring-2 focus:ring-goodwill-primary focus:border-transparent"
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-semibold text-goodwill-dark mb-2">
              Verification Status
            </label>
            <select
              name="verification_status"
              value={formData.verification_status}
              onChange={handleChange}
              className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-sm focus:ring-2 focus:ring-goodwill-primary focus:border-transparent"
            >
              <option value="pending">Pending</option>
              <option value="verified">Verified</option>
              <option value="failed">Failed</option>
              <option value="expired">Expired</option>
            </select>
          </div>

          <div>
            <label className="block text-sm font-semibold text-goodwill-dark mb-2">
              Document
            </label>
            <div className="flex items-center gap-3">
              <label className="flex-1 px-4 py-2 border border-goodwill-border/50 rounded-lg text-sm cursor-pointer hover:bg-goodwill-light/50 transition-colors flex items-center justify-center gap-2">
                <Upload className="w-4 h-4 text-goodwill-primary" />
                {documentFile ? documentFile.name : 'Upload Document (PDF, DOC, DOCX, Images)'}
                <input
                  type="file"
                  onChange={handleFileChange}
                  accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                  className="hidden"
                />
              </label>
            </div>
            <p className="text-xs text-goodwill-text-muted mt-1">
              Max file size: 10MB
            </p>
          </div>

          <div>
            <label className="block text-sm font-semibold text-goodwill-dark mb-2">
              Notes
            </label>
            <textarea
              name="notes"
              value={formData.notes}
              onChange={handleChange}
              rows={3}
              className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-sm focus:ring-2 focus:ring-goodwill-primary focus:border-transparent"
            />
          </div>

          <div className="flex items-center justify-end gap-3 pt-4 border-t border-goodwill-border/50">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 text-sm font-medium text-goodwill-text-muted hover:text-goodwill-dark transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={loading}
              className="px-4 py-2 bg-goodwill-primary text-white rounded-lg text-sm font-medium hover:bg-goodwill-primary/90 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              {loading ? (
                <>
                  <Loader className="w-4 h-4 animate-spin" />
                  Saving...
                </>
              ) : (
                'Save Background Check'
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default BackgroundCheckForm;

