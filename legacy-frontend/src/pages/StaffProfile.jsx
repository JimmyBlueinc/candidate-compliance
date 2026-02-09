import { useState, useEffect } from 'react';
import { Shield, Heart, FileText, CheckCircle, XCircle, Clock, Eye, Lock, AlertTriangle, Plus, Edit, Trash2, Download } from 'lucide-react';
import Layout from '../components/Layout/Layout';
import api from '../config/api';
import { useParams } from 'react-router-dom';
import BackgroundCheckForm from '../components/BackgroundCheckForm';
import HealthRecordForm from '../components/HealthRecordForm';
import WorkAuthorizationForm from '../components/WorkAuthorizationForm';
import CredentialForm from '../components/CredentialForm';
import ComplianceBreakdown from '../components/ComplianceBreakdown';

const StaffProfile = () => {
  const { candidateName } = useParams();
  const [loading, setLoading] = useState(true);
  const [staffData, setStaffData] = useState({
    credentials: [],
    backgroundChecks: [],
    healthRecords: [],
    workAuthorizations: [],
  });
  const [activeTab, setActiveTab] = useState('overview');
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [formType, setFormType] = useState(null); // 'credential', 'background', 'health', 'authorization'
  const [editingItem, setEditingItem] = useState(null);

  useEffect(() => {
    fetchStaffData();
  }, [candidateName]);

  const fetchStaffData = async () => {
    try {
      setLoading(true);
      const [credentialsRes, bgChecksRes, healthRes, workAuthRes] = await Promise.all([
        api.get('/credentials', { params: { name: candidateName } }),
        api.get('/background-checks', { params: { candidate_name: candidateName } }),
        api.get('/health-records', { params: { candidate_name: candidateName } }),
        api.get('/work-authorizations', { params: { candidate_name: candidateName } }),
      ]);

      setStaffData({
        credentials: credentialsRes.data.data || [],
        backgroundChecks: bgChecksRes.data.data || [],
        healthRecords: healthRes.data.data || [],
        workAuthorizations: workAuthRes.data.data || [],
      });
    } catch (err) {
      console.error('Error fetching staff data:', err);
    } finally {
      setLoading(false);
    }
  };

  const calculateComplianceScore = () => {
    const allDocs = [
      ...staffData.credentials,
      ...staffData.backgroundChecks,
      ...staffData.healthRecords,
      ...staffData.workAuthorizations,
    ];

    if (allDocs.length === 0) return 0;

    const validDocs = allDocs.filter(doc => {
      if (doc.expiry_date) {
        const expiry = new Date(doc.expiry_date);
        const today = new Date();
        return expiry > today;
      }
      return doc.status === 'verified' || doc.status === 'valid' || doc.status === 'up_to_date' || doc.status === 'active';
    }).length;

    return Math.round((validDocs / allDocs.length) * 100);
  };

  const getStatusColor = (status) => {
    const statusMap = {
      active: 'green',
      verified: 'green',
      valid: 'green',
      up_to_date: 'green',
      expired: 'red',
      failed: 'red',
      revoked: 'red',
      pending: 'yellow',
      expiring_soon: 'yellow',
      pending_renewal: 'yellow',
      due: 'yellow',
    };
    return statusMap[status] || 'gray';
  };

  const handleDelete = async (type, id) => {
    if (!window.confirm('Are you sure you want to delete this record?')) return;

    try {
      const endpoints = {
        credential: `/credentials/${id}`,
        backgroundCheck: `/background-checks/${id}`,
        healthRecord: `/health-records/${id}`,
        workAuthorization: `/work-authorizations/${id}`,
      };

      await api.delete(endpoints[type]);
      await fetchStaffData();
    } catch (err) {
      console.error('Error deleting:', err);
      alert('Failed to delete record');
    }
  };

  const complianceScore = calculateComplianceScore();
  const riskLevel = complianceScore >= 90 ? 'low' : complianceScore >= 70 ? 'medium' : 'high';

  if (loading) {
    return (
      <Layout>
        <div className="p-8 text-center">
          <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-goodwill-primary border-t-transparent"></div>
          <p className="mt-3 text-xs text-goodwill-dark font-medium">Loading staff profile...</p>
        </div>
      </Layout>
    );
  }

  return (
    <Layout>
      <div className="space-y-6 animate-fade-in-up">
        {/* Header */}
        <div className="bg-white rounded-lg shadow-sm p-6 border border-goodwill-border/50">
          <div className="flex items-center justify-between flex-wrap gap-4">
            <div>
              <h1 className="text-2xl font-bold text-goodwill-dark flex items-center gap-2">
                <Shield className="w-6 h-6 text-goodwill-primary" strokeWidth={2} />
                Staff Profile: {candidateName || 'Unknown'}
              </h1>
              <p className="text-sm text-goodwill-text-muted mt-1">
                Comprehensive HR compliance and security overview
              </p>
            </div>
            <div className="flex items-center gap-3">
              {/* Compliance Score */}
              <div className={`px-4 py-2 rounded-lg border-2 ${
                riskLevel === 'low' ? 'bg-green-50 border-green-500' :
                riskLevel === 'medium' ? 'bg-yellow-50 border-yellow-500' :
                'bg-red-50 border-red-500'
              }`}>
                <div className="text-xs font-medium text-goodwill-text-muted">Compliance Score</div>
                <div className={`text-2xl font-bold ${
                  riskLevel === 'low' ? 'text-green-600' :
                  riskLevel === 'medium' ? 'text-yellow-600' :
                  'text-red-600'
                }`}>
                  {complianceScore}%
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Tabs */}
        <div className="bg-white rounded-lg shadow-sm border border-goodwill-border/50">
          <div className="flex border-b border-goodwill-border/50 overflow-x-auto">
            {['overview', 'compliance', 'credentials', 'background', 'health', 'authorizations'].map((tab) => (
              <button
                key={tab}
                onClick={() => setActiveTab(tab)}
                className={`px-6 py-3 text-sm font-medium transition-colors whitespace-nowrap ${
                  activeTab === tab
                    ? 'text-goodwill-primary border-b-2 border-goodwill-primary'
                    : 'text-goodwill-text-muted hover:text-goodwill-dark'
                }`}
              >
                {tab === 'compliance' ? 'Compliance Details' : tab.charAt(0).toUpperCase() + tab.slice(1)}
              </button>
            ))}
          </div>

          <div className="p-6">
            {/* Compliance Details Tab */}
            {activeTab === 'compliance' && (
              <div className="space-y-4">
                <ComplianceBreakdown 
                  staffData={staffData} 
                  complianceScore={complianceScore}
                />
              </div>
            )}

            {/* Overview Tab */}
            {activeTab === 'overview' && (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div className="bg-blue-50 rounded-lg p-4 border border-blue-200">
                  <div className="flex items-center justify-between mb-2">
                    <FileText className="w-5 h-5 text-blue-600" />
                    <span className="text-2xl font-bold text-blue-600">{staffData.credentials.length}</span>
                  </div>
                  <div className="text-sm font-medium text-goodwill-dark">Credentials</div>
                  <div className="text-xs text-goodwill-text-muted mt-1">Professional licenses & certifications</div>
                </div>

                <div className="bg-purple-50 rounded-lg p-4 border border-purple-200">
                  <div className="flex items-center justify-between mb-2">
                    <Shield className="w-5 h-5 text-purple-600" />
                    <span className="text-2xl font-bold text-purple-600">{staffData.backgroundChecks.length}</span>
                  </div>
                  <div className="text-sm font-medium text-goodwill-dark">Background Checks</div>
                  <div className="text-xs text-goodwill-text-muted mt-1">Security clearances</div>
                </div>

                <div className="bg-green-50 rounded-lg p-4 border border-green-200">
                  <div className="flex items-center justify-between mb-2">
                    <Heart className="w-5 h-5 text-green-600" />
                    <span className="text-2xl font-bold text-green-600">{staffData.healthRecords.length}</span>
                  </div>
                  <div className="text-sm font-medium text-goodwill-dark">Health Records</div>
                  <div className="text-xs text-goodwill-text-muted mt-1">Immunizations & screenings</div>
                </div>

                <div className="bg-orange-50 rounded-lg p-4 border border-orange-200">
                  <div className="flex items-center justify-between mb-2">
                    <Lock className="w-5 h-5 text-orange-600" />
                    <span className="text-2xl font-bold text-orange-600">{staffData.workAuthorizations.length}</span>
                  </div>
                  <div className="text-sm font-medium text-goodwill-dark">Work Authorizations</div>
                  <div className="text-xs text-goodwill-text-muted mt-1">Legal work status</div>
                </div>
              </div>
            )}

            {/* Credentials Tab */}
            {activeTab === 'credentials' && (
              <div className="space-y-4">
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-lg font-semibold text-goodwill-dark">Professional Credentials</h3>
                  <button 
                    onClick={() => {
                      setFormType('credential');
                      setEditingItem(null);
                      setIsFormOpen(true);
                    }}
                    className="px-3 py-1.5 bg-goodwill-primary text-white rounded-lg text-xs font-medium hover:bg-goodwill-primary/90 flex items-center gap-1"
                  >
                    <Plus className="w-4 h-4" />
                    Add Credential
                  </button>
                </div>
                {staffData.credentials.length === 0 ? (
                  <p className="text-sm text-goodwill-text-muted text-center py-8">No credentials found</p>
                ) : (
                  <div className="space-y-2">
                    {staffData.credentials.map((cred) => (
                      <div key={cred.id} className="bg-goodwill-light rounded-lg p-4 border border-goodwill-border/50">
                        <div className="flex items-start justify-between">
                          <div className="flex-1">
                            <h4 className="font-semibold text-goodwill-dark">{cred.credential_type}</h4>
                            <p className="text-xs text-goodwill-text-muted mt-1">
                              {cred.position} • {cred.specialty}
                            </p>
                            <div className="flex items-center gap-4 mt-2 text-xs text-goodwill-text-muted">
                              <span>Issue: {cred.issue_date || 'N/A'}</span>
                              <span>Expiry: {cred.expiry_date || 'N/A'}</span>
                            </div>
                          </div>
                          <div className="flex items-center gap-2">
                            <span className={`px-2 py-1 rounded text-xs font-medium bg-${getStatusColor(cred.status)}-100 text-${getStatusColor(cred.status)}-700`}>
                              {cred.status}
                            </span>
                            {cred.document_url && (
                              <a href={cred.document_url} target="_blank" rel="noopener noreferrer" className="p-1 hover:bg-goodwill-light rounded">
                                <Download className="w-4 h-4 text-goodwill-primary" />
                              </a>
                            )}
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}

            {/* Background Checks Tab */}
            {activeTab === 'background' && (
              <div className="space-y-4">
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-lg font-semibold text-goodwill-dark">Background Checks</h3>
                  <button 
                    onClick={() => {
                      setFormType('background');
                      setEditingItem(null);
                      setIsFormOpen(true);
                    }}
                    className="px-3 py-1.5 bg-goodwill-primary text-white rounded-lg text-xs font-medium hover:bg-goodwill-primary/90 flex items-center gap-1"
                  >
                    <Plus className="w-4 h-4" />
                    Add Check
                  </button>
                </div>
                {staffData.backgroundChecks.length === 0 ? (
                  <p className="text-sm text-goodwill-text-muted text-center py-8">No background checks found</p>
                ) : (
                  <div className="space-y-2">
                    {staffData.backgroundChecks.map((check) => (
                      <div key={check.id} className="bg-goodwill-light rounded-lg p-4 border border-goodwill-border/50">
                        <div className="flex items-start justify-between">
                          <div className="flex-1">
                            <h4 className="font-semibold text-goodwill-dark">
                              {check.check_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                            </h4>
                            <div className="flex items-center gap-4 mt-2 text-xs text-goodwill-text-muted">
                              <span>Issue: {check.issue_date || 'N/A'}</span>
                              <span>Expiry: {check.expiry_date || 'N/A'}</span>
                            </div>
                            {check.verified_by && (
                              <p className="text-xs text-goodwill-text-muted mt-1">
                                Verified on {check.verification_date}
                              </p>
                            )}
                          </div>
                          <div className="flex items-center gap-2">
                            <span className={`px-2 py-1 rounded text-xs font-medium bg-${getStatusColor(check.verification_status)}-100 text-${getStatusColor(check.verification_status)}-700`}>
                              {check.verification_status}
                            </span>
                            {check.document_url && (
                              <a href={check.document_url} target="_blank" rel="noopener noreferrer" className="p-1 hover:bg-goodwill-light rounded">
                                <Download className="w-4 h-4 text-goodwill-primary" />
                              </a>
                            )}
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}

            {/* Health Records Tab */}
            {activeTab === 'health' && (
              <div className="space-y-4">
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-lg font-semibold text-goodwill-dark">Health Records</h3>
                  <button 
                    onClick={() => {
                      setFormType('health');
                      setEditingItem(null);
                      setIsFormOpen(true);
                    }}
                    className="px-3 py-1.5 bg-goodwill-primary text-white rounded-lg text-xs font-medium hover:bg-goodwill-primary/90 flex items-center gap-1"
                  >
                    <Plus className="w-4 h-4" />
                    Add Record
                  </button>
                </div>
                {staffData.healthRecords.length === 0 ? (
                  <p className="text-sm text-goodwill-text-muted text-center py-8">No health records found</p>
                ) : (
                  <div className="space-y-2">
                    {staffData.healthRecords.map((record) => (
                      <div key={record.id} className="bg-goodwill-light rounded-lg p-4 border border-goodwill-border/50">
                        <div className="flex items-start justify-between">
                          <div className="flex-1">
                            <h4 className="font-semibold text-goodwill-dark">
                              {record.record_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                              {record.vaccine_type && ` - ${record.vaccine_type}`}
                            </h4>
                            {record.provider_name && (
                              <p className="text-xs text-goodwill-text-muted mt-1">Provider: {record.provider_name}</p>
                            )}
                            <div className="flex items-center gap-4 mt-2 text-xs text-goodwill-text-muted">
                              <span>Date: {record.administration_date || 'N/A'}</span>
                              {record.expiry_date && <span>Expiry: {record.expiry_date}</span>}
                            </div>
                          </div>
                          <div className="flex items-center gap-2">
                            <span className={`px-2 py-1 rounded text-xs font-medium bg-${getStatusColor(record.status)}-100 text-${getStatusColor(record.status)}-700`}>
                              {record.status}
                            </span>
                            {record.document_url && (
                              <a href={record.document_url} target="_blank" rel="noopener noreferrer" className="p-1 hover:bg-goodwill-light rounded">
                                <Download className="w-4 h-4 text-goodwill-primary" />
                              </a>
                            )}
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}

            {/* Work Authorizations Tab */}
            {activeTab === 'authorizations' && (
              <div className="space-y-4">
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-lg font-semibold text-goodwill-dark">Work Authorizations</h3>
                  <button 
                    onClick={() => {
                      setFormType('authorization');
                      setEditingItem(null);
                      setIsFormOpen(true);
                    }}
                    className="px-3 py-1.5 bg-goodwill-primary text-white rounded-lg text-xs font-medium hover:bg-goodwill-primary/90 flex items-center gap-1"
                  >
                    <Plus className="w-4 h-4" />
                    Add Authorization
                  </button>
                </div>
                {staffData.workAuthorizations.length === 0 ? (
                  <p className="text-sm text-goodwill-text-muted text-center py-8">No work authorizations found</p>
                ) : (
                  <div className="space-y-2">
                    {staffData.workAuthorizations.map((auth) => (
                      <div key={auth.id} className="bg-goodwill-light rounded-lg p-4 border border-goodwill-border/50">
                        <div className="flex items-start justify-between">
                          <div className="flex-1">
                            <h4 className="font-semibold text-goodwill-dark">
                              {auth.authorization_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                            </h4>
                            {auth.document_number && (
                              <p className="text-xs text-goodwill-text-muted mt-1">Document #: {auth.document_number}</p>
                            )}
                            <div className="flex items-center gap-4 mt-2 text-xs text-goodwill-text-muted">
                              <span>Issue: {auth.issue_date || 'N/A'}</span>
                              <span>Expiry: {auth.expiry_date || 'N/A'}</span>
                            </div>
                          </div>
                          <div className="flex items-center gap-2">
                            <span className={`px-2 py-1 rounded text-xs font-medium bg-${getStatusColor(auth.status)}-100 text-${getStatusColor(auth.status)}-700`}>
                              {auth.status}
                            </span>
                            {auth.document_url && (
                              <a href={auth.document_url} target="_blank" rel="noopener noreferrer" className="p-1 hover:bg-goodwill-light rounded">
                                <Download className="w-4 h-4 text-goodwill-primary" />
                              </a>
                            )}
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Forms */}
      {formType === 'credential' && (
        <CredentialForm
          isOpen={isFormOpen}
          onClose={() => setIsFormOpen(false)}
          onSuccess={fetchStaffData}
          credential={editingItem}
          defaultCandidateName={candidateName}
        />
      )}

      {formType === 'background' && (
        <BackgroundCheckForm
          isOpen={isFormOpen}
          onClose={() => setIsFormOpen(false)}
          onSuccess={fetchStaffData}
          backgroundCheck={editingItem}
          candidateName={candidateName}
        />
      )}

      {formType === 'health' && (
        <HealthRecordForm
          isOpen={isFormOpen}
          onClose={() => setIsFormOpen(false)}
          onSuccess={fetchStaffData}
          healthRecord={editingItem}
          candidateName={candidateName}
        />
      )}

      {formType === 'authorization' && (
        <WorkAuthorizationForm
          isOpen={isFormOpen}
          onClose={() => setIsFormOpen(false)}
          onSuccess={fetchStaffData}
          workAuthorization={editingItem}
          candidateName={candidateName}
        />
      )}
    </Layout>
  );
};

export default StaffProfile;

