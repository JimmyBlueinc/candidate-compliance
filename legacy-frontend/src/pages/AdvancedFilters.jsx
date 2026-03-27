import { useState, useEffect } from 'react';
import { Filter, Plus, Save, Trash2, Search } from 'lucide-react';
import Layout from '../components/Layout/Layout';
import api from '../config/api';

const AdvancedFilters = () => {
  const [savedFilters, setSavedFilters] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [newFilter, setNewFilter] = useState({
    name: '',
    status: 'all',
    type: '',
    dateFrom: '',
    dateTo: '',
  });

  useEffect(() => {
    fetchFilters();
  }, []);

  const fetchFilters = async () => {
    setLoading(true);
    try {
      const response = await api.get('/filters');
      setSavedFilters(response.data);
    } catch (err) {
      console.error('Error fetching filters:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleSaveFilter = async () => {
    if (!newFilter.name.trim()) {
      alert('Please enter a filter name');
      return;
    }

    try {
      const filterData = {
        name: newFilter.name,
        filters: {
          status: newFilter.status,
          type: newFilter.type,
          dateFrom: newFilter.dateFrom,
          dateTo: newFilter.dateTo,
        },
      };

      await api.post('/filters', filterData);
      fetchFilters();
      setNewFilter({ name: '', status: 'all', type: '', dateFrom: '', dateTo: '' });
      setShowCreateModal(false);
    } catch (err) {
      alert(err.response?.data?.message || 'Failed to save filter');
    }
  };

  const handleDeleteFilter = async (id) => {
    if (!window.confirm('Are you sure you want to delete this filter?')) return;
    try {
      await api.delete(`/filters/${id}`);
      fetchFilters();
    } catch (err) {
      alert(err.response?.data?.message || 'Failed to delete filter');
    }
  };

  const handleUseFilter = (filter) => {
    // Navigate to dashboard with filter applied
    window.location.href = `/dashboard?filter=${encodeURIComponent(JSON.stringify(filter.filters))}`;
  };

  return (
    <Layout>
      <div className="space-y-4 animate-fade-in-up">
        <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-lg font-bold text-goodwill-dark flex items-center gap-2">
                <Filter className="w-5 h-5 text-goodwill-primary" strokeWidth={2} />
                Advanced Filters
              </h1>
              <p className="text-xs text-goodwill-text-muted mt-1">Create and manage saved filter presets</p>
            </div>
            <button
              onClick={() => setShowCreateModal(true)}
              className="px-3 py-2 bg-goodwill-primary text-white rounded-lg text-xs font-medium hover:bg-goodwill-primary/90 transition-all flex items-center gap-1.5"
            >
              <Plus className="w-3.5 h-3.5" />
              New Filter
            </button>
          </div>
        </div>

        {/* Saved Filters */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
          {savedFilters.map((filter) => (
            <div
              key={filter.id}
              className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50 hover-lift"
            >
              <div className="flex items-start justify-between mb-3">
                <div className="flex-1">
                  <h3 className="text-sm font-semibold text-goodwill-dark mb-2">{filter.name}</h3>
                  <div className="space-y-1">
                    {filter.filters.status !== 'all' && (
                      <p className="text-xs text-goodwill-text-muted">
                        Status: <span className="font-medium">{filter.filters.status}</span>
                      </p>
                    )}
                    {filter.filters.type && (
                      <p className="text-xs text-goodwill-text-muted">
                        Type: <span className="font-medium">{filter.filters.type}</span>
                      </p>
                    )}
                    {filter.filters.dateFrom && (
                      <p className="text-xs text-goodwill-text-muted">
                        From: <span className="font-medium">{filter.filters.dateFrom}</span>
                      </p>
                    )}
                  </div>
                </div>
                <button
                  onClick={() => handleDeleteFilter(filter.id)}
                  className="p-1 hover:bg-goodwill-light rounded transition-colors"
                  title="Delete"
                >
                  <Trash2 className="w-4 h-4 text-goodwill-text-muted" />
                </button>
              </div>
              <button
                onClick={() => handleUseFilter(filter)}
                className="w-full px-3 py-2 bg-goodwill-primary text-white rounded-lg text-xs font-medium hover:bg-goodwill-primary/90 transition-all flex items-center justify-center gap-1.5"
              >
                <Search className="w-3.5 h-3.5" />
                Use Filter
              </button>
            </div>
          ))}
        </div>

        {/* Create Filter Modal */}
        {showCreateModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
            <div className="bg-white rounded-lg shadow-lg p-6 max-w-md w-full mx-4 border border-goodwill-border/50">
              <h3 className="text-base font-bold text-goodwill-dark mb-4">Create New Filter</h3>
              <div className="space-y-3">
                <div>
                  <label className="block text-xs font-semibold text-goodwill-dark mb-1.5">Filter Name</label>
                  <input
                    type="text"
                    value={newFilter.name}
                    onChange={(e) => setNewFilter({ ...newFilter, name: e.target.value })}
                    placeholder="e.g., Expiring This Month"
                    className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-goodwill-dark mb-1.5">Status</label>
                  <select
                    value={newFilter.status}
                    onChange={(e) => setNewFilter({ ...newFilter, status: e.target.value })}
                    className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
                  >
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="expiring_soon">Expiring Soon</option>
                    <option value="expired">Expired</option>
                  </select>
                </div>
                <div>
                  <label className="block text-xs font-semibold text-goodwill-dark mb-1.5">Credential Type</label>
                  <input
                    type="text"
                    value={newFilter.type}
                    onChange={(e) => setNewFilter({ ...newFilter, type: e.target.value })}
                    placeholder="Optional"
                    className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
                  />
                </div>
                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="block text-xs font-semibold text-goodwill-dark mb-1.5">From Date</label>
                    <input
                      type="date"
                      value={newFilter.dateFrom}
                      onChange={(e) => setNewFilter({ ...newFilter, dateFrom: e.target.value })}
                      className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-semibold text-goodwill-dark mb-1.5">To Date</label>
                    <input
                      type="date"
                      value={newFilter.dateTo}
                      onChange={(e) => setNewFilter({ ...newFilter, dateTo: e.target.value })}
                      className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
                    />
                  </div>
                </div>
              </div>
              <div className="flex items-center gap-2 mt-6">
                <button
                  onClick={handleSaveFilter}
                  className="flex-1 px-4 py-2 bg-goodwill-primary text-white rounded-lg text-xs font-medium hover:bg-goodwill-primary/90 transition-all flex items-center justify-center gap-1.5"
                >
                  <Save className="w-4 h-4" />
                  Save Filter
                </button>
                <button
                  onClick={() => {
                    setShowCreateModal(false);
                    setNewFilter({ name: '', status: 'all', type: '', dateFrom: '', dateTo: '' });
                  }}
                  className="px-4 py-2 bg-goodwill-light border border-goodwill-border text-goodwill-dark rounded-lg text-xs font-medium hover:bg-goodwill-primary hover:text-white transition-all"
                >
                  Cancel
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </Layout>
  );
};

export default AdvancedFilters;

