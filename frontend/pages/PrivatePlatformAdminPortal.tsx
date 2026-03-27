import React, { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useApi } from '../lib/api';

const PrivatePlatformAdminPortal: React.FC = () => {
  const navigate = useNavigate();
  const { request } = useApi();

  const [secretKey, setSecretKey] = useState('');
  const [mode, setMode] = useState<'upsert' | 'resetFirst'>('upsert');

  const [name, setName] = useState('Platform Admin');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState<string | null>(null);

  const canSubmit = useMemo(() => {
    if (!secretKey || !password || !passwordConfirmation) return false;
    if (password !== passwordConfirmation) return false;
    if (mode === 'upsert') {
      if (!name || !email) return false;
    }
    return true;
  }, [secretKey, mode, name, email, password, passwordConfirmation]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    setSuccess(null);

    try {
      if (mode === 'upsert') {
        const res = await request('/private/platform-admin/upsert', {
          method: 'POST',
          body: JSON.stringify({
            secret_key: secretKey,
            name,
            email,
            password,
            password_confirmation: passwordConfirmation,
          }),
        });
        setSuccess(`Done. Platform admin: ${res?.user?.email || email}`);
      } else {
        const res = await request('/private/platform-admin/reset-first-password', {
          method: 'POST',
          body: JSON.stringify({
            secret_key: secretKey,
            password,
            password_confirmation: passwordConfirmation,
          }),
        });
        setSuccess(`Done. Reset password for: ${res?.user?.email || 'platform admin'}`);
      }

      setTimeout(() => navigate('/login'), 800);
    } catch (err: any) {
      setError(err?.message || 'Request failed');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#050507] text-white flex items-center justify-center px-6 py-12 selection:bg-purple-500/30 relative overflow-hidden">
      <div className="absolute w-[500px] h-[500px] bg-purple-600/10 top-[-200px] left-[-150px] rounded-full blur-[120px] animate-pulse pointer-events-none" />
      <div className="absolute w-[600px] h-[600px] bg-blue-600/10 bottom-[-250px] right-[-200px] rounded-full blur-[150px] animate-pulse pointer-events-none" style={{ animationDelay: '1s' }} />

      <div className="w-full max-w-[560px] space-y-8 relative z-10 animate-[fadeInUp_0.7s_ease-out_both]">
        <div className="text-center space-y-3">
          <div className="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-tr from-purple-600 to-blue-400 rounded-[20px] shadow-2xl shadow-purple-500/30 mb-2 ring-1 ring-white/20">
            <span className="text-white font-black text-3xl tracking-tighter">PA</span>
          </div>
          <h1 className="text-3xl font-display font-bold tracking-tight text-white leading-tight">Private Platform Admin Portal</h1>
          <p className="text-slate-400 text-xs font-medium max-w-[440px] mx-auto leading-relaxed">Secret-key protected bootstrap/reset for platform admin credentials.</p>
        </div>

        <form onSubmit={handleSubmit} className="glass-dark p-8 rounded-[32px] space-y-6 border border-white/5 shadow-2xl relative">
          {error && (
            <div className="p-4 bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-bold rounded-2xl flex items-center gap-3">
              <span className="material-symbols-outlined text-sm">error</span>
              {error}
            </div>
          )}

          {success && (
            <div className="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-xs font-bold rounded-2xl flex items-center gap-3">
              <span className="material-symbols-outlined text-sm">check_circle</span>
              {success}
            </div>
          )}

          <div className="space-y-2">
            <label className="text-[11px] font-black text-slate-500 uppercase tracking-widest ml-1">Mode</label>
            <div className="grid grid-cols-2 gap-3">
              <button
                type="button"
                onClick={() => setMode('upsert')}
                className={`px-4 py-3 rounded-2xl border text-xs font-black uppercase tracking-widest transition-all ${
                  mode === 'upsert'
                    ? 'bg-white/10 border-white/20 text-white'
                    : 'bg-white/[0.03] border-white/5 text-slate-400 hover:text-white'
                }`}
              >
                Create / Update
              </button>
              <button
                type="button"
                onClick={() => setMode('resetFirst')}
                className={`px-4 py-3 rounded-2xl border text-xs font-black uppercase tracking-widest transition-all ${
                  mode === 'resetFirst'
                    ? 'bg-white/10 border-white/20 text-white'
                    : 'bg-white/[0.03] border-white/5 text-slate-400 hover:text-white'
                }`}
              >
                Reset First Admin
              </button>
            </div>
          </div>

          <div className="space-y-2">
            <label className="text-[11px] font-black text-slate-500 uppercase tracking-widest ml-1">Secret Key</label>
            <input
              type="password"
              required
              value={secretKey}
              onChange={(e) => setSecretKey(e.target.value)}
              className="w-full bg-white/[0.03] border border-white/5 rounded-2xl px-5 py-3 text-white placeholder:text-slate-600 focus:outline-none focus:border-purple-500/50 focus:bg-white/[0.06] transition-all duration-300"
              placeholder="SUPER_ADMIN_SECRET_KEY"
              autoComplete="off"
            />
          </div>

          {mode === 'upsert' && (
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div className="space-y-2">
                <label className="text-[11px] font-black text-slate-500 uppercase tracking-widest ml-1">Name</label>
                <input
                  type="text"
                  required
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  className="w-full bg-white/[0.03] border border-white/5 rounded-2xl px-5 py-3 text-white placeholder:text-slate-600 focus:outline-none focus:border-purple-500/50 focus:bg-white/[0.06] transition-all duration-300"
                  placeholder="Platform Admin"
                />
              </div>
              <div className="space-y-2">
                <label className="text-[11px] font-black text-slate-500 uppercase tracking-widest ml-1">Email</label>
                <input
                  type="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="w-full bg-white/[0.03] border border-white/5 rounded-2xl px-5 py-3 text-white placeholder:text-slate-600 focus:outline-none focus:border-purple-500/50 focus:bg-white/[0.06] transition-all duration-300"
                  placeholder="platform-admin@yourdomain.com"
                />
              </div>
            </div>
          )}

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div className="space-y-2">
              <label className="text-[11px] font-black text-slate-500 uppercase tracking-widest ml-1">Password</label>
              <input
                type="password"
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full bg-white/[0.03] border border-white/5 rounded-2xl px-5 py-3 text-white placeholder:text-slate-600 focus:outline-none focus:border-purple-500/50 focus:bg-white/[0.06] transition-all duration-300"
                placeholder="••••••••"
                autoComplete="new-password"
              />
            </div>
            <div className="space-y-2">
              <label className="text-[11px] font-black text-slate-500 uppercase tracking-widest ml-1">Confirm Password</label>
              <input
                type="password"
                required
                value={passwordConfirmation}
                onChange={(e) => setPasswordConfirmation(e.target.value)}
                className="w-full bg-white/[0.03] border border-white/5 rounded-2xl px-5 py-3 text-white placeholder:text-slate-600 focus:outline-none focus:border-purple-500/50 focus:bg-white/[0.06] transition-all duration-300"
                placeholder="••••••••"
                autoComplete="new-password"
              />
            </div>
          </div>

          <button
            type="submit"
            disabled={loading || !canSubmit}
            className="w-full bg-white text-black font-black py-4 rounded-2xl hover:scale-[1.02] active:scale-[0.98] transition-all duration-300 shadow-xl shadow-white/10 disabled:opacity-50 disabled:hover:scale-100 flex items-center justify-center gap-2"
          >
            {loading ? (
              <>
                <span className="w-4 h-4 border-2 border-black/20 border-t-black rounded-full animate-spin"></span>
                <span>Working...</span>
              </>
            ) : (
              <span>Execute and Go to Login</span>
            )}
          </button>

          <div className="text-center">
            <button
              type="button"
              className="text-slate-400 hover:text-white text-xs font-black transition-colors"
              onClick={() => navigate('/login')}
            >
              Back to Login
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default PrivatePlatformAdminPortal;
