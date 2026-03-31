import { useState } from 'react';
import { Search as SearchIcon, FileText, Filter } from 'lucide-react';
import Layout from '../components/Layout/Layout';
import CredentialForm from '../components/CredentialForm';
import api from '../config/api';
import { useAuth } from '../contexts/AuthContext';

const Search = () => {
  const { isAdmin } = useAuth();
  const [searchQuery, setSearchQuery] = useState('');
  const [results, setResults] = useState([]);
  const [loading, setLoading] = useState(false);
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingCredential, setEditingCredential] = useState(null);
  const [searchFilters, setSearchFilters] = useState({
    searchIn: 'all', // all, name, email, type, position
    status: 'all',
    dateRange: 'all',
    province: '',
    specialty: '',
  });

  const handleAdd = () => {
    setEditingCredential(null);
    setIsFormOpen(true);
  };

  const handleFormSuccess = async () => {
    if (searchQuery.trim()) {
      await handleSearch();
    }
  };

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

  const handleSearch = async () => {
    if (!searchQuery.trim()) {
      setResults([]);
      return;
    }

    setLoading(true);
    try {
      const params = {
        name: searchFilters.searchIn === 'all' || searchFilters.searchIn === 'name' ? searchQuery : '',
        type: searchFilters.searchIn === 'all' || searchFilters.searchIn === 'type' ? searchQuery : '',
      };

      const response = await api.get('/credentials', { params });
      let filteredResults = response.data.data || response.data || [];

      // Additional client-side filtering
      if (searchFilters.status !== 'all') {
        filteredResults = filteredResults.filter(c => c.status === searchFilters.status);
      }
      if (searchFilters.province) {
        filteredResults = filteredResults.filter(c => c.province === searchFilters.province);
      }
      if (searchFilters.specialty) {
        filteredResults = filteredResults.filter(c => c.specialty === searchFilters.specialty);
      }

      setResults(filteredResults);
    } catch (err) {
      console.error('Search error:', err);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Layout onAddClick={handleAdd}>
      <div className="space-y-4 animate-fade-in-up">
        <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
          <h1 className="text-lg font-bold text-goodwill-dark flex items-center gap-2">
            <SearchIcon className="w-5 h-5 text-goodwill-primary" strokeWidth={2} />
            Advanced Search
          </h1>
          <p className="text-xs text-goodwill-text-muted mt-1">Search across all credentials</p>
        </div>

        <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
          <div className="space-y-4">
            <div>
              <label className="block text-xs font-semibold text-goodwill-dark mb-2">Search Query</label>
              <div className="flex gap-2">
                <input
                  type="text"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
                  placeholder="Enter search term..."
                  className="flex-1 px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs focus:ring-2 focus:ring-goodwill-primary"
                />
                <button
                  onClick={handleSearch}
                  disabled={loading}
                  className="px-4 py-2 bg-goodwill-primary text-white rounded-lg text-xs font-medium hover:bg-goodwill-primary/90 transition-all disabled:opacity-50"
                >
                  {loading ? 'Searching...' : 'Search'}
                </button>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
              <div>
                <label className="block text-xs font-semibold text-goodwill-dark mb-2">Search In</label>
                <select
                  value={searchFilters.searchIn}
                  onChange={(e) => setSearchFilters({ ...searchFilters, searchIn: e.target.value })}
                  className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
                >
                  <option value="all">All Fields</option>
                  <option value="name">Candidate Name</option>
                  <option value="email">Email</option>
                  <option value="type">Credential Type</option>
                  <option value="position">Position</option>
                </select>
              </div>
              <div>
                <label className="block text-xs font-semibold text-goodwill-dark mb-2">Status</label>
                <select
                  value={searchFilters.status}
                  onChange={(e) => setSearchFilters({ ...searchFilters, status: e.target.value })}
                  className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
                >
                  <option value="all">All Status</option>
                  <option value="active">Active</option>
                  <option value="expiring_soon">Expiring Soon</option>
                  <option value="expired">Expired</option>
                </select>
              </div>
              <div>
                <label className="block text-xs font-semibold text-goodwill-dark mb-2">Province</label>
                <select
                  value={searchFilters.province}
                  onChange={(e) => setSearchFilters({ ...searchFilters, province: e.target.value })}
                  className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
                >
                  <option value="">All Provinces</option>
                  {allProvinces.map(province => (
                    <option key={province} value={province}>{province}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-xs font-semibold text-goodwill-dark mb-2">Specialty</label>
                <select
                  value={searchFilters.specialty}
                  onChange={(e) => setSearchFilters({ ...searchFilters, specialty: e.target.value })}
                  className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
                >
                  <option value="">All Specialties</option>
                  {allSpecialties.map(specialty => (
                    <option key={specialty} value={specialty}>{specialty}</option>
                  ))}
                </select>
              </div>
              <div className="flex items-end">
                {(searchFilters.province || searchFilters.specialty) && (
                  <button
                    onClick={() => setSearchFilters({ ...searchFilters, province: '', specialty: '' })}
                    className="w-full px-3 py-2 bg-goodwill-light hover:bg-goodwill-light/80 text-goodwill-dark rounded-lg text-xs font-medium transition-all"
                  >
                    Clear Filters
                  </button>
                )}
              </div>
            </div>
          </div>
        </div>

        {results.length > 0 && (
          <div className="bg-white rounded-lg shadow-sm border border-goodwill-border/50">
            <div className="p-4 border-b border-goodwill-border/50">
              <h2 className="text-sm font-semibold text-goodwill-dark">
                Search Results ({results.length})
              </h2>
            </div>
            <div className="divide-y divide-goodwill-border/30">
              {results.map((cred) => (
                <div key={cred.id} className="p-4 hover:bg-goodwill-light/50 transition-colors">
                  <div className="flex items-start gap-3">
                    <FileText className="w-5 h-5 text-goodwill-primary mt-0.5" />
                    <div className="flex-1">
                      <h3 className="text-sm font-semibold text-goodwill-dark">{cred.candidate_name}</h3>
                      <p className="text-xs text-goodwill-text-muted mt-1">{cred.credential_type}</p>
                      <p className="text-xs text-goodwill-text-muted">{cred.email}</p>
                      {cred.expiry_date && (
                        <p className="text-xs text-goodwill-text-muted mt-1">
                          Expires: {new Date(cred.expiry_date).toLocaleDateString()}
                        </p>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {searchQuery && !loading && results.length === 0 && (
          <div className="bg-white rounded-lg shadow-sm p-8 text-center border border-goodwill-border/50">
            <SearchIcon className="w-12 h-12 mx-auto mb-3 text-goodwill-text-muted/50" />
            <p className="text-xs font-medium text-goodwill-text-muted">No results found</p>
          </div>
        )}
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

export default Search;

