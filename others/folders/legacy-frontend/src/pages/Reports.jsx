import { useState, useEffect } from 'react';
import { TrendingUp, Download, FileText, Calendar, Filter, Mail } from 'lucide-react';
import Layout from '../components/Layout/Layout';
import CredentialForm from '../components/CredentialForm';
import api from '../config/api';
import { CSVLink } from 'react-csv';

const Reports = () => {
  const [credentials, setCredentials] = useState([]);
  const [loading, setLoading] = useState(false);
  const [reportType, setReportType] = useState('status');
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingCredential, setEditingCredential] = useState(null);
  const [filters, setFilters] = useState({
    status: 'all',
    dateFrom: '',
    dateTo: '',
  });

  const handleAdd = () => {
    setEditingCredential(null);
    setIsFormOpen(true);
  };

  const handleFormSuccess = async () => {
    await fetchCredentials();
  };

  useEffect(() => {
    fetchCredentials();
  }, []);

  const fetchCredentials = async () => {
    setLoading(true);
    try {
      const response = await api.get('/credentials');
      setCredentials(response.data.data || response.data || []);
    } catch (err) {
      console.error('Error fetching credentials:', err);
    } finally {
      setLoading(false);
    }
  };

  const generateReport = () => {
    let filtered = [...credentials];

    if (filters.status !== 'all') {
      filtered = filtered.filter(c => c.status === filters.status);
    }

    if (filters.dateFrom) {
      filtered = filtered.filter(c => {
        if (!c.expiry_date) return false;
        return new Date(c.expiry_date) >= new Date(filters.dateFrom);
      });
    }

    if (filters.dateTo) {
      filtered = filtered.filter(c => {
        if (!c.expiry_date) return false;
        return new Date(c.expiry_date) <= new Date(filters.dateTo);
      });
    }

    return filtered;
  };

  const reportData = generateReport();
  const csvData = reportData.map(cred => ({
    'Candidate Name': cred.candidate_name,
    'Position': cred.position,
    'Credential Type': cred.credential_type,
    'Issue Date': cred.issue_date ? new Date(cred.issue_date).toLocaleDateString() : '-',
    'Expiry Date': cred.expiry_date ? new Date(cred.expiry_date).toLocaleDateString() : '-',
    'Status': cred.status,
    'Email': cred.email,
  }));

  return (
    <Layout>
      <div className="space-y-4 animate-fade-in-up">
        <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
          <h1 className="text-lg font-bold text-goodwill-dark flex items-center gap-2">
            <TrendingUp className="w-5 h-5 text-goodwill-primary" strokeWidth={2} />
            Reports
          </h1>
          <p className="text-xs text-goodwill-text-muted mt-1">Generate and export custom reports</p>
        </div>

        {/* Report Filters */}
        <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
          <div className="flex items-center gap-2 mb-4">
            <Filter className="w-4 h-4 text-goodwill-primary" />
            <h3 className="text-sm font-semibold text-goodwill-dark">Report Filters</h3>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
              <label className="block text-xs font-semibold text-goodwill-dark mb-1.5">Status</label>
              <select
                value={filters.status}
                onChange={(e) => setFilters({ ...filters, status: e.target.value })}
                className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
              >
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="expiring_soon">Expiring Soon</option>
                <option value="expired">Expired</option>
              </select>
            </div>
            <div>
              <label className="block text-xs font-semibold text-goodwill-dark mb-1.5">From Date</label>
              <input
                type="date"
                value={filters.dateFrom}
                onChange={(e) => setFilters({ ...filters, dateFrom: e.target.value })}
                className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
              />
            </div>
            <div>
              <label className="block text-xs font-semibold text-goodwill-dark mb-1.5">To Date</label>
              <input
                type="date"
                value={filters.dateTo}
                onChange={(e) => setFilters({ ...filters, dateTo: e.target.value })}
                className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
              />
            </div>
          </div>
        </div>

        {/* Report Summary */}
        <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-sm font-semibold text-goodwill-dark">Report Summary</h3>
            <CSVLink
              data={csvData}
              filename={`credential-report-${new Date().toISOString().split('T')[0]}.csv`}
              className="px-3 py-2 bg-goodwill-primary text-white rounded-lg text-xs font-medium hover:bg-goodwill-primary/90 transition-all flex items-center gap-1.5"
            >
              <Download className="w-3.5 h-3.5" />
              Export CSV
            </CSVLink>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div className="p-3 bg-goodwill-light/50 rounded-lg">
              <p className="text-xs text-goodwill-text-muted">Total Records</p>
              <p className="text-xl font-bold text-goodwill-primary mt-1">{reportData.length}</p>
            </div>
            <div className="p-3 bg-goodwill-light/50 rounded-lg">
              <p className="text-xs text-goodwill-text-muted">Active</p>
              <p className="text-xl font-bold text-green-600 mt-1">
                {reportData.filter(c => c.status === 'active').length}
              </p>
            </div>
            <div className="p-3 bg-goodwill-light/50 rounded-lg">
              <p className="text-xs text-goodwill-text-muted">Expiring Soon</p>
              <p className="text-xl font-bold text-yellow-600 mt-1">
                {reportData.filter(c => c.status === 'expiring_soon').length}
              </p>
            </div>
          </div>
        </div>

        {/* Report Preview */}
        <div className="bg-white rounded-lg shadow-sm border border-goodwill-border/50 overflow-hidden">
          <div className="p-4 border-b border-goodwill-border/50">
            <h3 className="text-sm font-semibold text-goodwill-dark">Report Preview</h3>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full divide-y divide-goodwill-border/30">
              <thead className="bg-goodwill-primary">
                <tr>
                  <th className="px-3 py-2 text-left text-xs font-semibold text-white">Candidate</th>
                  <th className="px-3 py-2 text-left text-xs font-semibold text-white">Type</th>
                  <th className="px-3 py-2 text-left text-xs font-semibold text-white">Expiry Date</th>
                  <th className="px-3 py-2 text-left text-xs font-semibold text-white">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-goodwill-border/30">
                {reportData.slice(0, 10).map((cred) => (
                  <tr key={cred.id} className="hover:bg-goodwill-light/50">
                    <td className="px-3 py-2 text-xs text-goodwill-dark">{cred.candidate_name}</td>
                    <td className="px-3 py-2 text-xs text-goodwill-text-muted">{cred.credential_type}</td>
                    <td className="px-3 py-2 text-xs text-goodwill-text-muted">
                      {cred.expiry_date ? new Date(cred.expiry_date).toLocaleDateString() : '-'}
                    </td>
                    <td className="px-3 py-2">
                      <span className={`px-2 py-0.5 rounded text-xs font-medium ${
                        cred.status === 'active' ? 'bg-green-100 text-green-700' :
                        cred.status === 'expiring_soon' ? 'bg-yellow-100 text-yellow-700' :
                        cred.status === 'expired' ? 'bg-red-100 text-red-700' :
                        'bg-gray-100 text-gray-700'
                      }`}>
                        {cred.status}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
            {reportData.length > 10 && (
              <div className="p-3 text-center text-xs text-goodwill-text-muted">
                Showing 10 of {reportData.length} records. Export to see all.
              </div>
            )}
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

export default Reports;

