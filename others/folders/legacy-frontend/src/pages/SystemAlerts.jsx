import { useState, useEffect } from 'react';
import { AlertCircle, CheckCircle, XCircle, Bell, RefreshCw } from 'lucide-react';
import Layout from '../components/Layout/Layout';
import api from '../config/api';

const SystemAlerts = () => {
  const [alerts, setAlerts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [systemHealth, setSystemHealth] = useState({
    status: 'healthy',
    database: 'connected',
    email: 'configured',
    storage: 'normal',
  });

  useEffect(() => {
    fetchAlerts();
    checkSystemHealth();
  }, []);

  const fetchAlerts = async () => {
    setLoading(true);
    try {
      // TODO: Implement alerts API
      const mockAlerts = [
        {
          id: 1,
          type: 'warning',
          title: 'High Expiry Rate',
          message: '15 credentials expiring in the next 7 days',
          timestamp: new Date(Date.now() - 1000 * 60 * 60),
          resolved: false,
        },
        {
          id: 2,
          type: 'info',
          title: 'System Update Available',
          message: 'A new version of the system is available',
          timestamp: new Date(Date.now() - 1000 * 60 * 60 * 24),
          resolved: false,
        },
        {
          id: 3,
          type: 'error',
          title: 'Email Service Issue',
          message: 'Failed to send reminder emails to 3 recipients',
          timestamp: new Date(Date.now() - 1000 * 60 * 60 * 2),
          resolved: true,
        },
      ];
      setAlerts(mockAlerts);
    } catch (err) {
      console.error('Error fetching alerts:', err);
    } finally {
      setLoading(false);
    }
  };

  const checkSystemHealth = async () => {
    try {
      const response = await api.get('/health');
      // TODO: Implement comprehensive health check
    } catch (err) {
      console.error('Health check error:', err);
    }
  };

  const resolveAlert = (id) => {
    setAlerts(alerts.map(a => a.id === id ? { ...a, resolved: true } : a));
  };

  const getAlertIcon = (type) => {
    switch (type) {
      case 'error':
        return <XCircle className="w-5 h-5 text-red-600" />;
      case 'warning':
        return <AlertCircle className="w-5 h-5 text-yellow-600" />;
      case 'success':
        return <CheckCircle className="w-5 h-5 text-green-600" />;
      default:
        return <Bell className="w-5 h-5 text-blue-600" />;
    }
  };

  const getHealthColor = (status) => {
    switch (status) {
      case 'healthy':
      case 'connected':
      case 'configured':
      case 'normal':
        return 'text-green-600';
      case 'warning':
        return 'text-yellow-600';
      default:
        return 'text-red-600';
    }
  };

  if (loading) {
    return (
      <Layout>
        <div className="p-8 text-center">
          <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-goodwill-primary"></div>
          <p className="mt-3 text-xs text-goodwill-dark font-medium">Loading alerts...</p>
        </div>
      </Layout>
    );
  }

  return (
    <Layout>
      <div className="space-y-4 animate-fade-in-up">
        <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-lg font-bold text-goodwill-dark flex items-center gap-2">
                <AlertCircle className="w-5 h-5 text-goodwill-primary" strokeWidth={2} />
                System Alerts
              </h1>
              <p className="text-xs text-goodwill-text-muted mt-1">Monitor system health and alerts</p>
            </div>
            <button
              onClick={fetchAlerts}
              className="p-2 bg-goodwill-light border border-goodwill-border rounded-lg hover:bg-goodwill-primary hover:text-white transition-all"
              title="Refresh"
            >
              <RefreshCw className="w-4 h-4" />
            </button>
          </div>
        </div>

        {/* System Health */}
        <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
          <h3 className="text-sm font-semibold text-goodwill-dark mb-4">System Health</h3>
          <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div className="p-3 bg-goodwill-light/50 rounded-lg">
              <p className="text-xs text-goodwill-text-muted">Overall Status</p>
              <p className={`text-sm font-bold mt-1 ${getHealthColor(systemHealth.status)}`}>
                {systemHealth.status.toUpperCase()}
              </p>
            </div>
            <div className="p-3 bg-goodwill-light/50 rounded-lg">
              <p className="text-xs text-goodwill-text-muted">Database</p>
              <p className={`text-sm font-bold mt-1 ${getHealthColor(systemHealth.database)}`}>
                {systemHealth.database.toUpperCase()}
              </p>
            </div>
            <div className="p-3 bg-goodwill-light/50 rounded-lg">
              <p className="text-xs text-goodwill-text-muted">Email Service</p>
              <p className={`text-sm font-bold mt-1 ${getHealthColor(systemHealth.email)}`}>
                {systemHealth.email.toUpperCase()}
              </p>
            </div>
            <div className="p-3 bg-goodwill-light/50 rounded-lg">
              <p className="text-xs text-goodwill-text-muted">Storage</p>
              <p className={`text-sm font-bold mt-1 ${getHealthColor(systemHealth.storage)}`}>
                {systemHealth.storage.toUpperCase()}
              </p>
            </div>
          </div>
        </div>

        {/* Alerts List */}
        <div className="bg-white rounded-lg shadow-sm border border-goodwill-border/50 overflow-hidden">
          <div className="p-4 border-b border-goodwill-border/50">
            <h3 className="text-sm font-semibold text-goodwill-dark">
              Recent Alerts ({alerts.filter(a => !a.resolved).length} active)
            </h3>
          </div>
          <div className="divide-y divide-goodwill-border/30">
            {alerts.length === 0 ? (
              <div className="p-8 text-center">
                <CheckCircle className="w-12 h-12 mx-auto mb-3 text-green-600/50" />
                <p className="text-xs font-medium text-goodwill-text-muted">No alerts</p>
              </div>
            ) : (
              alerts.map((alert) => (
                <div
                  key={alert.id}
                  className={`p-4 transition-all ${
                    alert.resolved ? 'opacity-60 bg-goodwill-light/30' : 'hover:bg-goodwill-light/50'
                  }`}
                >
                  <div className="flex items-start gap-3">
                    <div className="flex-shrink-0 mt-0.5">
                      {getAlertIcon(alert.type)}
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="flex items-start justify-between gap-2">
                        <div className="flex-1">
                          <h4 className="text-sm font-semibold text-goodwill-dark">{alert.title}</h4>
                          <p className="text-xs text-goodwill-text-muted mt-1">{alert.message}</p>
                          <p className="text-xs text-goodwill-text-muted mt-2">
                            {new Date(alert.timestamp).toLocaleString()}
                          </p>
                        </div>
                        {!alert.resolved && (
                          <button
                            onClick={() => resolveAlert(alert.id)}
                            className="px-3 py-1.5 bg-goodwill-primary text-white rounded-lg text-xs font-medium hover:bg-goodwill-primary/90 transition-all"
                          >
                            Resolve
                          </button>
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              ))
            )}
          </div>
        </div>
      </div>
    </Layout>
  );
};

export default SystemAlerts;

