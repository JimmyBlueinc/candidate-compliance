import { useState, useEffect } from 'react';
import { History, Filter, Search, User, FileText, Calendar, Trash2, Edit, Plus } from 'lucide-react';
import Layout from '../components/Layout/Layout';
import { useAuth } from '../contexts/AuthContext';
import api from '../config/api';

const ActivityLog = () => {
  const { user } = useAuth();
  const [activities, setActivities] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState('all');
  const [searchQuery, setSearchQuery] = useState('');

  useEffect(() => {
    fetchActivities();
  }, [filter, searchQuery]);

  const fetchActivities = async () => {
    setLoading(true);
    try {
      const response = await api.get('/activity-logs', {
        params: {
          filter: filter !== 'all' ? filter : undefined,
          search: searchQuery || undefined,
        }
      });
      const activitiesData = response.data.map(activity => {
        let icon = FileText;
        let color = 'text-blue-600';
        if (activity.action === 'created') {
          icon = Plus;
          color = 'text-green-600';
        } else if (activity.action === 'updated') {
          icon = Edit;
          color = 'text-blue-600';
        } else if (activity.action === 'deleted') {
          icon = Trash2;
          color = 'text-red-600';
        }
        return {
          ...activity,
          icon,
          color,
        };
      });
      setActivities(activitiesData);
    } catch (err) {
      console.error('Error fetching activities:', err);
    } finally {
      setLoading(false);
    }
  };

  const filteredActivities = activities;

  const formatTime = (date) => {
    const now = new Date();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 60) return `${minutes} minutes ago`;
    if (hours < 24) return `${hours} hours ago`;
    return `${days} days ago`;
  };

  return (
    <Layout>
      <div className="space-y-4 animate-fade-in-up">
        <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
          <div className="flex items-center justify-between flex-wrap gap-4">
            <div>
              <h1 className="text-lg font-bold text-goodwill-dark flex items-center gap-2">
                <History className="w-5 h-5 text-goodwill-primary" strokeWidth={2} />
                Activity Log
              </h1>
              <p className="text-xs text-goodwill-text-muted mt-1">Track all system activities and changes</p>
            </div>
            <div className="flex items-center gap-2">
              <div className="relative">
                <Search className="w-4 h-4 absolute left-2 top-1/2 transform -translate-y-1/2 text-goodwill-text-muted" />
                <input
                  type="text"
                  placeholder="Search activities..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="pl-8 pr-3 py-2 border border-goodwill-border/50 rounded-lg text-xs w-48"
                />
              </div>
              <select
                value={filter}
                onChange={(e) => setFilter(e.target.value)}
                className="px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
              >
                <option value="all">All Actions</option>
                <option value="created">Created</option>
                <option value="updated">Updated</option>
                <option value="deleted">Deleted</option>
                <option value="viewed">Viewed</option>
              </select>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm border border-goodwill-border/50 overflow-hidden">
          {filteredActivities.length === 0 ? (
            <div className="p-8 text-center">
              <History className="w-12 h-12 mx-auto mb-3 text-goodwill-text-muted/50" />
              <p className="text-xs font-medium text-goodwill-text-muted">No activities found</p>
            </div>
          ) : (
            <div className="divide-y divide-goodwill-border/30">
              {filteredActivities.map((activity) => {
                const Icon = activity.icon;
                return (
                  <div key={activity.id} className="p-4 hover:bg-goodwill-light/50 transition-colors">
                    <div className="flex items-start gap-3">
                      <div className={`p-2 rounded-lg bg-goodwill-light ${activity.color}`}>
                        <Icon className="w-4 h-4" />
                      </div>
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2 flex-wrap">
                          <span className="text-xs font-semibold text-goodwill-dark">{activity.user}</span>
                          <span className="text-xs text-goodwill-text-muted capitalize">{activity.action}</span>
                          <span className="text-xs text-goodwill-text-muted">a</span>
                          <span className="text-xs font-medium text-goodwill-dark">{activity.entity_name}</span>
                        </div>
                        <div className="flex items-center gap-2 mt-1">
                          <Calendar className="w-3 h-3 text-goodwill-text-muted" />
                          <span className="text-xs text-goodwill-text-muted">{formatTime(new Date(activity.timestamp))}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      </div>
    </Layout>
  );
};

export default ActivityLog;

