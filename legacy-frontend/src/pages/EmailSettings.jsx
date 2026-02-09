import { useState, useEffect } from 'react';
import { Mail, Save, Send, Settings as SettingsIcon, Loader2 } from 'lucide-react';
import Layout from '../components/Layout/Layout';
import api from '../config/api';
import { useAuth } from '../contexts/AuthContext';

const EmailSettings = () => {
  const { isAdmin } = useAuth();
  const [settings, setSettings] = useState({
    smtp_host: 'smtp.gmail.com',
    smtp_port: '587',
    smtp_username: '',
    smtp_password: '',
    from_email: 'noreply@goodwillstaffing.ca',
    from_name: 'Goodwill Staffing',
    reminder_days: [30, 14, 7],
    enable_reminders: true,
    enable_summary: true,
  });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [testing, setTesting] = useState(false);

  useEffect(() => {
    if (isAdmin) {
      fetchSettings();
    }
  }, [isAdmin]);

  const fetchSettings = async () => {
    setLoading(true);
    try {
      const response = await api.get('/email-settings');
      setSettings(response.data);
    } catch (err) {
      console.error('Error fetching email settings:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleSave = async () => {
    setSaving(true);
    try {
      await api.put('/email-settings', settings);
      alert('Email settings saved successfully!');
    } catch (err) {
      alert(err.response?.data?.message || 'Failed to save settings');
    } finally {
      setSaving(false);
    }
  };

  const handleTestEmail = async () => {
    setTesting(true);
    try {
      await api.post('/email-settings/test', { email: settings.from_email });
      alert('Test email sent successfully!');
    } catch (err) {
      alert(err.response?.data?.message || 'Failed to send test email');
    } finally {
      setTesting(false);
    }
  };

  if (!isAdmin) {
    return (
      <Layout>
        <div className="p-8 text-center">
          <p className="text-xs text-goodwill-text-muted">Access denied. Admin privileges required.</p>
        </div>
      </Layout>
    );
  }

  if (loading) {
    return (
      <Layout>
        <div className="p-8 text-center">
          <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-goodwill-primary"></div>
          <p className="mt-3 text-xs text-goodwill-dark font-medium">Loading settings...</p>
        </div>
      </Layout>
    );
  }

  return (
    <Layout>
      <div className="space-y-4 animate-fade-in-up">
        <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
          <h1 className="text-lg font-bold text-goodwill-dark flex items-center gap-2">
            <Mail className="w-5 h-5 text-goodwill-primary" strokeWidth={2} />
            Email Settings
          </h1>
          <p className="text-xs text-goodwill-text-muted mt-1">Configure email server and notification preferences</p>
        </div>

        {/* SMTP Configuration */}
        <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
          <div className="flex items-center gap-2 mb-4">
            <SettingsIcon className="w-4 h-4 text-goodwill-primary" />
            <h3 className="text-sm font-semibold text-goodwill-dark">SMTP Configuration</h3>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label className="block text-xs font-semibold text-goodwill-dark mb-1.5">SMTP Host</label>
              <input
                type="text"
                value={settings.smtp_host}
                onChange={(e) => setSettings({ ...settings, smtp_host: e.target.value })}
                className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
              />
            </div>
            <div>
              <label className="block text-xs font-semibold text-goodwill-dark mb-1.5">SMTP Port</label>
              <input
                type="text"
                value={settings.smtp_port}
                onChange={(e) => setSettings({ ...settings, smtp_port: e.target.value })}
                className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
              />
            </div>
            <div>
              <label className="block text-xs font-semibold text-goodwill-dark mb-1.5">SMTP Username</label>
              <input
                type="text"
                value={settings.smtp_username}
                onChange={(e) => setSettings({ ...settings, smtp_username: e.target.value })}
                className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
              />
            </div>
            <div>
              <label className="block text-xs font-semibold text-goodwill-dark mb-1.5">SMTP Password</label>
              <input
                type="password"
                value={settings.smtp_password === '***hidden***' ? '' : settings.smtp_password}
                onChange={(e) => setSettings({ ...settings, smtp_password: e.target.value })}
                placeholder={settings.smtp_password === '***hidden***' ? 'Password is hidden' : ''}
                className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
              />
            </div>
            <div>
              <label className="block text-xs font-semibold text-goodwill-dark mb-1.5">From Email</label>
              <input
                type="email"
                value={settings.from_email}
                onChange={(e) => setSettings({ ...settings, from_email: e.target.value })}
                className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
              />
            </div>
            <div>
              <label className="block text-xs font-semibold text-goodwill-dark mb-1.5">From Name</label>
              <input
                type="text"
                value={settings.from_name}
                onChange={(e) => setSettings({ ...settings, from_name: e.target.value })}
                className="w-full px-3 py-2 border border-goodwill-border/50 rounded-lg text-xs"
              />
            </div>
          </div>
        </div>

        {/* Notification Settings */}
        <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
          <h3 className="text-sm font-semibold text-goodwill-dark mb-4">Notification Settings</h3>
          <div className="space-y-3">
            <div className="flex items-center justify-between">
              <div>
                <label className="text-xs font-semibold text-goodwill-dark">Enable Reminder Emails</label>
                <p className="text-xs text-goodwill-text-muted">Send automatic reminder emails</p>
              </div>
              <input
                type="checkbox"
                checked={settings.enable_reminders}
                onChange={(e) => setSettings({ ...settings, enable_reminders: e.target.checked })}
                className="w-4 h-4 text-goodwill-primary"
              />
            </div>
            <div className="flex items-center justify-between">
              <div>
                <label className="text-xs font-semibold text-goodwill-dark">Enable Summary Emails</label>
                <p className="text-xs text-goodwill-text-muted">Send daily summary emails to admins</p>
              </div>
              <input
                type="checkbox"
                checked={settings.enable_summary}
                onChange={(e) => setSettings({ ...settings, enable_summary: e.target.checked })}
                className="w-4 h-4 text-goodwill-primary"
              />
            </div>
            <div>
              <label className="block text-xs font-semibold text-goodwill-dark mb-2">Reminder Days</label>
              <div className="flex items-center gap-2">
                {[30, 14, 7].map((day) => (
                  <label key={day} className="flex items-center gap-1.5">
                    <input
                      type="checkbox"
                      checked={(settings.reminder_days || []).includes(day)}
                      onChange={(e) => {
                        const currentDays = settings.reminder_days || [];
                        if (e.target.checked) {
                          setSettings({ ...settings, reminder_days: [...currentDays, day] });
                        } else {
                          setSettings({ ...settings, reminder_days: currentDays.filter(d => d !== day) });
                        }
                      }}
                      className="w-4 h-4 text-goodwill-primary"
                    />
                    <span className="text-xs text-goodwill-dark">{day} days</span>
                  </label>
                ))}
              </div>
            </div>
          </div>
        </div>

        {/* Actions */}
        <div className="bg-white rounded-lg shadow-sm p-4 border border-goodwill-border/50">
          <div className="flex items-center gap-2">
            <button
              onClick={handleSave}
              disabled={saving}
              className="px-4 py-2 bg-goodwill-primary text-white rounded-lg text-xs font-medium hover:bg-goodwill-primary/90 transition-all flex items-center gap-1.5 disabled:opacity-50"
            >
              <Save className="w-4 h-4" />
              {saving ? 'Saving...' : 'Save Settings'}
            </button>
            <button
              onClick={handleTestEmail}
              disabled={testing}
              className="px-4 py-2 bg-goodwill-light border border-goodwill-border text-goodwill-dark rounded-lg text-xs font-medium hover:bg-goodwill-primary hover:text-white transition-all flex items-center gap-1.5 disabled:opacity-50"
            >
              <Send className="w-4 h-4" />
              {testing ? 'Sending...' : 'Send Test Email'}
            </button>
          </div>
        </div>
      </div>
    </Layout>
  );
};

export default EmailSettings;

