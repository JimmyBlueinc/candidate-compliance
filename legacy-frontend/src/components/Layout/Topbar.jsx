import React from 'react';
import { useAuth } from '../../contexts/AuthContext';
import { useNavigate } from 'react-router-dom';
import { Plus, User, LogOut } from 'lucide-react';
import NotificationDropdown from '../NotificationDropdown';

const Topbar = ({ onAddClick }) => {
  const { user, logout, isAdmin } = useAuth();
  const navigate = useNavigate();
  
  // Force re-render when user data changes by using a more specific key
  // Include avatar_url in the key to force re-render when avatar changes
  const avatarKey = user ? `avatar-${user.id}-${user.name}-${user.avatar_url || 'none'}-${user.updated_at || Date.now()}` : 'avatar-none';
  
  // Log user data changes for debugging
  React.useEffect(() => {
    if (user) {
      console.log('Topbar - User data:', {
        id: user.id,
        name: user.name,
        avatar_url: user.avatar_url,
        updated_at: user.updated_at,
      });
    }
  }, [user?.id, user?.avatar_url, user?.updated_at]);

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  return (
    <div className="backdrop-blur-md bg-goodwill-light/95 border-b border-goodwill-border shadow-soft relative z-50">
      <div className="px-6 py-5 max-w-7xl mx-auto flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-goodwill-dark tracking-tight">Compliance & Security Tracker</h2>
          <p className="text-sm text-goodwill-text-muted mt-1.5">
            {user && (
              <span className="inline-flex items-center gap-2.5">
                <span className="inline-flex h-6 px-2.5 rounded-full text-xs font-semibold bg-goodwill-primary/10 text-goodwill-primary border border-goodwill-primary/20">
                  {user.role}
                </span>
                <span className="text-goodwill-text-muted">Welcome,</span> <span className="text-goodwill-dark font-semibold">{user.name}</span>
              </span>
            )}
          </p>
        </div>
        <div className="flex items-center gap-3">
          <NotificationDropdown />
          {isAdmin && onAddClick && (
            <button
              onClick={onAddClick}
              className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-goodwill-primary text-white font-semibold shadow-medium hover:shadow-large hover:bg-goodwill-primary/90 transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98]"
            >
              <Plus className="w-5 h-5 text-white" strokeWidth={2.5} />
              <span>Add Credential</span>
            </button>
          )}
          {/* User Avatar Display */}
          <div className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-goodwill-border bg-goodwill-light">
            {user?.avatar_url ? (
              <img
                key={avatarKey}
                src={(() => {
                  let avatarUrl = user.avatar_url;
                  if (!avatarUrl.startsWith('http') && !avatarUrl.startsWith('data:')) {
                    const apiBase = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';
                    const baseUrl = apiBase.replace('/api', '');
                    avatarUrl = avatarUrl.startsWith('/') 
                      ? `${baseUrl}${avatarUrl}`
                      : `${baseUrl}/${avatarUrl}`;
                  }
                  // Add cache-busting parameter if not already present
                  if (avatarUrl && !avatarUrl.includes('?v=') && !avatarUrl.includes('&v=')) {
                    const separator = avatarUrl.includes('?') ? '&' : '?';
                    avatarUrl = `${avatarUrl}${separator}v=${Date.now()}`;
                  }
                  return avatarUrl;
                })()}
                alt={user.name}
                className="w-7 h-7 rounded-full object-cover ring-2 ring-goodwill-border"
                style={{ display: 'block' }}
                onError={(e) => {
                  console.error('Avatar image failed to load:', user.avatar_url);
                  e.target.style.display = 'none';
                }}
                onLoad={(e) => {
                  e.target.style.display = 'block';
                }}
              />
            ) : (
              <div className="w-7 h-7 rounded-full bg-gradient-to-br from-goodwill-primary to-goodwill-secondary flex items-center justify-center ring-2 ring-goodwill-border">
                <User className="w-4 h-4 text-white" strokeWidth={2.5} />
              </div>
            )}
            <span className="hidden sm:inline font-medium text-goodwill-dark">{user?.name}</span>
          </div>
          <button
            onClick={handleLogout}
            className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-goodwill-border text-goodwill-dark hover:bg-goodwill-light transition-all duration-200 hover:border-goodwill-primary/30"
          >
            <LogOut className="w-4 h-4 text-goodwill-dark" strokeWidth={2} />
            <span className="hidden sm:inline font-medium">Logout</span>
          </button>
        </div>
      </div>
    </div>
  );
};

export default Topbar;

