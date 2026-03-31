import React, { useEffect, useState } from 'react';
import { useApi } from '../lib/api';
import { Save } from 'lucide-react';

const Configuration: React.FC = () => {
  const { request } = useApi();
  const [settings, setSettings] = useState<any>({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  useEffect(() => {
    const fetchSettings = async () => {
      try {
        setLoading(true);
        const res = await request('/settings');
        setSettings(res || {});
      } catch (err: any) {
        setError(err.message || 'Failed to load settings');
      } finally {
        setLoading(false);
      }
    };
    fetchSettings();
  }, [request]);

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setError('');
    setSuccess('');
    try {
      await request('/settings', {
        method: 'PUT',
        body: JSON.stringify(settings),
      });
      setSuccess('Settings updated successfully');
    } catch (err: any) {
      setError(err.message || 'Failed to save settings');
    } finally {
      setSaving(false);
    }
  };

  const handleToggle = (key: string) => {
    setSettings({ ...settings, [key]: !settings[key] });
  };

  return (
    <div className="space-y-6 max-w-4xl">
      <div className="glass-dark rounded-3xl border border-white/5 p-6 flex items-center justify-between">
        <div>
          <h2 className="font-display text-2xl text-white">Global Configuration</h2>
          <p className="text-slate-500 text-sm">Configure system-wide preferences and defaults.</p>
        </div>
      </div>

      {error && (
        <div className="p-4 bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-xl">
          {error}
        </div>
      )}
      {success && (
        <div className="p-4 bg-green-500/10 border border-green-500/20 text-green-400 text-sm rounded-xl">
          {success}
        </div>
      )}

      <div className="grid grid-cols-1 gap-6">
        <div className="glass-dark rounded-3xl border border-white/5 p-8">
          <form onSubmit={handleSave} className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="space-y-2">
                <label className="text-xs font-bold uppercase tracking-widest text-slate-500">App Name</label>
                <input
                  value={settings.app_name || ''}
                  onChange={(e) => setSettings({ ...settings, app_name: e.target.value })}
                  className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-primary/50 transition-all"
                />
              </div>
              <div className="space-y-2">
                <label className="text-xs font-bold uppercase tracking-widest text-slate-500">Default Currency</label>
                <input
                  value={settings.default_currency || 'CAD'}
                  onChange={(e) => setSettings({ ...settings, default_currency: e.target.value })}
                  className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-primary/50 transition-all"
                />
              </div>
            </div>

            <div className="pt-4 border-t border-white/5">
              <button
                type="submit"
                disabled={saving || loading}
                className="px-6 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-primary/80 transition-all disabled:opacity-50 flex items-center gap-2"
              >
                {saving ? 'Saving...' : 'Save Settings'}
                <Save size={18} />
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
};

const ConfigToggle = ({ title, desc, active }: { title: string, desc: string, active: boolean }) => (
  <div className="flex items-center justify-between gap-8 p-4 rounded-2xl bg-white/5">
    <div>
      <h4 className="text-slate-200 font-semibold">{title}</h4>
      <p className="text-sm text-slate-500">{desc}</p>
    </div>
    <div className={`w-12 h-6 rounded-full relative cursor-pointer transition-all ${active ? 'bg-primary' : 'bg-slate-700'}`}>
      <div className={`absolute top-1 w-4 h-4 rounded-full bg-white transition-all ${active ? 'right-1' : 'left-1'}`}></div>
    </div>
  </div>
);

export default Configuration;
