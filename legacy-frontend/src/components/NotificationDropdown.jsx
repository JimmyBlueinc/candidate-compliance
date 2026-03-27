import { useState, useEffect, useRef } from 'react';
import { Bell, AlertTriangle, X, ChevronRight } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import api from '../config/api';

const NotificationDropdown = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(false);
  const dropdownRef = useRef(null);
  const navigate = useNavigate();

  useEffect(() => {
    fetchNotifications();
    // Close dropdown when clicking outside
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
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
      const notificationItems = expiringCredentials.map(cred => ({
        id: cred.id,
        type: 'expiring_soon',
        title: `${cred.candidate_name} - ${cred.credential_type}`,
        message: `Expires in ${Math.ceil((new Date(cred.expiry_date) - now) / (1000 * 60 * 60 * 24))} days`,
        credentialId: cred.id,
        expiryDate: cred.expiry_date,
      }));

      setNotifications(notificationItems);
    } catch (err) {
      console.error('Error fetching notifications:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleNotificationClick = (notification) => {
    setIsOpen(false);
    navigate('/notifications');
  };

  const unreadCount = notifications.length;

  return (
    <div className="relative" ref={dropdownRef}>
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="relative inline-flex items-center justify-center w-10 h-10 rounded-lg bg-goodwill-light hover:bg-goodwill-primary/10 transition-all duration-200 border border-goodwill-border hover:border-goodwill-primary/30"
        aria-label="Notifications"
      >
        <Bell className="w-5 h-5 text-goodwill-dark" strokeWidth={2} />
        {unreadCount > 0 && (
          <span className="absolute -top-1 -right-1 flex items-center justify-center w-5 h-5 bg-goodwill-secondary text-white text-xs font-bold rounded-full border-2 border-white">
            {unreadCount > 9 ? '9+' : unreadCount}
          </span>
        )}
      </button>

      {isOpen && (
        <>
          {/* Backdrop */}
          <div 
            className="fixed inset-0 z-[9998]" 
            onClick={() => setIsOpen(false)}
          />
          {/* Dropdown */}
          <div className="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-goodwill-border z-[9999] max-h-96 overflow-hidden flex flex-col">
          <div className="p-4 border-b border-goodwill-border/50 bg-goodwill-primary/5">
            <div className="flex items-center justify-between">
              <h3 className="text-sm font-semibold text-goodwill-dark flex items-center gap-2">
                <Bell className="w-4 h-4 text-goodwill-primary" />
                Notifications
              </h3>
              <button
                onClick={() => setIsOpen(false)}
                className="text-goodwill-text-muted hover:text-goodwill-dark transition-colors"
              >
                <X className="w-4 h-4" />
              </button>
            </div>
          </div>

          <div className="overflow-y-auto flex-1">
            {loading ? (
              <div className="p-8 text-center">
                <div className="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-goodwill-primary"></div>
                <p className="text-xs text-goodwill-text-muted mt-2">Loading...</p>
              </div>
            ) : notifications.length === 0 ? (
              <div className="p-8 text-center">
                <Bell className="w-8 h-8 mx-auto mb-2 text-goodwill-text-muted/50" />
                <p className="text-xs text-goodwill-text-muted">No notifications</p>
              </div>
            ) : (
              <div className="divide-y divide-goodwill-border/30">
                {notifications.map((notification) => (
                  <button
                    key={notification.id}
                    onClick={() => handleNotificationClick(notification)}
                    className="w-full p-4 text-left hover:bg-goodwill-light/50 transition-colors group"
                  >
                    <div className="flex items-start gap-3">
                      <div className="flex-shrink-0 mt-0.5">
                        <div className="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center">
                          <AlertTriangle className="w-4 h-4 text-yellow-600" strokeWidth={2} />
                        </div>
                      </div>
                      <div className="flex-1 min-w-0">
                        <p className="text-xs font-semibold text-goodwill-dark truncate">
                          {notification.title}
                        </p>
                        <p className="text-xs text-goodwill-text-muted mt-1">
                          {notification.message}
                        </p>
                        <p className="text-xs text-goodwill-text-muted mt-1">
                          {new Date(notification.expiryDate).toLocaleDateString()}
                        </p>
                      </div>
                      <ChevronRight className="w-4 h-4 text-goodwill-text-muted group-hover:text-goodwill-primary transition-colors flex-shrink-0 mt-1" />
                    </div>
                  </button>
                ))}
              </div>
            )}
          </div>

          {notifications.length > 0 && (
            <div className="p-3 border-t border-goodwill-border/50 bg-goodwill-light/30">
              <button
                onClick={() => {
                  setIsOpen(false);
                  navigate('/notifications');
                }}
                className="w-full text-xs font-medium text-goodwill-primary hover:text-goodwill-primary/80 transition-colors"
              >
                View All Notifications
              </button>
            </div>
          )}
        </div>
        </>
      )}
    </div>
  );
};

export default NotificationDropdown;

