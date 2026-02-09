import { useState, useEffect } from 'react';
import { BarChart3, TrendingUp, Calendar, FileText, Users, AlertCircle } from 'lucide-react';
import Layout from '../components/Layout/Layout';
import CredentialForm from '../components/CredentialForm';
import api from '../config/api';

const Analytics = () => {
  const [loading, setLoading] = useState(true);
  const [dateRange, setDateRange] = useState('30'); // days
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingCredential, setEditingCredential] = useState(null);
  const [analytics, setAnalytics] = useState({
    total: 0,
    by_status: {},
    by_type: {},
    by_province: {},
    by_specialty: {},
    by_position: {},
    expiring_next_30: 0,
    average_days_to_expiry: 0,
  });

  const handleAdd = () => {
    setEditingCredential(null);
    setIsFormOpen(true);
  };

  const handleFormSuccess = async () => {
    await fetchAnalytics();
  };

  useEffect(() => {
    fetchAnalytics();
  }, [dateRange]);

  const fetchAnalytics = async () => {
    setLoading(true);
    try {
      const response = await api.get('/analytics', {
        params: { date_range: dateRange }
      });
      setAnalytics(response.data);
    } catch (err) {
      console.error('Error fetching analytics:', err);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <Layout onAddClick={handleAdd}>
        <div className="p-8 text-center">
          <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-goodwill-primary"></div>
          <p className="mt-3 text-xs text-goodwill-dark font-medium">Loading analytics...</p>
        </div>
      </Layout>
    );
  }

  return (
    <Layout onAddClick={handleAdd}>
      <div className="space-y-4 animate-fade-in-up">
        <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
          <div className="flex items-center justify-between flex-wrap gap-4">
            <div>
              <h1 className="text-lg font-bold text-goodwill-dark flex items-center gap-2">
                <BarChart3 className="w-5 h-5 text-goodwill-primary" strokeWidth={2} />
                Analytics Dashboard
              </h1>
              <p className="text-xs text-goodwill-text-muted mt-1">Detailed insights and metrics</p>
            </div>
            <select
              value={dateRange}
              onChange={(e) => setDateRange(e.target.value)}
              className="px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs bg-white"
            >
              <option value="7">Last 7 days</option>
              <option value="30">Last 30 days</option>
              <option value="90">Last 90 days</option>
              <option value="365">Last year</option>
            </select>
          </div>
        </div>

        {/* Key Metrics */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
          <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50 hover-lift">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs text-goodwill-text-muted">Total Credentials</p>
                <p className="text-2xl font-bold text-goodwill-primary mt-1">{analytics.total || 0}</p>
              </div>
              <FileText className="w-8 h-8 text-goodwill-primary/50" />
            </div>
          </div>
          <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50 hover-lift">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs text-goodwill-text-muted">Expiring Next 30 Days</p>
                <p className="text-2xl font-bold text-yellow-600 mt-1">{analytics.expiring_next_30 || 0}</p>
              </div>
              <AlertCircle className="w-8 h-8 text-yellow-600/50" />
            </div>
          </div>
          <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50 hover-lift">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs text-goodwill-text-muted">Avg Days to Expiry</p>
                <p className="text-2xl font-bold text-goodwill-secondary mt-1">
                  {Math.round(analytics.average_days_to_expiry || 0)}
                </p>
              </div>
              <Calendar className="w-8 h-8 text-goodwill-secondary/50" />
            </div>
          </div>
          <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50 hover-lift">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs text-goodwill-text-muted">Status Types</p>
                <p className="text-2xl font-bold text-goodwill-dark mt-1">
                  {Object.keys(analytics.by_status || {}).length}
                </p>
              </div>
              <TrendingUp className="w-8 h-8 text-goodwill-dark/50" />
            </div>
          </div>
        </div>

        {/* Charts Section */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
          {/* Status Distribution */}
          <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
            <h3 className="text-sm font-semibold text-goodwill-dark mb-4">Status Distribution</h3>
            <div className="space-y-3">
              {Object.entries(analytics.by_status || {}).map(([status, count]) => {
                const percentage = analytics.total > 0 ? (count / analytics.total) * 100 : 0;
                const color = status === 'active' ? 'bg-green-500' : status === 'expiring_soon' ? 'bg-yellow-500' : status === 'expired' ? 'bg-red-500' : 'bg-gray-500';
                return (
                  <div key={status}>
                    <div className="flex items-center justify-between mb-1">
                      <span className="text-xs font-medium text-goodwill-dark capitalize">{status.replace('_', ' ')}</span>
                      <span className="text-xs text-goodwill-text-muted">{count} ({percentage.toFixed(1)}%)</span>
                    </div>
                    <div className="w-full bg-goodwill-light rounded-full h-2">
                      <div
                        className={`${color} h-2 rounded-full transition-all duration-500`}
                        style={{ width: `${percentage}%` }}
                      />
                    </div>
                  </div>
                );
              })}
            </div>
          </div>

          {/* Credential Types */}
          <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
            <h3 className="text-sm font-semibold text-goodwill-dark mb-4">Top Credential Types</h3>
            <div className="space-y-2">
              {Object.entries(analytics.by_type || {})
                .sort((a, b) => b[1] - a[1])
                .slice(0, 5)
                .map(([type, count]) => (
                  <div key={type} className="flex items-center justify-between p-2 bg-goodwill-light/50 rounded-lg">
                    <span className="text-xs font-medium text-goodwill-dark">{type}</span>
                    <span className="text-xs font-semibold text-goodwill-primary">{count}</span>
                  </div>
                ))}
            </div>
          </div>
        </div>

        {/* Additional Breakdowns */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
          {/* By Province */}
          <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
            <h3 className="text-sm font-semibold text-goodwill-dark mb-4 flex items-center gap-2">
              <Users className="w-4 h-4 text-goodwill-primary" />
              By Province
            </h3>
            <div className="space-y-2 max-h-64 overflow-y-auto">
              {Object.keys(analytics.by_province || {}).length > 0 ? (
                Object.entries(analytics.by_province || {})
                  .sort((a, b) => b[1] - a[1])
                  .map(([province, count]) => (
                    <div key={province} className="flex items-center justify-between p-2 bg-goodwill-light/50 rounded-lg">
                      <span className="text-xs font-medium text-goodwill-dark">{province}</span>
                      <span className="text-xs font-semibold text-goodwill-primary">{count}</span>
                    </div>
                  ))
              ) : (
                <p className="text-xs text-goodwill-text-muted text-center py-4">No province data available</p>
              )}
            </div>
          </div>

          {/* By Specialty */}
          <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
            <h3 className="text-sm font-semibold text-goodwill-dark mb-4 flex items-center gap-2">
              <FileText className="w-4 h-4 text-goodwill-primary" />
              By Specialty
            </h3>
            <div className="space-y-2 max-h-64 overflow-y-auto">
              {Object.keys(analytics.by_specialty || {}).length > 0 ? (
                Object.entries(analytics.by_specialty || {})
                  .sort((a, b) => b[1] - a[1])
                  .map(([specialty, count]) => (
                    <div key={specialty} className="flex items-center justify-between p-2 bg-goodwill-light/50 rounded-lg">
                      <span className="text-xs font-medium text-goodwill-dark">{specialty}</span>
                      <span className="text-xs font-semibold text-goodwill-primary">{count}</span>
                    </div>
                  ))
              ) : (
                <p className="text-xs text-goodwill-text-muted text-center py-4">No specialty data available</p>
              )}
            </div>
          </div>

          {/* By Position */}
          <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
            <h3 className="text-sm font-semibold text-goodwill-dark mb-4 flex items-center gap-2">
              <Users className="w-4 h-4 text-goodwill-primary" />
              By Position
            </h3>
            <div className="space-y-2 max-h-64 overflow-y-auto">
              {Object.keys(analytics.by_position || {}).length > 0 ? (
                Object.entries(analytics.by_position || {})
                  .sort((a, b) => b[1] - a[1])
                  .slice(0, 10)
                  .map(([position, count]) => (
                    <div key={position} className="flex items-center justify-between p-2 bg-goodwill-light/50 rounded-lg">
                      <span className="text-xs font-medium text-goodwill-dark truncate flex-1">{position}</span>
                      <span className="text-xs font-semibold text-goodwill-primary ml-2">{count}</span>
                    </div>
                  ))
              ) : (
                <p className="text-xs text-goodwill-text-muted text-center py-4">No position data available</p>
              )}
            </div>
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

export default Analytics;

