import { useState, useEffect } from 'react';
import { Shield, AlertTriangle, CheckCircle, XCircle, TrendingUp, Users, FileCheck, Eye } from 'lucide-react';
import Layout from '../components/Layout/Layout';
import CredentialForm from '../components/CredentialForm';
import api from '../config/api';
import { Link } from 'react-router-dom';

const ComplianceDashboard = () => {
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState({
    totalStaff: 0,
    compliant: 0,
    atRisk: 0,
    nonCompliant: 0,
    averageScore: 0,
  });
  const [staffList, setStaffList] = useState([]);
  const [filter, setFilter] = useState('all'); // all, compliant, atRisk, nonCompliant
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingCredential, setEditingCredential] = useState(null);

  const handleAdd = () => {
    setEditingCredential(null);
    setIsFormOpen(true);
  };

  const handleFormSuccess = async () => {
    await fetchComplianceData();
  };

  useEffect(() => {
    fetchComplianceData();
  }, [filter]);

  const fetchComplianceData = async () => {
    try {
      setLoading(true);
      
      // Fetch all credentials to get unique candidate names
      const credentialsRes = await api.get('/credentials');
      const credentials = credentialsRes.data.data || [];
      
      // Get unique candidate names
      const candidateNames = [...new Set(credentials.map(c => c.candidate_name))];
      
      // Fetch all HR data for each candidate
      const staffData = await Promise.all(
        candidateNames.map(async (name) => {
          try {
            const [bgChecksRes, healthRes, workAuthRes] = await Promise.all([
              api.get('/background-checks', { params: { candidate_name: name } }),
              api.get('/health-records', { params: { candidate_name: name } }),
              api.get('/work-authorizations', { params: { candidate_name: name } }),
            ]);

            const allDocs = [
              ...credentials.filter(c => c.candidate_name === name),
              ...(bgChecksRes.data.data || []),
              ...(healthRes.data.data || []),
              ...(workAuthRes.data.data || []),
            ];

            const validDocs = allDocs.filter(doc => {
              if (doc.expiry_date) {
                const expiry = new Date(doc.expiry_date);
                const today = new Date();
                return expiry > today;
              }
              const validStatuses = ['verified', 'valid', 'up_to_date', 'active'];
              return validStatuses.includes(doc.status) || validStatuses.includes(doc.verification_status);
            }).length;

            const complianceScore = allDocs.length > 0 
              ? Math.round((validDocs / allDocs.length) * 100) 
              : 0;

            const riskLevel = complianceScore >= 90 ? 'compliant' : 
                             complianceScore >= 70 ? 'atRisk' : 'nonCompliant';

            const expiringDocs = allDocs.filter(doc => {
              if (!doc.expiry_date) return false;
              const expiry = new Date(doc.expiry_date);
              const today = new Date();
              const daysUntilExpiry = Math.ceil((expiry - today) / (1000 * 60 * 60 * 24));
              return daysUntilExpiry > 0 && daysUntilExpiry <= 30;
            }).length;

            return {
              name,
              complianceScore,
              riskLevel,
              totalDocs: allDocs.length,
              validDocs,
              expiringDocs,
              credentials: credentials.filter(c => c.candidate_name === name).length,
              backgroundChecks: (bgChecksRes.data.data || []).length,
              healthRecords: (healthRes.data.data || []).length,
              workAuthorizations: (workAuthRes.data.data || []).length,
            };
          } catch (err) {
            console.error(`Error fetching data for ${name}:`, err);
            return null;
          }
        })
      );

      const validStaff = staffData.filter(s => s !== null);
      setStaffList(validStaff);

      // Calculate overall stats
      const totalStaff = validStaff.length;
      const compliant = validStaff.filter(s => s.riskLevel === 'compliant').length;
      const atRisk = validStaff.filter(s => s.riskLevel === 'atRisk').length;
      const nonCompliant = validStaff.filter(s => s.riskLevel === 'nonCompliant').length;
      const averageScore = totalStaff > 0
        ? Math.round(validStaff.reduce((sum, s) => sum + s.complianceScore, 0) / totalStaff)
        : 0;

      setStats({
        totalStaff,
        compliant,
        atRisk,
        nonCompliant,
        averageScore,
      });
    } catch (err) {
      console.error('Error fetching compliance data:', err);
    } finally {
      setLoading(false);
    }
  };

  const getRiskColor = (riskLevel) => {
    const colors = {
      compliant: 'green',
      atRisk: 'yellow',
      nonCompliant: 'red',
    };
    return colors[riskLevel] || 'gray';
  };

  const filteredStaff = staffList.filter(staff => {
    if (filter === 'all') return true;
    return staff.riskLevel === filter;
  });

  if (loading) {
    return (
      <Layout onAddClick={handleAdd}>
        <div className="p-8 text-center">
          <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-goodwill-primary border-t-transparent"></div>
          <p className="mt-3 text-xs text-goodwill-dark font-medium">Loading compliance data...</p>
        </div>
      </Layout>
    );
  }

  return (
    <Layout onAddClick={handleAdd}>
      <div className="space-y-6 animate-fade-in-up">
        {/* Header */}
        <div className="bg-white rounded-lg shadow-sm p-6 border border-goodwill-border/50">
          <div className="flex items-center justify-between flex-wrap gap-4">
            <div>
              <h1 className="text-2xl font-bold text-goodwill-dark flex items-center gap-2">
                <Shield className="w-6 h-6 text-goodwill-primary" strokeWidth={2} />
                Compliance Dashboard
              </h1>
              <p className="text-sm text-goodwill-text-muted mt-1">
                Comprehensive HR compliance overview and monitoring
              </p>
            </div>
          </div>
        </div>

        {/* Statistics Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
          <div className="bg-white rounded-lg shadow-sm p-5 border border-goodwill-border/50">
            <div className="flex items-center justify-between mb-2">
              <Users className="w-5 h-5 text-blue-600" />
              <span className="text-2xl font-bold text-blue-600">{stats.totalStaff}</span>
            </div>
            <div className="text-sm font-medium text-goodwill-dark">Total Staff</div>
            <div className="text-xs text-goodwill-text-muted mt-1">Active candidates</div>
          </div>

          <div className="bg-white rounded-lg shadow-sm p-5 border border-green-200">
            <div className="flex items-center justify-between mb-2">
              <CheckCircle className="w-5 h-5 text-green-600" />
              <span className="text-2xl font-bold text-green-600">{stats.compliant}</span>
            </div>
            <div className="text-sm font-medium text-goodwill-dark">Compliant</div>
            <div className="text-xs text-goodwill-text-muted mt-1">≥90% compliance</div>
          </div>

          <div className="bg-white rounded-lg shadow-sm p-5 border border-yellow-200">
            <div className="flex items-center justify-between mb-2">
              <AlertTriangle className="w-5 h-5 text-yellow-600" />
              <span className="text-2xl font-bold text-yellow-600">{stats.atRisk}</span>
            </div>
            <div className="text-sm font-medium text-goodwill-dark">At Risk</div>
            <div className="text-xs text-goodwill-text-muted mt-1">70-89% compliance</div>
          </div>

          <div className="bg-white rounded-lg shadow-sm p-5 border border-red-200">
            <div className="flex items-center justify-between mb-2">
              <XCircle className="w-5 h-5 text-red-600" />
              <span className="text-2xl font-bold text-red-600">{stats.nonCompliant}</span>
            </div>
            <div className="text-sm font-medium text-goodwill-dark">Non-Compliant</div>
            <div className="text-xs text-goodwill-text-muted mt-1">&lt;70% compliance</div>
          </div>

          <div className="bg-white rounded-lg shadow-sm p-5 border border-goodwill-primary/20">
            <div className="flex items-center justify-between mb-2">
              <TrendingUp className="w-5 h-5 text-goodwill-primary" />
              <span className="text-2xl font-bold text-goodwill-primary">{stats.averageScore}%</span>
            </div>
            <div className="text-sm font-medium text-goodwill-dark">Average Score</div>
            <div className="text-xs text-goodwill-text-muted mt-1">Overall compliance</div>
          </div>
        </div>

        {/* Filters */}
        <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
          <div className="flex items-center gap-2 flex-wrap">
            <span className="text-sm font-medium text-goodwill-dark">Filter:</span>
            {['all', 'compliant', 'atRisk', 'nonCompliant'].map((filterOption) => (
              <button
                key={filterOption}
                onClick={() => setFilter(filterOption)}
                className={`px-3 py-1.5 rounded-lg text-xs font-medium transition-all ${
                  filter === filterOption
                    ? 'bg-goodwill-primary text-white'
                    : 'bg-goodwill-light text-goodwill-dark hover:bg-goodwill-primary/10'
                }`}
              >
                {filterOption === 'all' ? 'All Staff' :
                 filterOption === 'compliant' ? 'Compliant' :
                 filterOption === 'atRisk' ? 'At Risk' : 'Non-Compliant'}
              </button>
            ))}
          </div>
        </div>

        {/* Staff List */}
        <div className="bg-white rounded-lg shadow-sm border border-goodwill-border/50 overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-goodwill-light border-b border-goodwill-border/50">
                <tr>
                  <th className="px-4 py-3 text-left text-xs font-semibold text-goodwill-dark">Staff Name</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold text-goodwill-dark">Compliance Score</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold text-goodwill-dark">Risk Level</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold text-goodwill-dark">Documents</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold text-goodwill-dark">Expiring Soon</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold text-goodwill-dark">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-goodwill-border/50">
                {filteredStaff.length === 0 ? (
                  <tr>
                    <td colSpan="6" className="px-4 py-8 text-center text-sm text-goodwill-text-muted">
                      No staff found
                    </td>
                  </tr>
                ) : (
                  filteredStaff.map((staff) => (
                    <tr key={staff.name} className="hover:bg-goodwill-light/50 transition-colors">
                      <td className="px-4 py-3">
                        <div className="font-medium text-goodwill-dark">{staff.name}</div>
                        <div className="text-xs text-goodwill-text-muted mt-0.5">
                          {staff.credentials} credentials • {staff.backgroundChecks} checks • {staff.healthRecords} health • {staff.workAuthorizations} auth
                        </div>
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-2">
                          <span className={`text-lg font-bold ${
                            staff.complianceScore >= 90 ? 'text-green-600' :
                            staff.complianceScore >= 70 ? 'text-yellow-600' :
                            'text-red-600'
                          }`}>
                            {staff.complianceScore}%
                          </span>
                          <div className="w-24 h-2 bg-goodwill-light rounded-full overflow-hidden">
                            <div
                              className={`h-full ${
                                staff.complianceScore >= 90 ? 'bg-green-500' :
                                staff.complianceScore >= 70 ? 'bg-yellow-500' :
                                'bg-red-500'
                              }`}
                              style={{ width: `${staff.complianceScore}%` }}
                            />
                          </div>
                        </div>
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-2">
                          {staff.riskLevel === 'compliant' ? (
                            <CheckCircle className="w-4 h-4 text-green-600" />
                          ) : staff.riskLevel === 'atRisk' ? (
                            <AlertTriangle className="w-4 h-4 text-yellow-600" />
                          ) : (
                            <XCircle className="w-4 h-4 text-red-600" />
                          )}
                          <span className={`px-2 py-1 rounded text-xs font-medium ${
                            staff.riskLevel === 'compliant' ? 'bg-green-100 text-green-700' :
                            staff.riskLevel === 'atRisk' ? 'bg-yellow-100 text-yellow-700' :
                            'bg-red-100 text-red-700'
                          }`}>
                            {staff.riskLevel === 'compliant' ? 'Compliant' :
                             staff.riskLevel === 'atRisk' ? 'At Risk' : 'Non-Compliant'}
                          </span>
                        </div>
                      </td>
                      <td className="px-4 py-3">
                        <div className="text-sm text-goodwill-dark">
                          {staff.validDocs} / {staff.totalDocs} valid
                        </div>
                      </td>
                      <td className="px-4 py-3">
                        {staff.expiringDocs > 0 ? (
                          <span className="px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-700">
                            {staff.expiringDocs} expiring
                          </span>
                        ) : (
                          <span className="text-xs text-goodwill-text-muted">None</span>
                        )}
                      </td>
                      <td className="px-4 py-3">
                        <Link
                          to={`/staff/${encodeURIComponent(staff.name)}`}
                          className="px-3 py-1.5 bg-goodwill-primary text-white rounded-lg text-xs font-medium hover:bg-goodwill-primary/90 transition-all inline-flex items-center gap-1"
                        >
                          <Eye className="w-3.5 h-3.5" />
                          View Profile
                        </Link>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {/* Credential Form */}
      <CredentialForm
        isOpen={isFormOpen}
        onClose={() => setIsFormOpen(false)}
        onSuccess={handleFormSuccess}
        credential={editingCredential}
      />
    </Layout>
  );
};

export default ComplianceDashboard;

