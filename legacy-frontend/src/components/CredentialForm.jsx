import { useState, useEffect } from 'react';
import { X } from 'lucide-react';
import api from '../config/api';

const CredentialForm = ({ isOpen, onClose, credential, onSuccess, defaultEmail, defaultCandidateName }) => {
  const [formData, setFormData] = useState({
    candidate_name: defaultCandidateName || '',
    position: '',
    specialty: '',
    credential_type: '',
    issue_date: '',
    expiry_date: '',
    email: defaultEmail || '',
    province: '',
    status: '',
  });
  const [documentFile, setDocumentFile] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (credential) {
      setFormData({
        candidate_name: credential.candidate_name || '',
        position: credential.position || '',
        specialty: credential.specialty || '',
        credential_type: credential.credential_type || '',
        issue_date: credential.issue_date || '',
        expiry_date: credential.expiry_date || '',
        email: credential.email || '',
        province: credential.province || '',
        status: credential.status || '',
      });
      setDocumentFile(null);
    } else {
      setFormData({
        candidate_name: defaultCandidateName || '',
        position: '',
        specialty: '',
        credential_type: '',
        issue_date: '',
        expiry_date: '',
        email: defaultEmail || '',
        province: '',
        status: '',
      });
      setDocumentFile(null);
    }
    setError(null);
  }, [credential, isOpen]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    setError(null);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError(null);

    try {
      const fd = new FormData();
      Object.entries(formData).forEach(([k, v]) => fd.append(k, v ?? ''));
      if (documentFile) {
        fd.append('document', documentFile);
      }

      if (credential) {
        // Update existing credential
        await api.post(`/credentials/${credential.id}?_method=PUT`, fd, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      } else {
        // Create new credential
        await api.post('/credentials', fd, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
      }
      // Close the form first
      onClose();
      // Then trigger success callback to refresh the list
      // Use setTimeout to ensure the form closes before refresh
      setTimeout(() => {
        onSuccess();
      }, 100);
    } catch (err) {
      setError(
        err.response?.data?.message ||
          err.response?.data?.error ||
          err.message ||
          'Failed to save credential'
      );
      console.error('Error saving credential:', err);
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50">
      <div 
        className="absolute inset-0 bg-black/40 backdrop-blur-sm" 
        onClick={(e) => {
          if (!loading) {
            e.preventDefault();
            e.stopPropagation();
            // Reset form data
            setFormData({
              candidate_name: '',
              position: '',
              specialty: '',
              credential_type: '',
              issue_date: '',
              expiry_date: '',
              email: '',
              province: '',
              status: '',
            });
            setDocumentFile(null);
            setError(null);
            onClose();
          }
        }} 
      />
      <div className="relative h-full w-full flex items-center justify-center p-4">
        <div 
          className="bg-goodwill-light/95 border border-goodwill-border rounded-lg shadow-2xl max-w-lg w-full mx-auto"
          onClick={(e) => e.stopPropagation()}
        >
          <div className="relative overflow-hidden rounded-t-lg">
            <div className="absolute inset-0 bg-gradient-to-r from-blue-600 to-indigo-600 opacity-10" />
            <div className="px-3 pt-2.5 pb-2 flex items-start justify-between">
              <div>
                <h2 className="text-base font-bold text-goodwill-dark tracking-tight">
                  {credential ? 'Edit Credential' : 'Add New Credential'}
                </h2>
                <p className="text-xs text-goodwill-text-muted mt-0.5">
                  Provide candidate and credential details.
                </p>
              </div>
              <button
                type="button"
                onClick={(e) => {
                  e.preventDefault();
                  e.stopPropagation();
                  // Reset form data
                  setFormData({
                    candidate_name: '',
                    position: '',
                    credential_type: '',
                    issue_date: '',
                    expiry_date: '',
                    email: '',
                    status: '',
                  });
                  setDocumentFile(null);
                  setError(null);
                  onClose();
                }}
                className="inline-flex items-center justify-center h-6 w-6 rounded-lg border border-goodwill-border text-goodwill-text hover:bg-goodwill-light transition-colors"
                disabled={loading}
                aria-label="Close"
              >
                <X className="w-3.5 h-3.5 text-goodwill-text" strokeWidth={2.5} />
              </button>
            </div>
          </div>

          <form onSubmit={handleSubmit} className="p-3">
          {error && (
            <div className="mb-2 p-2 text-xs bg-goodwill-secondary/10 border border-goodwill-secondary/30 text-goodwill-dark rounded">
              {error}
            </div>
          )}

            {/* Candidate */}
            <h3 className="text-xs font-semibold text-goodwill-text-muted uppercase tracking-wider mb-2">Candidate</h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-2 mb-3">
            <div>
              <label className="block text-xs font-medium text-goodwill-dark mb-0.5">
                Candidate Name *
              </label>
              <input
                type="text"
                name="candidate_name"
                value={formData.candidate_name}
                onChange={handleChange}
                required
                  className="w-full px-2 py-1.5 text-sm rounded-lg border border-goodwill-border bg-white text-goodwill-dark focus:ring-2 focus:ring-goodwill-primary focus:border-goodwill-primary"
              />
            </div>

            <div>
              <label className="block text-xs font-medium text-goodwill-dark mb-0.5 dark:text-white">Position *</label>
              <select
                name="position"
                value={formData.position}
                onChange={handleChange}
                required
                className="w-full px-2 py-1.5 text-sm rounded-lg border border-goodwill-border bg-white text-goodwill-dark focus:ring-2 focus:ring-goodwill-primary focus:border-goodwill-primary dark:bg-gray-800 dark:border-gray-700 dark:text-white"
              >
                <option value="">Select Position</option>
                <optgroup label="Nursing">
                  <option value="Registered Nurse (RN)">Registered Nurse (RN)</option>
                  <option value="Licensed Practical Nurse (LPN)">Licensed Practical Nurse (LPN)</option>
                  <option value="Registered Psychiatric Nurse (RPN)">Registered Psychiatric Nurse (RPN)</option>
                  <option value="Nurse Practitioner (NP)">Nurse Practitioner (NP)</option>
                </optgroup>
                <optgroup label="Support Staff">
                  <option value="Personal Support Worker (PSW)">Personal Support Worker (PSW)</option>
                  <option value="Health Care Aide (HCA)">Health Care Aide (HCA)</option>
                  <option value="Patient Care Assistant (PCA)">Patient Care Assistant (PCA)</option>
                </optgroup>
                <optgroup label="Allied Health">
                  <option value="Respiratory Therapist">Respiratory Therapist</option>
                  <option value="Occupational Therapist">Occupational Therapist</option>
                  <option value="Physiotherapist">Physiotherapist</option>
                  <option value="Medical Laboratory Technologist">Medical Laboratory Technologist</option>
                  <option value="Radiology Technologist">Radiology Technologist</option>
                  <option value="Pharmacist">Pharmacist</option>
                  <option value="Social Worker">Social Worker</option>
                </optgroup>
                <optgroup label="Other">
                  <option value="Physician">Physician</option>
                  <option value="Travel Nurse">Travel Nurse</option>
                  <option value="Other">Other</option>
                </optgroup>
              </select>
            </div>

            <div>
              <label className="block text-xs font-medium text-goodwill-dark mb-0.5 dark:text-white">
                Credential Type *
              </label>
              <select
                name="credential_type"
                value={formData.credential_type}
                onChange={handleChange}
                required
                className="w-full px-2 py-1.5 text-sm rounded-lg border border-goodwill-border bg-white text-goodwill-dark focus:ring-2 focus:ring-goodwill-primary focus:border-goodwill-primary dark:bg-gray-800 dark:border-gray-700 dark:text-white"
              >
                <option value="">Select Credential Type</option>
                <optgroup label="Licenses & Registrations">
                  <option value="Provincial Nursing License">Provincial Nursing License</option>
                  <option value="Provincial LPN License">Provincial LPN License</option>
                  <option value="Provincial RPN License">Provincial RPN License</option>
                  <option value="Provincial NP License">Provincial NP License</option>
                  <option value="Professional Registration">Professional Registration</option>
                </optgroup>
                <optgroup label="Certifications">
                  <option value="CPR Certification">CPR Certification</option>
                  <option value="ACLS Certification">ACLS Certification</option>
                  <option value="PALS Certification">PALS Certification</option>
                  <option value="BLS Certification">BLS Certification</option>
                  <option value="Specialty Certification">Specialty Certification</option>
                </optgroup>
                <optgroup label="Clearances & Checks">
                  <option value="Criminal Background Check">Criminal Background Check</option>
                  <option value="Vulnerable Sector Check">Vulnerable Sector Check</option>
                  <option value="Child Abuse Registry Check">Child Abuse Registry Check</option>
                  <option value="Adult Abuse Registry Check">Adult Abuse Registry Check</option>
                </optgroup>
                <optgroup label="Medical & Health">
                  <option value="Medical Clearance">Medical Clearance</option>
                  <option value="Immunization Record">Immunization Record</option>
                  <option value="TB Test">TB Test</option>
                  <option value="Drug Test">Drug Test</option>
                </optgroup>
                <optgroup label="Verifications">
                  <option value="Education Verification">Education Verification</option>
                  <option value="Employment Verification">Employment Verification</option>
                  <option value="Reference Check">Reference Check</option>
                </optgroup>
                <optgroup label="Other">
                  <option value="Work Permit">Work Permit</option>
                  <option value="Professional Liability Insurance">Professional Liability Insurance</option>
                  <option value="Other">Other</option>
                </optgroup>
              </select>
            </div>

            <div>
              <label className="block text-xs font-medium text-goodwill-dark mb-0.5 dark:text-white">Email *</label>
              <input
                type="email"
                name="email"
                value={formData.email}
                onChange={handleChange}
                required
                className="w-full px-2 py-1.5 text-sm rounded-lg border border-goodwill-border bg-white text-goodwill-dark focus:ring-2 focus:ring-goodwill-primary focus:border-goodwill-primary dark:bg-gray-800 dark:border-gray-700 dark:text-white"
              />
            </div>

            <div>
              <label className="block text-xs font-medium text-goodwill-dark mb-0.5 dark:text-white">Specialty</label>
              <select
                name="specialty"
                value={formData.specialty}
                onChange={handleChange}
                className="w-full px-2 py-1.5 text-sm rounded-lg border border-goodwill-border bg-white text-goodwill-dark focus:ring-2 focus:ring-goodwill-primary focus:border-goodwill-primary dark:bg-gray-800 dark:border-gray-700 dark:text-white"
              >
                <option value="">Select Specialty (Optional)</option>
                <option value="Medical-Surgical">Medical-Surgical</option>
                <option value="Intensive Care Unit (ICU)">Intensive Care Unit (ICU)</option>
                <option value="Emergency">Emergency</option>
                <option value="Long Term Care (LTC)">Long Term Care (LTC)</option>
                <option value="Cardiovascular">Cardiovascular</option>
                <option value="Pediatrics">Pediatrics</option>
                <option value="Mental Health">Mental Health</option>
                <option value="Operating Room">Operating Room</option>
                <option value="Labor & Delivery">Labor & Delivery</option>
                <option value="Oncology">Oncology</option>
                <option value="Critical Care">Critical Care</option>
                <option value="Rehabilitation">Rehabilitation</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <div>
              <label className="block text-xs font-medium text-goodwill-dark mb-0.5 dark:text-white">Province</label>
              <select
                name="province"
                value={formData.province}
                onChange={handleChange}
                className="w-full px-2 py-1.5 text-sm rounded-lg border border-goodwill-border bg-white text-goodwill-dark focus:ring-2 focus:ring-goodwill-primary focus:border-goodwill-primary dark:bg-gray-800 dark:border-gray-700 dark:text-white"
              >
                <option value="">Select Province (Optional)</option>
                <option value="Alberta">Alberta</option>
                <option value="Saskatchewan">Saskatchewan</option>
                <option value="Manitoba">Manitoba</option>
                <option value="Nova Scotia">Nova Scotia</option>
                <option value="Prince Edward Island">Prince Edward Island</option>
                <option value="New Brunswick">New Brunswick</option>
                <option value="Newfoundland & Labrador">Newfoundland & Labrador</option>
                <option value="Ontario">Ontario</option>
                <option value="British Columbia">British Columbia</option>
                <option value="Quebec">Quebec</option>
                <option value="Yukon">Yukon</option>
                <option value="Northwest Territories">Northwest Territories</option>
                <option value="Nunavut">Nunavut</option>
              </select>
            </div>
            </div>

            {/* Dates & Status */}
            <h3 className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Dates & Status</h3>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-2 mb-3">
              <div>
              <label className="block text-xs font-medium text-goodwill-dark mb-0.5">Issue Date *</label>
              <input
                type="date"
                name="issue_date"
                value={formData.issue_date}
                onChange={handleChange}
                required
                  className="w-full px-2 py-1.5 text-sm rounded-lg border border-goodwill-border bg-white text-goodwill-dark focus:ring-2 focus:ring-goodwill-primary focus:border-goodwill-primary"
              />
            </div>

            <div>
              <label className="block text-xs font-medium text-goodwill-dark mb-0.5">Expiry Date *</label>
              <input
                type="date"
                name="expiry_date"
                value={formData.expiry_date}
                onChange={handleChange}
                required
                min={formData.issue_date}
                  className="w-full px-2 py-1.5 text-sm rounded-lg border border-goodwill-border bg-white text-goodwill-dark focus:ring-2 focus:ring-goodwill-primary focus:border-goodwill-primary"
              />
            </div>

            <div>
              <label className="block text-xs font-medium text-goodwill-dark mb-0.5">
                Status <span className="text-xs text-goodwill-text-muted">(Optional)</span>
              </label>
              <select
                name="status"
                value={formData.status}
                onChange={handleChange}
                  className="w-full px-2 py-1.5 text-sm rounded-lg border border-goodwill-border bg-white text-goodwill-dark focus:ring-2 focus:ring-goodwill-primary focus:border-goodwill-primary"
              >
                <option value="">Auto-calculate</option>
                <option value="active">Active</option>
                <option value="expiring_soon">Expiring Soon</option>
                <option value="expired">Expired</option>
                <option value="pending">Pending</option>
              </select>
            </div>
          </div>

            {/* Document */}
            <h3 className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Document</h3>
            <div className="rounded-lg border border-dashed border-gray-300 p-2 bg-gray-50 mb-3">
              <label className="block text-xs font-medium text-gray-700 mb-1">
                Document (PDF/DOC, optional)
              </label>
              <input
                type="file"
                accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                onChange={(e) => setDocumentFile(e.target.files?.[0] || null)}
                className="w-full px-2 py-1.5 text-xs rounded-lg border border-goodwill-border bg-white focus:ring-2 focus:ring-goodwill-primary focus:border-goodwill-primary"
              />
              <p className="text-xs text-goodwill-text-muted mt-1">PDF, DOC, DOCX. Max 5MB.</p>
            </div>

            <div className="mt-3 flex items-center justify-end gap-2">
              <button
                type="button"
                onClick={(e) => {
                  e.preventDefault();
                  e.stopPropagation();
                  // Reset form data
                  setFormData({
                    candidate_name: '',
                    position: '',
                    credential_type: '',
                    issue_date: '',
                    expiry_date: '',
                    email: '',
                    status: '',
                  });
                  setDocumentFile(null);
                  setError(null);
                  onClose();
                }}
                className="px-3 py-1.5 text-sm rounded-lg border border-goodwill-border text-goodwill-dark hover:bg-goodwill-light transition-colors"
                disabled={loading}
              >
                Cancel
              </button>
              <button
                type="submit"
                className="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-sm hover:shadow-md hover:from-blue-700 hover:to-indigo-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                disabled={loading}
              >
                {loading ? 'Saving...' : credential ? 'Update' : 'Create'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
};

export default CredentialForm;

