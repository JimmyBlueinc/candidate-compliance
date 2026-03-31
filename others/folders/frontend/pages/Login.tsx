import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { useApi } from '../lib/api';

const Login: React.FC = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();
  const { login: authLogin } = useAuth();
  const { request } = useApi();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      const data = await request('/login', {
        method: 'POST',
        body: JSON.stringify({ email, password }),
      });

      if (data.token && data.user) {
        authLogin(data.token, data.user);
        if (data.user?.must_change_password) {
          navigate('/dashboard/change_password');
        } else {
          navigate('/dashboard');
        }
      } else {
        setError('Invalid response from server.');
      }
    } catch (err: any) {
      setError(err.message || 'Login failed. Please check your credentials.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#050507] text-white flex items-center justify-center px-6 py-12 selection:bg-purple-500/30 relative overflow-hidden">
      {/* Background ambient orbs */}
      <div className="absolute w-[500px] h-[500px] bg-purple-600/10 top-[-200px] left-[-150px] rounded-full blur-[120px] animate-pulse pointer-events-none" />
      <div className="absolute w-[600px] h-[600px] bg-blue-600/10 bottom-[-250px] right-[-200px] rounded-full blur-[150px] animate-pulse pointer-events-none" style={{ animationDelay: '1s' }} />

      <div className="w-full max-w-[440px] space-y-10 relative z-10 animate-[fadeInUp_0.7s_ease-out_both]">
        <div className="text-center space-y-4">
          <div className="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-tr from-purple-600 to-blue-400 rounded-[24px] shadow-2xl shadow-purple-500/30 mb-4 ring-1 ring-white/20">
            <span className="text-white font-black text-4xl tracking-tighter italic">H</span>
          </div>
          <h1 className="text-4xl font-display font-bold tracking-tight text-white leading-tight">Welcome Back</h1>
          <p className="text-slate-400 text-sm font-medium max-w-[280px] mx-auto leading-relaxed">Secure access to HealthFlow Intelligence</p>
        </div>

        <form onSubmit={handleSubmit} className="glass-dark p-10 rounded-[40px] space-y-8 border border-white/5 shadow-2xl relative">
          <div className="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 px-4 py-1 bg-white/5 border border-white/10 rounded-full backdrop-blur-md">
            <span className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Secure Authentication</span>
          </div>

          {error && (
            <div className="p-4 bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-bold rounded-2xl flex items-center gap-3">
              <span className="material-symbols-outlined text-sm">error</span>
              {error}
            </div>
          )}

          <div className="space-y-6">
            <div className="space-y-2.5">
              <label className="text-[11px] font-black text-slate-500 uppercase tracking-widest ml-1">Email Address</label>
              <div className="relative group">
                <input
                  type="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="w-full bg-white/[0.03] border border-white/5 rounded-2xl px-6 py-4 text-white placeholder:text-slate-600 focus:outline-none focus:border-purple-500/50 focus:bg-white/[0.06] transition-all duration-300"
                  placeholder="name@organization.com"
                />
                <div className="absolute inset-0 rounded-2xl bg-gradient-to-r from-purple-500/20 to-blue-500/20 opacity-0 group-focus-within:opacity-100 pointer-events-none transition-opacity duration-500"></div>
              </div>
            </div>

            <div className="space-y-2.5">
              <div className="flex justify-between items-center ml-1">
                <label className="text-[11px] font-black text-slate-500 uppercase tracking-widest">Password</label>
                <a href="#" className="text-[10px] font-bold text-purple-400 hover:text-purple-300 transition-colors uppercase tracking-wider">Forgot?</a>
              </div>
              <div className="relative group">
                <input
                  type="password"
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="w-full bg-white/[0.03] border border-white/5 rounded-2xl px-6 py-4 text-white placeholder:text-slate-600 focus:outline-none focus:border-purple-500/50 focus:bg-white/[0.06] transition-all duration-300"
                  placeholder="••••••••"
                />
                <div className="absolute inset-0 rounded-2xl bg-gradient-to-r from-purple-500/20 to-blue-500/20 opacity-0 group-focus-within:opacity-100 pointer-events-none transition-opacity duration-500"></div>
              </div>
            </div>
          </div>

          <button
            type="submit"
            disabled={loading}
            className="w-full bg-white text-black font-black py-4 rounded-2xl hover:scale-[1.02] active:scale-[0.98] transition-all duration-300 shadow-xl shadow-white/10 disabled:opacity-50 disabled:hover:scale-100 flex items-center justify-center gap-2"
          >
            {loading ? (
              <>
                <span className="w-4 h-4 border-2 border-black/20 border-t-black rounded-full animate-spin"></span>
                <span>Authenticating...</span>
              </>
            ) : 'Sign In to Portal'}
          </button>

          <div className="pt-4 text-center">
            <span className="text-slate-500 text-xs font-medium">New to HealthFlow? </span>
            <button
              type="button"
              onClick={() => navigate('/register')}
              className="text-white hover:text-purple-400 text-xs font-black transition-colors"
            >
              Create an Account
            </button>
          </div>
        </form>

        <div className="text-center space-y-6">
          <p className="text-slate-600 text-[10px] font-black uppercase tracking-[0.3em]">Protected by HealthFlow Security</p>
          <div className="flex justify-center gap-6 text-[10px] font-bold text-slate-500 uppercase tracking-widest">
            <a href="#" className="hover:text-white transition-colors">Privacy</a>
            <a href="#" className="hover:text-white transition-colors">Terms</a>
            <a href="#" className="hover:text-white transition-colors">Support</a>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Login;
