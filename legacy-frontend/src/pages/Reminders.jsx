import { useState, useEffect } from 'react';
import { Clock, Calendar, Bell, CheckCircle, XCircle } from 'lucide-react';
import Layout from '../components/Layout/Layout';
import CredentialForm from '../components/CredentialForm';
import api from '../config/api';
import { useAuth } from '../contexts/AuthContext';

const Reminders = () => {
  const { user } = useAuth();
  const [reminders, setReminders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState('upcoming');
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingCredential, setEditingCredential] = useState(null);

  const handleAdd = () => {
    setEditingCredential(null);
    setIsFormOpen(true);
  };

  const handleFormSuccess = async () => {
    await fetchReminders();
  };

  useEffect(() => {
    fetchReminders();
  }, []);

  const fetchReminders = async () => {
    setLoading(true);
    try {
      const response = await api.get('/credentials');
      const credentials = response.data.data || response.data || [];
      
      // Generate reminders from credentials
      const now = new Date();
      const reminderList = credentials
        .filter(c => c.expiry_date)
        .map(c => {
          const expiry = new Date(c.expiry_date);
          const daysUntil = Math.ceil((expiry - now) / (1000 * 60 * 60 * 24));
          return {
            id: c.id,
            credential: c.candidate_name,
            type: c.credential_type,
            expiryDate: c.expiry_date,
            daysUntil,
            status: daysUntil <= 0 ? 'expired' : daysUntil <= 30 ? 'expiring_soon' : 'upcoming',
            completed: false,
          };
        })
        .sort((a, b) => a.daysUntil - b.daysUntil);
      
      setReminders(reminderList);
    } catch (err) {
      console.error('Error fetching reminders:', err);
    } finally {
      setLoading(false);
    }
  };

  const filteredReminders = reminders.filter(r => {
    if (filter === 'all') return true;
    if (filter === 'upcoming') return r.status === 'upcoming';
    if (filter === 'expiring_soon') return r.status === 'expiring_soon';
    if (filter === 'expired') return r.status === 'expired';
    return true;
  });

  const markAsCompleted = (id) => {
    setReminders(reminders.map(r => r.id === id ? { ...r, completed: !r.completed } : r));
  };

  if (loading) {
    return (
      <Layout>
        <div className="p-8 text-center">
          <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-goodwill-primary"></div>
          <p className="mt-3 text-xs text-goodwill-dark font-medium">Loading reminders...</p>
        </div>
      </Layout>
    );
  }

  return (
    <Layout>
      <div className="space-y-4 animate-fade-in-up">
        <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
          <div className="flex items-center justify-between flex-wrap gap-4">
            <div>
              <h1 className="text-lg font-bold text-goodwill-dark flex items-center gap-2">
                <Clock className="w-5 h-5 text-goodwill-primary" strokeWidth={2} />
                Reminders
              </h1>
              <p className="text-xs text-goodwill-text-muted mt-1">Manage your credential reminders</p>
            </div>
            <select
              value={filter}
              onChange={(e) => setFilter(e.target.value)}
              className="px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
            >
              <option value="all">All Reminders</option>
              <option value="upcoming">Upcoming</option>
              <option value="expiring_soon">Expiring Soon</option>
              <option value="expired">Expired</option>
            </select>
          </div>
        </div>

        <div className="space-y-2">
          {filteredReminders.length === 0 ? (
            <div className="bg-white rounded-lg shadow-sm p-8 text-center border border-goodwill-border/50">
              <Bell className="w-12 h-12 mx-auto mb-3 text-goodwill-text-muted/50" />
              <p className="text-xs font-medium text-goodwill-text-muted">No reminders found</p>
            </div>
          ) : (
            filteredReminders.map((reminder) => (
              <div
                key={reminder.id}
                className={`bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50 transition-all ${
                  reminder.completed ? 'opacity-60' : ''
                }`}
              >
                <div className="flex items-start justify-between">
                  <div className="flex items-start gap-3 flex-1">
                    <div className={`p-2 rounded-lg ${
                      reminder.status === 'expired' ? 'bg-red-100' :
                      reminder.status === 'expiring_soon' ? 'bg-yellow-100' :
                      'bg-green-100'
                    }`}>
                      <Calendar className={`w-4 h-4 ${
                        reminder.status === 'expired' ? 'text-red-600' :
                        reminder.status === 'expiring_soon' ? 'text-yellow-600' :
                        'text-green-600'
                      }`} />
                    </div>
                    <div className="flex-1">
                      <h3 className="text-sm font-semibold text-goodwill-dark">{reminder.credential}</h3>
                      <p className="text-xs text-goodwill-text-muted mt-0.5">{reminder.type}</p>
                      <div className="flex items-center gap-3 mt-2">
                        <span className="text-xs text-goodwill-text-muted">
                          Expires: {new Date(reminder.expiryDate).toLocaleDateString()}
                        </span>
                        <span className={`text-xs font-medium ${
                          reminder.daysUntil <= 0 ? 'text-red-600' :
                          reminder.daysUntil <= 30 ? 'text-yellow-600' :
                          'text-green-600'
                        }`}>
                          {reminder.daysUntil <= 0 
                            ? 'Expired' 
                            : `${reminder.daysUntil} day${reminder.daysUntil !== 1 ? 's' : ''} remaining`}
                        </span>
                      </div>
                    </div>
                  </div>
                  <button
                    onClick={() => markAsCompleted(reminder.id)}
                    className={`p-2 rounded-lg transition-colors ${
                      reminder.completed 
                        ? 'bg-green-100 text-green-600' 
                        : 'bg-goodwill-light text-goodwill-text-muted hover:bg-green-100 hover:text-green-600'
                    }`}
                    title={reminder.completed ? 'Mark as incomplete' : 'Mark as completed'}
                  >
                    {reminder.completed ? (
                      <CheckCircle className="w-4 h-4" />
                    ) : (
                      <XCircle className="w-4 h-4" />
                    )}
                  </button>
                </div>
              </div>
            ))
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

export default Reminders;

