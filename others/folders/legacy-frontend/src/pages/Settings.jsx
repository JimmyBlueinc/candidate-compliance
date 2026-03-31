import { useState, useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';
import { 
  Settings as SettingsIcon, 
  Save, 
  User, 
  Bell, 
  Shield, 
  Palette,
  RotateCcw,
  CheckCircle,
  AlertCircle,
  Loader,
  Globe,
  Clock,
  Mail,
  Eye,
  EyeOff,
  Lock,
  X
} from 'lucide-react';
import Layout from '../components/Layout/Layout';
import { useAuth } from '../contexts/AuthContext';
import { useTheme } from '../contexts/ThemeContext';
import api from '../config/api';

const Settings = () => {
  const { user, updateUser } = useAuth();
  const { theme: currentTheme, updateTheme } = useTheme();
  const [searchParams, setSearchParams] = useSearchParams();
  const [activeTab, setActiveTab] = useState(() => {
    const tab = searchParams.get('tab');
    return tab && ['general', 'profile', 'notifications', 'security', 'appearance'].includes(tab) ? tab : 'general';
  });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState({ type: '', text: '' });
  
  // Settings state
  const [settings, setSettings] = useState({
    language: 'en',
    timezone: 'UTC',
    theme: 'light',
    notifications_enabled: true,
    email_notifications_enabled: true,
    expiry_reminders_enabled: true,
    reminder_days_before: 30,
  });

  // Profile state
  const [profile, setProfile] = useState({
    name: user?.name || '',
    email: user?.email || '',
    current_password: '',
    password: '',
    password_confirmation: '',
    avatar: null,
  });
  const [avatarPreview, setAvatarPreview] = useState(null);

  // Security state
  const [showCurrentPassword, setShowCurrentPassword] = useState(false);
  const [showNewPassword, setShowNewPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);

  useEffect(() => {
    fetchSettings();
  }, []);

  // Sync theme with ThemeContext
  useEffect(() => {
    if (currentTheme && settings.theme !== currentTheme) {
      setSettings(prev => ({ ...prev, theme: currentTheme }));
    }
  }, [currentTheme]);

  useEffect(() => {
    if (user) {
      setProfile(prev => ({
        ...prev,
        name: user.name || '',
        email: user.email || '',
      }));
      
      // Set avatar preview from user on initial load or when user changes
      if (user.avatar_url) {
        let avatarUrl = user.avatar_url;
        if (!avatarUrl.startsWith('http') && !avatarUrl.startsWith('data:')) {
          const apiBase = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';
          const baseUrl = apiBase.replace('/api', '');
          avatarUrl = avatarUrl.startsWith('/') 
            ? `${baseUrl}${avatarUrl}`
            : `${baseUrl}/${avatarUrl}`;
        }
        // Always update if the URL is different from current preview
        // This ensures the avatar shows after page refresh
        if (avatarPreview !== avatarUrl) {
          setAvatarPreview(avatarUrl);
        }
      } else {
        // Only clear if we don't have a file selected (data URL means file is selected)
        if (!avatarPreview || !avatarPreview.startsWith('data:')) {
          setAvatarPreview(null);
        }
      }
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [user?.id, user?.avatar_url]); // Only depend on user ID and avatar_url, not the whole user object

  const fetchSettings = async () => {
    try {
      setLoading(true);
      const response = await api.get('/settings');
      if (response.data.settings) {
        setSettings({
          language: response.data.settings.language || 'en',
          timezone: response.data.settings.timezone || 'UTC',
          theme: response.data.settings.theme || 'light',
          notifications_enabled: response.data.settings.notifications_enabled ?? true,
          email_notifications_enabled: response.data.settings.email_notifications_enabled ?? true,
          expiry_reminders_enabled: response.data.settings.expiry_reminders_enabled ?? true,
          reminder_days_before: response.data.settings.reminder_days_before || 30,
        });
      }
    } catch (err) {
      console.error('Error fetching settings:', err);
      showMessage('error', 'Failed to load settings');
    } finally {
      setLoading(false);
    }
  };

  const showMessage = (type, text) => {
    setMessage({ type, text });
    setTimeout(() => setMessage({ type: '', text: '' }), 5000);
  };

  const handleSaveSettings = async () => {
    // Validation
    if (settings.reminder_days_before < 1 || settings.reminder_days_before > 365) {
      showMessage('error', 'Reminder days must be between 1 and 365');
      return;
    }

    try {
      setSaving(true);
      const response = await api.put('/settings', settings);
      showMessage('success', 'Settings saved successfully!');
      
      // Apply theme immediately if it changed
      if (settings.theme !== currentTheme) {
        updateTheme(settings.theme);
      }
      
      // Optionally reload settings to ensure sync
      await fetchSettings();
    } catch (err) {
      console.error('Error saving settings:', err);
      let errorMsg = 'Failed to save settings';
      
      if (err.response?.data?.errors) {
        const errors = err.response.data.errors;
        const firstError = Object.values(errors).flat()[0];
        errorMsg = firstError || errorMsg;
      } else if (err.response?.data?.message) {
        errorMsg = err.response.data.message;
      }
      
      showMessage('error', errorMsg);
    } finally {
      setSaving(false);
    }
  };

  const handleThemeChange = (newTheme) => {
    setSettings({ ...settings, theme: newTheme });
    // Apply theme immediately for better UX
    updateTheme(newTheme);
  };

  const handleSaveProfile = async () => {
    // Validation
    if (!profile.name || profile.name.trim() === '') {
      showMessage('error', 'Name is required');
      return;
    }

    if (!profile.email || profile.email.trim() === '') {
      showMessage('error', 'Email is required');
      return;
    }

    // Email format validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(profile.email)) {
      showMessage('error', 'Please enter a valid email address');
      return;
    }

    // Password validation if password is being changed
    if (profile.password || profile.password_confirmation || profile.current_password) {
      if (!profile.current_password) {
        showMessage('error', 'Current password is required to change password');
        return;
      }
      if (!profile.password || profile.password.length < 8) {
        showMessage('error', 'New password must be at least 8 characters');
        return;
      }
      if (profile.password !== profile.password_confirmation) {
        showMessage('error', 'New password and confirmation do not match');
        return;
      }
    }

    try {
      setSaving(true);
      const formData = new FormData();
      
      formData.append('name', profile.name.trim());
      formData.append('email', profile.email.trim());
      
      if (profile.password) {
        formData.append('password', profile.password);
        formData.append('password_confirmation', profile.password_confirmation);
        formData.append('current_password', profile.current_password);
      }
      
      // Log avatar state before appending
      console.log('Avatar state before save:', {
        hasAvatar: !!profile.avatar,
        avatarType: profile.avatar?.constructor?.name,
        avatarName: profile.avatar?.name,
        avatarSize: profile.avatar?.size,
        isFile: profile.avatar instanceof File,
      });
      
      if (profile.avatar && profile.avatar instanceof File) {
        formData.append('avatar', profile.avatar, profile.avatar.name);
        console.log('Avatar appended to FormData:', {
          name: profile.avatar.name,
          size: profile.avatar.size,
          type: profile.avatar.type,
        });
      } else {
        console.warn('No avatar file to append - profile.avatar is:', profile.avatar);
      }
      
      // Log FormData contents for debugging
      console.log('FormData entries:');
      for (let pair of formData.entries()) {
        if (pair[1] instanceof File) {
          console.log(pair[0] + ': ', `File: ${pair[1].name} (${pair[1].size} bytes, ${pair[1].type})`);
        } else {
          console.log(pair[0] + ': ', pair[1]);
        }
      }

      // For FormData uploads, Axios will automatically handle Content-Type
      // The API interceptor removes Content-Type for FormData
      const response = await api.put('/user/profile', formData);

      if (response.data.user) {
        console.log('Profile update response:', response.data.user);
        
        // Update user context with the new data immediately
        const updatedUser = {
          ...response.data.user,
          // Ensure avatar_url is included
          avatar_url: response.data.user.avatar_url || null,
          updated_at: response.data.user.updated_at || null,
        };
        updateUser(updatedUser);
        showMessage('success', 'Profile updated successfully!');
        
        // Update avatar preview if new avatar was uploaded
        if (response.data.user.avatar_url) {
          let avatarUrl = response.data.user.avatar_url;
          // Ensure we have a full URL
          if (!avatarUrl.startsWith('http') && !avatarUrl.startsWith('data:')) {
            const apiBase = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';
            const baseUrl = apiBase.replace('/api', '');
            avatarUrl = avatarUrl.startsWith('/') 
              ? `${baseUrl}${avatarUrl}`
              : `${baseUrl}/${avatarUrl}`;
          }
          console.log('Setting avatar preview to:', avatarUrl);
          setAvatarPreview(avatarUrl);
        } else if (profile.avatar) {
          // If avatar was uploaded but URL not returned, keep the preview
          // The avatar will be saved and URL will be available after refresh
          console.warn('Avatar uploaded but no URL returned in response');
        }
        
        // Reset password fields but keep avatar preview
        // DON'T clear avatar here - it might be needed for the next save
        setProfile(prev => ({
          ...prev,
          current_password: '',
          password: '',
          password_confirmation: '',
          // Keep avatar file reference if it was just uploaded
          // Only clear it if we successfully got an avatar_url back
          avatar: response.data.user.avatar_url ? null : prev.avatar,
        }));
        
        // Refresh user data from server to ensure we have the latest avatar_url
        // This ensures the Topbar and other components get the updated avatar
        setTimeout(async () => {
          try {
            const refreshResponse = await api.get('/user');
            console.log('Refreshed user data:', refreshResponse.data.user);
            if (refreshResponse.data.user) {
              const refreshedUser = {
                ...refreshResponse.data.user,
                avatar_url: refreshResponse.data.user.avatar_url || null,
                updated_at: refreshResponse.data.user.updated_at || null,
              };
              updateUser(refreshedUser);
              // Update avatar preview with refreshed data
              if (refreshResponse.data.user.avatar_url) {
                let avatarUrl = refreshResponse.data.user.avatar_url;
                if (!avatarUrl.startsWith('http') && !avatarUrl.startsWith('data:')) {
                  const apiBase = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';
                  const baseUrl = apiBase.replace('/api', '');
                  avatarUrl = avatarUrl.startsWith('/') 
                    ? `${baseUrl}${avatarUrl}`
                    : `${baseUrl}/${avatarUrl}`;
                }
                console.log('Updating avatar preview from refresh:', avatarUrl);
                setAvatarPreview(avatarUrl);
              } else {
                console.warn('No avatar_url in refreshed user data');
              }
            }
          } catch (err) {
            console.error('Error refreshing user data:', err);
          }
        }, 500);
      }
    } catch (err) {
      console.error('Error updating profile:', err);
      let errorMsg = 'Failed to update profile';
      
      if (err.response?.data?.errors) {
        const errors = err.response.data.errors;
        // Get first error message
        const firstError = Object.values(errors).flat()[0];
        errorMsg = firstError || errorMsg;
      } else if (err.response?.data?.message) {
        errorMsg = err.response.data.message;
      }
      
      showMessage('error', errorMsg);
    } finally {
      setSaving(false);
    }
  };

  const handleResetSettings = async () => {
    if (!window.confirm('Are you sure you want to reset all settings to defaults?')) {
      return;
    }

    try {
      setSaving(true);
      await api.post('/settings/reset');
      showMessage('success', 'Settings reset to defaults');
      await fetchSettings();
    } catch (err) {
      console.error('Error resetting settings:', err);
      showMessage('error', 'Failed to reset settings');
    } finally {
      setSaving(false);
    }
  };

  const handleAvatarChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      if (file.size > 2 * 1024 * 1024) {
        showMessage('error', 'Avatar file size must be less than 2MB');
        e.target.value = ''; // Clear the input
        return;
      }
      if (!file.type.startsWith('image/')) {
        showMessage('error', 'Please select an image file');
        e.target.value = ''; // Clear the input
        return;
      }
      
      console.log('Avatar file selected:', {
        name: file.name,
        size: file.size,
        type: file.type,
        lastModified: file.lastModified,
      });
      
      // Store the file in profile state
      setProfile(prev => ({ ...prev, avatar: file }));
      
      // Create preview
      const reader = new FileReader();
      reader.onloadend = () => {
        if (reader.result) {
          setAvatarPreview(reader.result);
          console.log('Avatar preview created');
        }
      };
      reader.onerror = () => {
        showMessage('error', 'Failed to read image file');
      };
      reader.readAsDataURL(file);
    } else {
      console.warn('No file selected');
    }
  };

  const handleRemoveAvatar = () => {
    setProfile(prev => ({ ...prev, avatar: null }));
    // Reset to user's current avatar or null
    if (user?.avatar_url) {
      let avatarUrl = user.avatar_url;
      if (!avatarUrl.startsWith('http') && !avatarUrl.startsWith('data:')) {
        const apiBase = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';
        const baseUrl = apiBase.replace('/api', '');
        avatarUrl = avatarUrl.startsWith('/') 
          ? `${baseUrl}${avatarUrl}`
          : `${baseUrl}/${avatarUrl}`;
      }
      setAvatarPreview(avatarUrl);
    } else {
      setAvatarPreview(null);
    }
  };

  const tabs = [
    { id: 'general', name: 'General', icon: <SettingsIcon className="w-4 h-4" /> },
    { id: 'profile', name: 'Profile', icon: <User className="w-4 h-4" /> },
    { id: 'notifications', name: 'Notifications', icon: <Bell className="w-4 h-4" /> },
    { id: 'security', name: 'Security', icon: <Shield className="w-4 h-4" /> },
    { id: 'appearance', name: 'Appearance', icon: <Palette className="w-4 h-4" /> },
  ];

  const handleTabChange = (tabId) => {
    setActiveTab(tabId);
    setSearchParams({ tab: tabId });
  };

  if (loading) {
    return (
      <Layout>
        <div className="flex items-center justify-center min-h-[400px]">
          <div className="text-center">
            <Loader className="w-8 h-8 animate-spin text-goodwill-primary mx-auto mb-4" />
            <p className="text-sm text-goodwill-text-muted">Loading settings...</p>
          </div>
        </div>
      </Layout>
    );
  }

  return (
    <Layout>
      <div className="space-y-6 animate-fade-in-up">
        {/* Header */}
        <div className="bg-gradient-to-r from-goodwill-primary via-goodwill-primary to-goodwill-primary/90 rounded-xl shadow-lg border border-goodwill-primary/20 p-6 text-white">
          <div className="flex items-center gap-4">
            <div className="h-12 w-12 rounded-xl bg-white/20 backdrop-blur-sm shadow-lg flex items-center justify-center border border-white/30">
              <SettingsIcon className="w-6 h-6 text-white" strokeWidth={2.5} />
            </div>
            <div>
              <h1 className="text-2xl font-bold tracking-tight">Settings</h1>
              <p className="text-white/80 text-sm mt-1">Manage your application settings and preferences</p>
            </div>
          </div>
        </div>

        {/* Message Alert */}
        {message.text && (
          <div className={`rounded-lg p-4 border-l-4 flex items-center gap-3 animate-fade-in ${
            message.type === 'success' 
              ? 'bg-green-50 border-green-500 text-green-800' 
              : 'bg-red-50 border-red-500 text-red-800'
          }`}>
            {message.type === 'success' ? (
              <CheckCircle className="w-5 h-5 flex-shrink-0" />
            ) : (
              <AlertCircle className="w-5 h-5 flex-shrink-0" />
            )}
            <p className="text-sm font-medium">{message.text}</p>
          </div>
        )}

        {/* Settings Content */}
        <div className="bg-white rounded-xl shadow-lg border border-goodwill-border/50 overflow-hidden">
          {/* Tabs */}
          <div className="flex border-b border-goodwill-border/50 overflow-x-auto">
            {tabs.map((tab) => (
              <button
                key={tab.id}
                onClick={() => handleTabChange(tab.id)}
                className={`flex items-center gap-2 px-6 py-4 text-sm font-medium transition-all duration-200 whitespace-nowrap ${
                  activeTab === tab.id
                    ? 'bg-goodwill-primary text-white border-b-2 border-goodwill-primary'
                    : 'text-goodwill-text-muted hover:bg-goodwill-light/50'
                }`}
              >
                {tab.icon}
                {tab.name}
              </button>
            ))}
          </div>

          {/* Tab Content */}
          <div className="p-6">
            {/* General Tab */}
            {activeTab === 'general' && (
              <div className="space-y-6">
                <div>
                  <label className="block text-sm font-semibold text-goodwill-dark mb-2 flex items-center gap-2">
                    <Globe className="w-4 h-4 text-goodwill-primary" />
                    Language
                  </label>
                  <select
                    value={settings.language}
                    onChange={(e) => setSettings({ ...settings, language: e.target.value })}
                    className="w-full px-4 py-2.5 border border-goodwill-border/50 rounded-lg text-sm focus:ring-2 focus:ring-goodwill-primary focus:border-transparent bg-white text-goodwill-dark"
                  >
                    <option value="en">English</option>
                    <option value="es">Spanish</option>
                    <option value="fr">French</option>
                  </select>
                </div>

                <div>
                  <label className="block text-sm font-semibold text-goodwill-dark mb-2 flex items-center gap-2">
                    <Clock className="w-4 h-4 text-goodwill-primary" />
                    Timezone
                  </label>
                  <select
                    value={settings.timezone}
                    onChange={(e) => setSettings({ ...settings, timezone: e.target.value })}
                    className="w-full px-4 py-2.5 border border-goodwill-border/50 rounded-lg text-sm focus:ring-2 focus:ring-goodwill-primary focus:border-transparent bg-white text-goodwill-dark"
                  >
                    <option value="UTC">UTC</option>
                    <option value="America/New_York">Eastern Time (ET)</option>
                    <option value="America/Chicago">Central Time (CT)</option>
                    <option value="America/Denver">Mountain Time (MT)</option>
                    <option value="America/Los_Angeles">Pacific Time (PT)</option>
                    <option value="America/Toronto">Toronto (ET)</option>
                    <option value="America/Vancouver">Vancouver (PT)</option>
                  </select>
                </div>

                <div className="pt-4 border-t border-goodwill-border/50 flex items-center justify-between">
                  <div>
                    <p className="text-sm font-medium text-goodwill-dark">Reset to Defaults</p>
                    <p className="text-xs text-goodwill-text-muted mt-1">Restore all settings to their default values</p>
                  </div>
                  <button
                    onClick={handleResetSettings}
                    disabled={saving}
                    className="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-2 disabled:opacity-50"
                  >
                    <RotateCcw className="w-4 h-4" />
                    Reset
                  </button>
                </div>
              </div>
            )}

            {/* Profile Tab */}
            {activeTab === 'profile' && (
              <div className="space-y-6">
                {/* Avatar Section */}
                <div className="flex items-start gap-4 pb-6 border-b border-goodwill-border/50">
                  <div className="flex-shrink-0 relative">
                    <div className="w-20 h-20 rounded-full overflow-hidden border-2 border-goodwill-border/50 shadow-sm relative">
                      {avatarPreview ? (
                        <img
                          src={avatarPreview}
                          alt="Avatar"
                          className="w-full h-full object-cover"
                          onError={(e) => {
                            e.target.style.display = 'none';
                            setTimeout(() => {
                              if (!profile.avatar) {
                                setAvatarPreview(null);
                              }
                            }, 100);
                          }}
                          onLoad={(e) => {
                            e.target.style.display = 'block';
                          }}
                        />
                      ) : (
                        <div className="w-full h-full flex items-center justify-center bg-gradient-to-br from-goodwill-primary to-goodwill-secondary">
                          <span className="text-2xl font-bold text-white">
                            {user?.name?.charAt(0).toUpperCase() || 'U'}
                          </span>
                        </div>
                      )}
                    </div>
                    {avatarPreview && (
                      <button
                        type="button"
                        onClick={handleRemoveAvatar}
                        className="absolute -top-1 -right-1 w-6 h-6 bg-goodwill-secondary text-white rounded-full flex items-center justify-center shadow-sm hover:shadow-md transition-all duration-200 hover:scale-110"
                        title="Remove avatar"
                      >
                        <X className="w-3.5 h-3.5" strokeWidth={2.5} />
                      </button>
                    )}
                  </div>
                  <div className="flex-1">
                    <label className="block text-sm font-semibold text-goodwill-dark mb-2">
                      Profile Photo
                    </label>
                    <input
                      type="file"
                      accept="image/*"
                      onChange={handleAvatarChange}
                      className="hidden"
                      id="avatar-upload"
                    />
                    <label
                      htmlFor="avatar-upload"
                      className="px-4 py-2 bg-goodwill-primary/10 hover:bg-goodwill-primary/20 text-goodwill-primary rounded-lg text-sm font-medium cursor-pointer transition-all duration-200 inline-block mb-2"
                    >
                      {profile.avatar ? 'Change Avatar' : 'Upload Avatar'}
                    </label>
                    {profile.avatar && (
                      <p className="text-xs text-goodwill-text-muted">
                        Selected: {profile.avatar.name}
                      </p>
                    )}
                    <p className="text-xs text-goodwill-text-muted mt-2">
                      JPG, PNG or GIF. Maximum file size: 2MB
                    </p>
                  </div>
                </div>

                {/* Basic Information */}
                <div className="space-y-4">
                  <h3 className="text-sm font-semibold text-goodwill-dark border-b border-goodwill-border/50 pb-2">
                    Basic Information
                  </h3>
                  
                  <div>
                    <label className="block text-sm font-semibold text-goodwill-dark mb-2">
                      Full Name <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      value={profile.name}
                      onChange={(e) => setProfile({ ...profile, name: e.target.value })}
                      className="w-full px-4 py-2.5 border border-goodwill-border/50 rounded-lg text-sm focus:ring-2 focus:ring-goodwill-primary focus:border-transparent bg-white text-goodwill-dark"
                      placeholder="Enter your full name"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-semibold text-goodwill-dark mb-2">
                      Email Address <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="email"
                      value={profile.email}
                      onChange={(e) => setProfile({ ...profile, email: e.target.value })}
                      className="w-full px-4 py-2.5 border border-goodwill-border/50 rounded-lg text-sm focus:ring-2 focus:ring-goodwill-primary focus:border-transparent bg-white text-goodwill-dark"
                      placeholder="Enter your email address"
                      required
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-semibold text-goodwill-dark mb-2">
                      Role
                    </label>
                    <input
                      type="text"
                      value={user?.role ? user.role.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'N/A'}
                      disabled
                      className="w-full px-4 py-2.5 border border-goodwill-border/50 rounded-lg text-sm bg-goodwill-light/50 text-goodwill-text-muted cursor-not-allowed"
                    />
                    <p className="text-xs text-goodwill-text-muted mt-1">
                      Your role cannot be changed. Contact an administrator if you need a role change.
                    </p>
                  </div>
                </div>
              </div>
            )}

            {/* Notifications Tab */}
            {activeTab === 'notifications' && (
              <div className="space-y-6">
                <div className="flex items-center justify-between p-4 bg-goodwill-light/50 rounded-lg border border-goodwill-border/50">
                  <div className="flex-1">
                    <label className="text-sm font-semibold text-goodwill-dark flex items-center gap-2">
                      <Bell className="w-4 h-4 text-goodwill-primary" />
                      Enable Notifications
                    </label>
                    <p className="text-xs text-goodwill-text-muted mt-1">Receive in-app notifications</p>
                  </div>
                  <label className="relative inline-flex items-center cursor-pointer">
                    <input
                      type="checkbox"
                      checked={settings.notifications_enabled}
                      onChange={(e) => setSettings({ ...settings, notifications_enabled: e.target.checked })}
                      className="sr-only peer"
                    />
                    <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-goodwill-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-goodwill-primary"></div>
                  </label>
                </div>

                <div className="flex items-center justify-between p-4 bg-goodwill-light/50 rounded-lg border border-goodwill-border/50">
                  <div className="flex-1">
                    <label className="text-sm font-semibold text-goodwill-dark flex items-center gap-2">
                      <Mail className="w-4 h-4 text-goodwill-primary" />
                      Email Notifications
                    </label>
                    <p className="text-xs text-goodwill-text-muted mt-1">Receive email notifications</p>
                  </div>
                  <label className="relative inline-flex items-center cursor-pointer">
                    <input
                      type="checkbox"
                      checked={settings.email_notifications_enabled}
                      onChange={(e) => setSettings({ ...settings, email_notifications_enabled: e.target.checked })}
                      className="sr-only peer"
                    />
                    <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-goodwill-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-goodwill-primary"></div>
                  </label>
                </div>

                <div className="flex items-center justify-between p-4 bg-goodwill-light/50 rounded-lg border border-goodwill-border/50">
                  <div className="flex-1">
                    <label className="text-sm font-semibold text-goodwill-dark flex items-center gap-2">
                      <Bell className="w-4 h-4 text-goodwill-primary" />
                      Expiry Reminders
                    </label>
                    <p className="text-xs text-goodwill-text-muted mt-1">Get reminders before credentials expire</p>
                  </div>
                  <label className="relative inline-flex items-center cursor-pointer">
                    <input
                      type="checkbox"
                      checked={settings.expiry_reminders_enabled}
                      onChange={(e) => setSettings({ ...settings, expiry_reminders_enabled: e.target.checked })}
                      className="sr-only peer"
                    />
                    <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-goodwill-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-goodwill-primary"></div>
                  </label>
                </div>

                {settings.expiry_reminders_enabled && (
                  <div>
                    <label className="block text-sm font-semibold text-goodwill-dark mb-2">
                      Reminder Days Before Expiry
                    </label>
                    <input
                      type="number"
                      min="1"
                      max="365"
                      value={settings.reminder_days_before}
                      onChange={(e) => {
                        const value = parseInt(e.target.value) || 1;
                        const clampedValue = Math.min(Math.max(value, 1), 365);
                        setSettings({ ...settings, reminder_days_before: clampedValue });
                      }}
                      className="w-full px-4 py-2.5 border border-goodwill-border/50 rounded-lg text-sm focus:ring-2 focus:ring-goodwill-primary focus:border-transparent bg-white text-goodwill-dark"
                    />
                    <p className="text-xs text-goodwill-text-muted mt-1">
                      Number of days before expiry to send reminder (1-365)
                    </p>
                    {(settings.reminder_days_before < 1 || settings.reminder_days_before > 365) && (
                      <p className="text-xs text-red-500 mt-1">Value must be between 1 and 365</p>
                    )}
                  </div>
                )}
              </div>
            )}

            {/* Security Tab */}
            {activeTab === 'security' && (
              <div className="space-y-6">
                <div>
                  <label className="block text-sm font-semibold text-goodwill-dark mb-2 flex items-center gap-2">
                    <Lock className="w-4 h-4 text-goodwill-primary" />
                    Current Password
                  </label>
                  <div className="relative">
                    <input
                      type={showCurrentPassword ? 'text' : 'password'}
                      value={profile.current_password}
                      onChange={(e) => setProfile({ ...profile, current_password: e.target.value })}
                      className="w-full px-4 py-2.5 pr-10 border border-goodwill-border/50 rounded-lg text-sm focus:ring-2 focus:ring-goodwill-primary focus:border-transparent bg-white text-goodwill-dark"
                      placeholder="Enter current password"
                    />
                    <button
                      type="button"
                      onClick={() => setShowCurrentPassword(!showCurrentPassword)}
                      className="absolute right-3 top-1/2 transform -translate-y-1/2 text-goodwill-text-muted hover:text-goodwill-primary"
                    >
                      {showCurrentPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                    </button>
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-semibold text-goodwill-dark mb-2">
                    New Password {profile.password && <span className="text-red-500">*</span>}
                  </label>
                  <div className="relative">
                    <input
                      type={showNewPassword ? 'text' : 'password'}
                      value={profile.password}
                      onChange={(e) => setProfile({ ...profile, password: e.target.value })}
                      className="w-full px-4 py-2.5 pr-10 border border-goodwill-border/50 rounded-lg text-sm focus:ring-2 focus:ring-goodwill-primary focus:border-transparent bg-white text-goodwill-dark"
                      placeholder="Enter new password (min 8 characters)"
                      minLength={8}
                    />
                    <button
                      type="button"
                      onClick={() => setShowNewPassword(!showNewPassword)}
                      className="absolute right-3 top-1/2 transform -translate-y-1/2 text-goodwill-text-muted hover:text-goodwill-primary"
                    >
                      {showNewPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                    </button>
                  </div>
                  {profile.password && profile.password.length > 0 && profile.password.length < 8 && (
                    <p className="text-xs text-red-500 mt-1">Password must be at least 8 characters</p>
                  )}
                </div>

                <div>
                  <label className="block text-sm font-semibold text-goodwill-dark mb-2">
                    Confirm New Password {profile.password && <span className="text-red-500">*</span>}
                  </label>
                  <div className="relative">
                    <input
                      type={showConfirmPassword ? 'text' : 'password'}
                      value={profile.password_confirmation}
                      onChange={(e) => setProfile({ ...profile, password_confirmation: e.target.value })}
                      className="w-full px-4 py-2.5 pr-10 border border-goodwill-border/50 rounded-lg text-sm focus:ring-2 focus:ring-goodwill-primary focus:border-transparent bg-white text-goodwill-dark"
                      placeholder="Confirm new password"
                    />
                    <button
                      type="button"
                      onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                      className="absolute right-3 top-1/2 transform -translate-y-1/2 text-goodwill-text-muted hover:text-goodwill-primary"
                    >
                      {showConfirmPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                    </button>
                  </div>
                  {profile.password && profile.password_confirmation && profile.password !== profile.password_confirmation && (
                    <p className="text-xs text-red-500 mt-1">Passwords do not match</p>
                  )}
                </div>

                <div className="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                  <p className="text-xs text-yellow-800">
                    <strong>Note:</strong> Leave password fields empty if you don't want to change your password.
                  </p>
                </div>
              </div>
            )}

            {/* Appearance Tab */}
            {activeTab === 'appearance' && (
              <div className="space-y-6">
                <div>
                  <label className="block text-sm font-semibold text-goodwill-dark mb-2 flex items-center gap-2">
                    <Palette className="w-4 h-4 text-goodwill-primary" />
                    Theme
                  </label>
                  <select
                    value={settings.theme}
                    onChange={(e) => handleThemeChange(e.target.value)}
                    className="w-full px-4 py-2.5 border border-goodwill-border/50 rounded-lg text-sm focus:ring-2 focus:ring-goodwill-primary focus:border-transparent bg-white text-goodwill-dark"
                  >
                    <option value="light">Light</option>
                    <option value="dark">Dark</option>
                    <option value="auto">Auto (System)</option>
                  </select>
                  <p className="text-xs text-goodwill-text-muted mt-2">
                    Theme changes are applied immediately
                  </p>
                </div>
              </div>
            )}

            {/* Save Button */}
            <div className="mt-8 pt-6 border-t border-goodwill-border/50 flex items-center justify-between">
              <p className="text-xs text-goodwill-text-muted">
                {activeTab === 'profile' 
                  ? 'Update your profile information and avatar'
                  : activeTab === 'security'
                  ? 'Change your password to keep your account secure'
                  : activeTab === 'appearance'
                  ? 'Customize the appearance of your application'
                  : 'Save your preferences and settings'
                }
              </p>
              <button
                onClick={activeTab === 'profile' ? handleSaveProfile : handleSaveSettings}
                disabled={saving}
                className="px-6 py-2.5 bg-goodwill-primary text-white rounded-lg text-sm font-semibold hover:bg-goodwill-primary/90 transition-all duration-200 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed hover-lift"
              >
                {saving ? (
                  <>
                    <Loader className="w-4 h-4 animate-spin" />
                    Saving...
                  </>
                ) : (
                  <>
                    <Save className="w-4 h-4" />
                    Save {activeTab === 'profile' ? 'Profile' : 'Settings'}
                  </>
                )}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Layout>
  );
};

export default Settings;
