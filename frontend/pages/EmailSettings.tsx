import React, { useEffect, useState } from 'react';
import { TestTube } from 'lucide-react';
import { useApi } from '../lib/api';

type Settings = Record<string, any>;

const EmailSettings: React.FC = () => {
  const { request } = useApi();
  const [settings, setSettings] = useState<Settings>({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [testing, setTesting] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    const run = async () => {
      try {
        setLoading(true);
        setError('');
        const res: any = await request('/email-settings');
        setSettings(res || {});
      } catch (e: any) {
        setSettings({});
        setError(e?.message || 'Failed to load email settings');
      } finally {
        setLoading(false);
      }
    };

    run();
  }, [request]);

  const onSave = async () => {
    try {
      setSaving(true);
      setError('');
      await request('/email-settings', {
        method: 'PUT',
        body: JSON.stringify(settings),
      });
    } catch (e: any) {
      setError(e?.message || 'Failed to save email settings');
    } finally {
      setSaving(false);
    }
  };

  const onTest = async () => {
    try {
      setTesting(true);
      setError('');
      await request('/email-settings/test', {
        method: 'POST',
        body: JSON.stringify({ email: settings.from_email || settings.smtp_username || '' }),
      });
    } finally {
      setTesting(false);
    }
  };

  return (
    <div className="space-y-6 max-w-5xl">
      <div className="glass-dark rounded-[32px] border border-white/5 p-8">
        <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
          <div>
            <h2 className="font-display text-2xl text-white">Email Settings</h2>
            <p className="text-slate-500 text-sm">Configure SMTP credentials for outbound system notifications.</p>
          </div>

          <div className="flex gap-3">
            <button
              type="button"
              onClick={onTest}
              disabled={testing || loading}
              className="px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-slate-200 text-sm font-semibold hover:bg-white/10 transition-all disabled:opacity-50 inline-flex items-center gap-2"
            >
              <TestTube size={18} />
              {testing ? 'Testing…' : 'Send Test'}
            </button>
            <button
              type="button"
              onClick={onSave}
              disabled={saving || loading}
              className="px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary/80 transition-all disabled:opacity-50"
            >
              {saving ? 'Saving…' : 'Save Changes'}
            </button>
          </div>
        </div>
      </div>

      {error && (
        <div className="glass-dark rounded-2xl border border-red-500/20 bg-red-500/10 p-4 text-sm text-red-300">
          {error}
        </div>
      )}

      {loading ? (
        <div className="glass-dark rounded-[32px] border border-white/5 p-8 text-slate-500">Loading…</div>
      ) : (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div className="glass-dark rounded-[32px] border border-white/5 p-8">
            <h3 className="font-display text-xl text-white">SMTP</h3>
            <p className="text-slate-500 text-sm mt-1">Connection details to your mail server.</p>

            <div className="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
              <Field label="Host" value={settings.smtp_host || ''} onChange={(v) => setSettings((s) => ({ ...s, smtp_host: v }))} />
              <Field label="Port" value={String(settings.smtp_port || '')} onChange={(v) => setSettings((s) => ({ ...s, smtp_port: v }))} />
              <Field label="Username" value={settings.smtp_username || ''} onChange={(v) => setSettings((s) => ({ ...s, smtp_username: v }))} />
              <Field label="Password" value={settings.smtp_password || ''} onChange={(v) => setSettings((s) => ({ ...s, smtp_password: v }))} type="password" />
            </div>

            <div className="mt-6 p-4 rounded-2xl bg-white/[0.03] border border-white/5">
              <div className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Tip</div>
              <p className="text-slate-400 text-sm mt-2">
                Use an app password or SMTP relay credentials. Don’t use your personal mailbox password.
              </p>
            </div>
          </div>

          <div className="glass-dark rounded-[32px] border border-white/5 p-8">
            <h3 className="font-display text-xl text-white">Sender</h3>
            <p className="text-slate-500 text-sm mt-1">Default “From” identity for outbound emails.</p>

            <div className="mt-6 grid grid-cols-1 gap-4">
              <Field label="From Email" value={settings.from_email || ''} onChange={(v) => setSettings((s) => ({ ...s, from_email: v }))} />
              <Field label="From Name" value={settings.from_name || ''} onChange={(v) => setSettings((s) => ({ ...s, from_name: v }))} />
            </div>

            <div className="mt-6 p-4 rounded-2xl bg-white/[0.03] border border-white/5">
              <div className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Test email</div>
              <p className="text-slate-400 text-sm mt-2">
                We’ll send a test message to the email configured above.
              </p>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

const Field: React.FC<{ label: string; value: string; onChange: (v: string) => void; type?: string }> = ({
  label,
  value,
  onChange,
  type = 'text',
}) => {
  return (
    <div className="space-y-2">
      <label className="text-xs font-bold uppercase tracking-widest text-slate-500">{label}</label>
      <input
        value={value}
        type={type}
        onChange={(e) => onChange(e.target.value)}
        className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:outline-none focus:border-primary/50"
      />
    </div>
  );
};

export default EmailSettings;
