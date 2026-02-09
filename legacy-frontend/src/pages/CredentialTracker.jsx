import { useState, useEffect, useMemo } from 'react';
import { Calendar, AlertTriangle, CheckCircle, XCircle, Clock, FileText, Download, BarChart3, Shield } from 'lucide-react';
import Layout from '../components/Layout/Layout';
import StatusTag from '../components/StatusTag';
import CredentialForm from '../components/CredentialForm';
import api from '../config/api';
import { CSVLink } from 'react-csv';

const CredentialTracker = () => {
  const [credentials, setCredentials] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [filter, setFilter] = useState('all'); // all, expiring_soon, expired, active
  const [provinceFilter, setProvinceFilter] = useState('');
  const [specialtyFilter, setSpecialtyFilter] = useState('');
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingCredential, setEditingCredential] = useState(null);

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
    try {
      setLoading(true);
      const response = await api.get('/credentials');
      setCredentials(response.data.data || response.data || []);
      setError(null);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to fetch credentials');
      console.error('Error fetching credentials:', err);
    } finally {
      setLoading(false);
    }
  };

  // Filter credentials based on status, province, and specialty
  const filteredCredentials = useMemo(() => {
    const now = new Date();
    now.setHours(0, 0, 0, 0);

    return credentials.filter(cred => {
      // Status filter
      let statusMatch = true;
      if (filter !== 'all') {
        if (!cred.expiry_date) {
          statusMatch = filter === 'pending';
        } else {
          const expiryDate = new Date(cred.expiry_date);
          expiryDate.setHours(0, 0, 0, 0);
          const daysUntilExpiry = Math.ceil((expiryDate - now) / (1000 * 60 * 60 * 24));

          switch (filter) {
            case 'expiring_soon':
              statusMatch = daysUntilExpiry <= 30 && daysUntilExpiry > 0;
              break;
            case 'expired':
              statusMatch = daysUntilExpiry <= 0;
              break;
            case 'active':
              statusMatch = daysUntilExpiry > 30;
              break;
            case 'pending':
              statusMatch = !cred.expiry_date;
              break;
            default:
              statusMatch = true;
          }
        }
      }

      // Province filter
      const provinceMatch = !provinceFilter || cred.province === provinceFilter;

      // Specialty filter
      const specialtyMatch = !specialtyFilter || cred.specialty === specialtyFilter;

      return statusMatch && provinceMatch && specialtyMatch;
    });
  }, [credentials, filter, provinceFilter, specialtyFilter]);

  // Calculate statistics
  const stats = useMemo(() => {
    const now = new Date();
    now.setHours(0, 0, 0, 0);

    return credentials.reduce((acc, cred) => {
      if (!cred.expiry_date) {
        acc.pending++;
        return acc;
      }

      const expiryDate = new Date(cred.expiry_date);
      expiryDate.setHours(0, 0, 0, 0);
      const daysUntilExpiry = Math.ceil((expiryDate - now) / (1000 * 60 * 60 * 24));

      if (daysUntilExpiry <= 0) {
        acc.expired++;
      } else if (daysUntilExpiry <= 30) {
        acc.expiringSoon++;
      } else {
        acc.active++;
      }

      return acc;
    }, { active: 0, expiringSoon: 0, expired: 0, pending: 0, total: credentials.length });
  }, [credentials]);

  // Full list of provinces and specialties for filters
  const allProvinces = [
    'Alberta',
    'Saskatchewan',
    'Manitoba',
    'Nova Scotia',
    'Prince Edward Island',
    'New Brunswick',
    'Newfoundland & Labrador',
    'Ontario',
    'British Columbia',
    'Quebec',
    'Yukon',
    'Northwest Territories',
    'Nunavut'
  ];

  const allSpecialties = [
    'Medical-Surgical',
    'Intensive Care Unit (ICU)',
    'Emergency',
    'Long Term Care (LTC)',
    'Cardiovascular',
    'Pediatrics',
    'Mental Health',
    'Operating Room',
    'Labor & Delivery',
    'Oncology',
    'Critical Care',
    'Rehabilitation',
    'Other'
  ];

  // Get unique provinces and specialties from existing credentials (for "All" option)
  const uniqueProvinces = useMemo(() => {
    const provinces = [...new Set(credentials.map(c => c.province).filter(Boolean))];
    return provinces.sort();
  }, [credentials]);

  const uniqueSpecialties = useMemo(() => {
    const specialties = [...new Set(credentials.map(c => c.specialty).filter(Boolean))];
    return specialties.sort();
  }, [credentials]);

  // Prepare CSV data
  const csvData = filteredCredentials.map(cred => ({
    'Candidate Name': cred.candidate_name,
    'Position': cred.position,
    'Specialty': cred.specialty || '-',
    'Credential Type': cred.credential_type,
    'Issue Date': cred.issue_date ? new Date(cred.issue_date).toLocaleDateString() : '-',
    'Expiry Date': cred.expiry_date ? new Date(cred.expiry_date).toLocaleDateString() : '-',
    'Status': cred.status,
    'Email': cred.email,
    'Province': cred.province || '-',
  }));

  if (loading) {
    return (
      <Layout onAddClick={handleAdd}>
        <div className="p-8 text-center">
          <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-goodwill-primary border-t-transparent"></div>
          <p className="mt-3 text-xs text-goodwill-dark font-medium">Loading credentials...</p>
        </div>
      </Layout>
    );
  }

  return (
    <Layout onAddClick={handleAdd}>
      <div className="space-y-4 animate-fade-in-up">
        {/* Header */}
        <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
          <div className="flex items-center justify-between flex-wrap gap-4">
            <div>
              <h1 className="text-lg font-bold text-goodwill-dark flex items-center gap-2">
                <Shield className="w-5 h-5 text-goodwill-primary" strokeWidth={2} />
                Credential Management & Compliance Monitoring
              </h1>
              <p className="text-xs text-goodwill-text-muted mt-1">
                Security verification, staff monitoring, and compliance tracking for all healthcare professionals
              </p>
            </div>
            <CSVLink
              data={csvData}
              filename={`credential-tracker-${new Date().toISOString().split('T')[0]}.csv`}
              className="px-3 py-2 bg-goodwill-primary text-white rounded-lg text-xs font-medium hover:bg-goodwill-primary/90 transition-all duration-300 flex items-center gap-1.5 hover:scale-105 active:scale-95"
            >
              <Download className="w-3.5 h-3.5" strokeWidth={2} />
              Export CSV
            </CSVLink>
          </div>
        </div>

        {/* Statistics Cards */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
          {/* Active - Green */}
          <div className="bg-green-500 border border-green-600 rounded-lg shadow-sm shadow-green-500/25 p-3 hover-lift">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs text-white/90 font-medium uppercase tracking-wide mb-1">Active</p>
                <p className="text-xl font-bold text-white">{stats.active}</p>
              </div>
              <div className="h-8 w-8 rounded-lg bg-white/20 border border-white/30 flex items-center justify-center">
                <CheckCircle className="w-5 h-5 text-white" strokeWidth={2} />
              </div>
            </div>
          </div>
          {/* Expiring Soon - Yellow */}
          <div className="bg-yellow-500 border border-yellow-600 rounded-lg shadow-sm shadow-yellow-500/25 p-3 hover-lift">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs text-white/90 font-medium uppercase tracking-wide mb-1">Expiring Soon</p>
                <p className="text-xl font-bold text-white">{stats.expiringSoon}</p>
              </div>
              <div className="h-8 w-8 rounded-lg bg-white/20 border border-white/30 flex items-center justify-center">
                <Clock className="w-5 h-5 text-white" strokeWidth={2} />
              </div>
            </div>
          </div>
          {/* Expired - Red */}
          <div className="bg-red-500 border border-red-600 rounded-lg shadow-sm shadow-red-500/25 p-3 hover-lift">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs text-white/90 font-medium uppercase tracking-wide mb-1">Expired</p>
                <p className="text-xl font-bold text-white">{stats.expired}</p>
              </div>
              <div className="h-8 w-8 rounded-lg bg-white/20 border border-white/30 flex items-center justify-center">
                <XCircle className="w-5 h-5 text-white" strokeWidth={2} />
              </div>
            </div>
          </div>
          {/* Total - Blue (Goodwill Primary) */}
          <div className="bg-goodwill-primary border border-goodwill-primary rounded-lg shadow-sm shadow-goodwill-primary/25 p-3 hover-lift">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs text-white/90 font-medium uppercase tracking-wide mb-1">Total</p>
                <p className="text-xl font-bold text-white">{stats.total}</p>
              </div>
              <div className="h-8 w-8 rounded-lg bg-white/20 border border-white/30 flex items-center justify-center">
                <BarChart3 className="w-5 h-5 text-white" strokeWidth={2} />
              </div>
            </div>
          </div>
        </div>

        {/* Filter Buttons */}
        <div className="bg-white rounded-lg shadow-sm p-3 border border-goodwill-border/50">
          <div className="flex items-center gap-3 flex-wrap">
            <div className="flex items-center gap-2">
              <span className="text-xs font-medium text-goodwill-dark">Status:</span>
              {['all', 'active', 'expiring_soon', 'expired', 'pending'].map((filterOption) => (
                <button
                  key={filterOption}
                  onClick={() => setFilter(filterOption)}
                  className={`px-1.5 py-0.5 rounded text-[9px] font-medium whitespace-nowrap transition-all duration-300 border ${
                    filter === filterOption
                      ? 'bg-goodwill-primary text-white border-goodwill-primary'
                      : 'bg-goodwill-light text-goodwill-dark border-transparent hover:bg-goodwill-primary/10'
                  }`}
                >
                  {filterOption === 'expiring_soon' ? 'Expiring' : filterOption.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                </button>
              ))}
            </div>
            <div className="flex items-center gap-2">
              <label className="text-xs font-medium text-goodwill-dark">Province:</label>
              <select
                value={provinceFilter}
                onChange={(e) => setProvinceFilter(e.target.value)}
                className="px-3 py-1.5 rounded-lg text-xs border border-goodwill-border/50 bg-white text-goodwill-dark focus:ring-2 focus:ring-goodwill-primary focus:border-transparent"
              >
                <option value="">All Provinces</option>
                {allProvinces.map(province => (
                  <option key={province} value={province}>{province}</option>
                ))}
              </select>
            </div>
            <div className="flex items-center gap-2">
              <label className="text-xs font-medium text-goodwill-dark">Specialty:</label>
              <select
                value={specialtyFilter}
                onChange={(e) => setSpecialtyFilter(e.target.value)}
                className="px-3 py-1.5 rounded-lg text-xs border border-goodwill-border/50 bg-white text-goodwill-dark focus:ring-2 focus:ring-goodwill-primary focus:border-transparent"
              >
                <option value="">All Specialties</option>
                {allSpecialties.map(specialty => (
                  <option key={specialty} value={specialty}>{specialty}</option>
                ))}
              </select>
            </div>
            {(provinceFilter || specialtyFilter) && (
              <button
                onClick={() => {
                  setProvinceFilter('');
                  setSpecialtyFilter('');
                }}
                className="px-3 py-1.5 rounded-lg text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 transition-all duration-300"
              >
                Clear Filters
              </button>
            )}
          </div>
        </div>

        {/* Credentials Table */}
        <div className="bg-white rounded-lg shadow-sm overflow-hidden border border-goodwill-border/50">
          {error ? (
            <div className="p-8 text-center text-goodwill-secondary font-semibold">{error}</div>
          ) : filteredCredentials.length === 0 ? (
            <div className="p-8 text-center text-goodwill-text-muted">
              <FileText className="w-12 h-12 mx-auto mb-3 text-goodwill-text-muted/50" strokeWidth={1.5} />
              <p className="text-xs font-medium">No credentials found</p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full divide-y divide-goodwill-border/30">
                <thead className="bg-goodwill-primary">
                  <tr>
                    <th className="px-3 py-2 text-left text-[10px] font-semibold text-white uppercase">Candidate</th>
                    <th className="px-3 py-2 text-left text-[10px] font-semibold text-white uppercase hidden md:table-cell">Position</th>
                    <th className="px-3 py-2 text-left text-[10px] font-semibold text-white uppercase hidden md:table-cell">Specialty</th>
                    <th className="px-3 py-2 text-left text-[10px] font-semibold text-white uppercase">Type</th>
                    <th className="px-3 py-2 text-left text-[10px] font-semibold text-white uppercase hidden lg:table-cell">Issue Date</th>
                    <th className="px-3 py-2 text-left text-[10px] font-semibold text-white uppercase">Expiry Date</th>
                    <th className="px-3 py-2 text-left text-[10px] font-semibold text-white uppercase hidden lg:table-cell">Email</th>
                    <th className="px-3 py-2 text-left text-[10px] font-semibold text-white uppercase hidden md:table-cell">Province</th>
                    <th className="px-3 py-2 text-left text-[10px] font-semibold text-white uppercase">Status</th>
                    <th className="px-3 py-2 text-left text-[10px] font-semibold text-white uppercase hidden xl:table-cell">Days Until Expiry</th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-goodwill-border/30">
                  {filteredCredentials.map((credential, index) => {
                    const now = new Date();
                    now.setHours(0, 0, 0, 0);
                    const expiryDate = credential.expiry_date ? new Date(credential.expiry_date) : null;
                    const daysUntilExpiry = expiryDate
                      ? Math.ceil((expiryDate.setHours(0, 0, 0, 0) - now) / (1000 * 60 * 60 * 24))
                      : null;

                    return (
                      <tr
                        key={credential.id}
                        className={`hover:bg-goodwill-light/50 transition-all duration-200 ${
                          index % 2 === 0 ? 'bg-white' : 'bg-goodwill-light/30'
                        } animate-fade-in`}
                        style={{ animationDelay: `${index * 0.03}s` }}
                      >
                        <td className="px-3 py-2 text-xs font-medium text-goodwill-dark">{credential.candidate_name}</td>
                        <td className="px-3 py-2 text-xs text-goodwill-text-muted hidden md:table-cell whitespace-nowrap">{credential.position || '-'}</td>
                        <td className="px-3 py-2 text-xs text-goodwill-text-muted hidden md:table-cell whitespace-nowrap">{credential.specialty || '-'}</td>
                        <td className="px-3 py-2 text-xs text-goodwill-text-muted whitespace-nowrap">{credential.credential_type}</td>
                        <td className="px-3 py-2 text-[10px] text-goodwill-text-muted hidden lg:table-cell">
                          {credential.issue_date ? new Date(credential.issue_date).toLocaleDateString() : '-'}
                        </td>
                        <td className="px-3 py-2 text-[10px] text-goodwill-text-muted font-medium">
                          {credential.expiry_date ? new Date(credential.expiry_date).toLocaleDateString() : '-'}
                        </td>
                        <td className="px-3 py-2 text-[10px] text-goodwill-text-muted hidden lg:table-cell max-w-[150px] truncate">
                          {credential.email || '-'}
                        </td>
                        <td className="px-3 py-2 text-[10px] text-goodwill-text-muted hidden md:table-cell whitespace-nowrap">
                          {credential.province || '-'}
                        </td>
                        <td className="px-3 py-2">
                          <StatusTag status={credential.status} />
                        </td>
                        <td className="px-3 py-2 text-[10px] font-medium hidden xl:table-cell">
                          {daysUntilExpiry !== null ? (
                            <span className={daysUntilExpiry <= 0 ? 'text-red-600' : daysUntilExpiry <= 30 ? 'text-yellow-600' : 'text-green-600'}>
                              {daysUntilExpiry <= 0 ? 'Expired' : `${daysUntilExpiry} days`}
                            </span>
                          ) : (
                            <span className="text-gray-500">-</span>
                          )}
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}
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

export default CredentialTracker;

