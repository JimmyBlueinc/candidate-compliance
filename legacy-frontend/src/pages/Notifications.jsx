import { useState, useEffect } from 'react';
import { Bell, CheckCircle, AlertTriangle, Info, X, FileText } from 'lucide-react';
import Layout from '../components/Layout/Layout';
import api from '../config/api';
import { useNavigate } from 'react-router-dom';

const Notifications = () => {
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();

  useEffect(() => {
    fetchNotifications();
  }, []);

  const fetchNotifications = async () => {
    try {
      setLoading(true);
      const response = await api.get('/credentials');
      const credentials = response.data.data || response.data || [];
      
      const now = new Date();
      now.setHours(0, 0, 0, 0);
      
      // Filter credentials expiring within 30 days
      const expiringCredentials = credentials.filter(cred => {
        if (!cred.expiry_date) return false;
        const expiryDate = new Date(cred.expiry_date);
        expiryDate.setHours(0, 0, 0, 0);
        const daysUntilExpiry = Math.ceil((expiryDate - now) / (1000 * 60 * 60 * 24));
        return daysUntilExpiry > 0 && daysUntilExpiry <= 30;
      });

      // Create notification items
      const notificationItems = expiringCredentials.map(cred => {
        const expiryDate = new Date(cred.expiry_date);
        expiryDate.setHours(0, 0, 0, 0);
        const daysUntilExpiry = Math.ceil((expiryDate - now) / (1000 * 60 * 60 * 24));
        
        return {
          id: cred.id,
          type: 'warning',
          title: `${cred.candidate_name} - ${cred.credential_type}`,
          message: `Credential expiring in ${daysUntilExpiry} day${daysUntilExpiry !== 1 ? 's' : ''}`,
          time: new Date(cred.expiry_date).toLocaleDateString(),
          read: false,
          credentialId: cred.id,
          expiryDate: cred.expiry_date,
        };
      });

      // Sort by expiry date (soonest first)
      notificationItems.sort((a, b) => new Date(a.expiryDate) - new Date(b.expiryDate));

      setNotifications(notificationItems);
    } catch (err) {
      console.error('Error fetching notifications:', err);
    } finally {
      setLoading(false);
    }
  };

  const markAsRead = (id) => {
    setNotifications(notifications.map(n => n.id === id ? { ...n, read: true } : n));
  };

  const markAllAsRead = () => {
    setNotifications(notifications.map(n => ({ ...n, read: true })));
  };

  const deleteNotification = (id) => {
    setNotifications(notifications.filter(n => n.id !== id));
  };

  const handleNotificationClick = (notification) => {
    markAsRead(notification.id);
    navigate('/credentials/tracker');
  };

  const getIcon = (type) => {
    switch (type) {
      case 'success':
        return <CheckCircle className="w-5 h-5 text-green-600" />;
      case 'warning':
        return <AlertTriangle className="w-5 h-5 text-yellow-600" />;
      case 'error':
        return <AlertTriangle className="w-5 h-5 text-red-600" />;
      default:
        return <Info className="w-5 h-5 text-blue-600" />;
    }
  };

  const unreadCount = notifications.filter(n => !n.read).length;

  return (
    <Layout>
      <div className="space-y-4 animate-fade-in-up">
        <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-lg font-bold text-goodwill-dark flex items-center gap-2">
                <Bell className="w-5 h-5 text-goodwill-primary" strokeWidth={2} />
                Notifications
              </h1>
              <p className="text-xs text-goodwill-text-muted mt-1">
                {unreadCount > 0 ? `${unreadCount} unread notification${unreadCount > 1 ? 's' : ''}` : 'All caught up!'}
              </p>
            </div>
            {unreadCount > 0 && (
              <button
                onClick={markAllAsRead}
                className="px-3 py-1.5 bg-goodwill-primary text-white rounded-lg text-xs font-medium hover:bg-goodwill-primary/90 transition-all"
              >
                Mark all as read
              </button>
            )}
          </div>
        </div>

        <div className="space-y-2">
          {loading ? (
            <div className="bg-white rounded-lg shadow-sm p-8 text-center border border-goodwill-border/50">
              <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-goodwill-primary"></div>
              <p className="text-xs text-goodwill-text-muted mt-2">Loading notifications...</p>
            </div>
          ) : notifications.length === 0 ? (
            <div className="bg-white rounded-lg shadow-sm p-8 text-center border border-goodwill-border/50">
              <Bell className="w-12 h-12 mx-auto mb-3 text-goodwill-text-muted/50" />
              <p className="text-xs font-medium text-goodwill-text-muted">No notifications</p>
              <p className="text-xs text-goodwill-text-muted mt-1">All credentials are up to date</p>
            </div>
          ) : (
            notifications.map((notification) => (
              <button
                key={notification.id}
                onClick={() => handleNotificationClick(notification)}
                className={`w-full text-left bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50 transition-all duration-200 hover:bg-goodwill-light/50 hover:shadow-md ${
                  !notification.read ? 'border-l-4 border-l-yellow-500' : ''
                }`}
              >
                <div className="flex items-start gap-3">
                  <div className="flex-shrink-0 mt-0.5">
                    {getIcon(notification.type)}
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-start justify-between gap-2">
                      <div className="flex-1">
                        <h3 className="text-sm font-semibold text-goodwill-dark">{notification.title}</h3>
                        <p className="text-xs text-goodwill-text-muted mt-1">{notification.message}</p>
                        <p className="text-xs text-goodwill-text-muted mt-2">Expires: {notification.time}</p>
                      </div>
                      <div className="flex items-center gap-1">
                        {!notification.read && (
                          <span className="w-2 h-2 bg-yellow-500 rounded-full"></span>
                        )}
                        <FileText className="w-4 h-4 text-goodwill-text-muted" />
                      </div>
                    </div>
                  </div>
                </div>
              </button>
            ))
          )}
        </div>
      </div>
    </Layout>
  );
};

export default Notifications;

