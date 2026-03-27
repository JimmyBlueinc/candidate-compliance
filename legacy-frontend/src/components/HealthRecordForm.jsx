import { useState } from 'react';
import { X, Upload, Loader } from 'lucide-react';
import api from '../config/api';

const HealthRecordForm = ({ isOpen, onClose, onSuccess, healthRecord = null, candidateName = '' }) => {
  const [formData, setFormData] = useState({
    candidate_name: candidateName || healthRecord?.candidate_name || '',
    record_type: healthRecord?.record_type || 'immunization',
    vaccine_type: healthRecord?.vaccine_type || '',
    dose_number: healthRecord?.dose_number || '',
    administration_date: healthRecord?.administration_date || '',
    expiry_date: healthRecord?.expiry_date || '',
    provider_name: healthRecord?.provider_name || '',
    status: healthRecord?.status || 'pending',
    notes: healthRecord?.notes || '',
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

      if (healthRecord) {
        await api.post(`/health-records/${healthRecord.id}?_method=PUT`, fd, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      } else {
        await api.post('/health-records', fd, {
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
        'Failed to save health record'
      );
      console.error('Error saving health record:', err);
    } finally {
      setLoading(false);
    }
  };

  const vaccineTypes = [
    'COVID-19',
    'Influenza (Flu)',
    'Hepatitis B',
    'Measles, Mumps, Rubella (MMR)',
    'Varicella (Chickenpox)',
    'Tetanus, Diphtheria, Pertussis (Tdap)',
    'Tuberculosis (TB) Test',
    'Other',
  ];

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div className="sticky top-0 bg-white border-b border-goodwill-border/50 p-4 flex items-center justify-between">
          <h2 className="text-lg font-bold text-goodwill-dark">
            {healthRecord ? 'Edit Health Record' : 'Add Health Record'}
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
              Record Type *
            </label>
            <select
              name="record_type"
              value={formData.record_type}
              onChange={handleChange}
              required
              className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-sm focus:ring-2 focus:ring-goodwill-primary focus:border-transparent"
            >
              <option value="immunization">Immunization</option>
              <option value="tb_test">TB Test</option>
              <option value="health_screening">Health Screening</option>
              <option value="medical_clearance">Medical Clearance</option>
              <option value="fit_for_duty">Fit for Duty Assessment</option>
            </select>
          </div>

          {formData.record_type === 'immunization' && (
            <>
              <div>
                <label className="block text-sm font-semibold text-goodwill-dark mb-2">
                  Vaccine Type
                </label>
                <select
                  name="vaccine_type"
                  value={formData.vaccine_type}
                  onChange={handleChange}
                  className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-sm focus:ring-2 focus:ring-goodwill-primary focus:border-transparent"
                >
                  <option value="">Select Vaccine Type</option>
                  {vaccineTypes.map(type => (
                    <option key={type} value={type}>{type}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-sm font-semibold text-goodwill-dark mb-2">
                  Dose Number
                </label>
                <input
                  type="number"
                  name="dose_number"
                  value={formData.dose_number}
                  onChange={handleChange}
                  min="1"
                  className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-sm focus:ring-2 focus:ring-goodwill-primary focus:border-transparent"
                />
              </div>
            </>
          )}

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-semibold text-goodwill-dark mb-2">
                Administration Date
              </label>
              <input
                type="date"
                name="administration_date"
                value={formData.administration_date}
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
                min={formData.administration_date}
                className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-sm focus:ring-2 focus:ring-goodwill-primary focus:border-transparent"
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-semibold text-goodwill-dark mb-2">
              Provider Name (Clinic/Hospital)
            </label>
            <input
              type="text"
              name="provider_name"
              value={formData.provider_name}
              onChange={handleChange}
              className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-sm focus:ring-2 focus:ring-goodwill-primary focus:border-transparent"
            />
          </div>

          <div>
            <label className="block text-sm font-semibold text-goodwill-dark mb-2">
              Status
            </label>
            <select
              name="status"
              value={formData.status}
              onChange={handleChange}
              className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-sm focus:ring-2 focus:ring-goodwill-primary focus:border-transparent"
            >
              <option value="pending">Pending</option>
              <option value="up_to_date">Up to Date</option>
              <option value="expired">Expired</option>
              <option value="due">Due</option>
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
                'Save Health Record'
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default HealthRecordForm;

