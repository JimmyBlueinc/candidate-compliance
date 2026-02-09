import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { useApi } from '../lib/api';
import { User, Mail, Lock, Loader2, ArrowRight } from 'lucide-react';

const Register: React.FC = () => {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'candidate',
  });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();
  const { login: authLogin } = useAuth();
  const { request } = useApi();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (formData.password !== formData.password_confirmation) {
      return setError('Passwords do not match');
    }

    setLoading(true);
    setError('');

    try {
      const data = await request('/register', {
        method: 'POST',
        body: JSON.stringify({
          ...formData,
          role: 'candidate', // Force candidate role for public registration
        }),
      });

      if (data.token && data.user) {
        authLogin(data.token, data.user);
        navigate('/dashboard');
      } else {
        setError('Invalid response from server.');
      }
    } catch (err: any) {
      setError(err.message || 'Registration failed. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#050507] text-white flex items-center justify-center px-6 py-12 selection:bg-purple-500/30 relative overflow-hidden">
      {/* Background ambient orbs */}
      <div className="absolute w-[500px] h-[500px] bg-purple-600/10 top-[-200px] right-[-150px] rounded-full blur-[120px] animate-pulse pointer-events-none" />
      <div className="absolute w-[600px] h-[600px] bg-blue-600/10 bottom-[-250px] left-[-200px] rounded-full blur-[150px] animate-pulse pointer-events-none" style={{ animationDelay: '1s' }} />

      <div className="w-full max-w-[480px] space-y-10 relative z-10 animate-[fadeInUp_0.7s_ease-out_both]">
        <div className="text-center space-y-4">
          <div className="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-tr from-purple-600 to-blue-400 rounded-[24px] shadow-2xl shadow-purple-500/30 mb-4 ring-1 ring-white/20">
            <User size={40} className="text-white" />
          </div>
          <h1 className="text-4xl font-display font-bold tracking-tight text-white leading-tight">Create Account</h1>
          <p className="text-slate-400 text-sm font-medium max-w-[320px] mx-auto leading-relaxed">Join HealthFlow to manage your staffing compliance.</p>
        </div>

        <form onSubmit={handleSubmit} className="glass-dark p-10 rounded-[40px] space-y-6 border border-white/5 shadow-2xl relative">
          <div className="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 px-4 py-1 bg-white/5 border border-white/10 rounded-full backdrop-blur-md">
            <span className="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Personnel Enrollment</span>
          </div>

          {error && (
            <div className="p-4 bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-bold rounded-2xl flex items-center gap-3">
              <span className="material-symbols-outlined text-sm">error</span>
              {error}
            </div>
          )}

          <div className="space-y-5">
            <div className="space-y-2">
              <label className="text-[11px] font-black text-slate-500 uppercase tracking-widest ml-1">Full Name</label>
              <div className="relative group">
                <div className="absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-purple-400 transition-colors">
                  <User size={18} />
                </div>
                <input
                  type="text"
                  required
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  className="w-full bg-white/[0.03] border border-white/5 rounded-2xl pl-14 pr-6 py-4 text-white placeholder:text-slate-600 focus:outline-none focus:border-purple-500/50 focus:bg-white/[0.06] transition-all duration-300"
                  placeholder="John Doe"
                />
              </div>
            </div>

            <div className="space-y-2">
              <label className="text-[11px] font-black text-slate-500 uppercase tracking-widest ml-1">Email Address</label>
              <div className="relative group">
                <div className="absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-purple-400 transition-colors">
                  <Mail size={18} />
                </div>
                <input
                  type="email"
                  required
                  value={formData.email}
                  onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                  className="w-full bg-white/[0.03] border border-white/5 rounded-2xl pl-14 pr-6 py-4 text-white placeholder:text-slate-600 focus:outline-none focus:border-purple-500/50 focus:bg-white/[0.06] transition-all duration-300"
                  placeholder="name@organization.com"
                />
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div className="space-y-2">
                <label className="text-[11px] font-black text-slate-500 uppercase tracking-widest ml-1">Password</label>
                <div className="relative group">
                  <div className="absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-purple-400 transition-colors">
                    <Lock size={18} />
                  </div>
                  <input
                    type="password"
                    required
                    value={formData.password}
                    onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                    className="w-full bg-white/[0.03] border border-white/5 rounded-2xl pl-14 pr-6 py-4 text-white placeholder:text-slate-600 focus:outline-none focus:border-purple-500/50 focus:bg-white/[0.06] transition-all duration-300"
                    placeholder="••••••••"
                  />
                </div>
              </div>

              <div className="space-y-2">
                <label className="text-[11px] font-black text-slate-500 uppercase tracking-widest ml-1">Confirm</label>
                <div className="relative group">
                  <div className="absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-purple-400 transition-colors">
                    <Lock size={18} />
                  </div>
                  <input
                    type="password"
                    required
                    value={formData.password_confirmation}
                    onChange={(e) => setFormData({ ...formData, password_confirmation: e.target.value })}
                    className="w-full bg-white/[0.03] border border-white/5 rounded-2xl pl-14 pr-6 py-4 text-white placeholder:text-slate-600 focus:outline-none focus:border-purple-500/50 focus:bg-white/[0.06] transition-all duration-300"
                    placeholder="••••••••"
                  />
                </div>
              </div>
            </div>
          </div>

          <button
            type="submit"
            disabled={loading}
            className="w-full bg-white text-black font-black py-4 rounded-2xl hover:scale-[1.02] active:scale-[0.98] transition-all duration-300 shadow-xl shadow-white/10 disabled:opacity-50 disabled:hover:scale-100 flex items-center justify-center gap-2 mt-4"
          >
            {loading ? (
              <Loader2 size={20} className="animate-spin" />
            ) : (
              <>
                <span>Create Secure Account</span>
                <ArrowRight size={18} />
              </>
            )}
          </button>

          <div className="pt-4 text-center">
            <span className="text-slate-500 text-xs font-medium">Already registered? </span>
            <button
              type="button"
              onClick={() => navigate('/login')}
              className="text-white hover:text-purple-400 text-xs font-black transition-colors"
            >
              Sign In
            </button>
          </div>
        </form>

        <p className="text-center text-slate-600 text-[10px] font-black uppercase tracking-[0.3em]">
          Secured by HealthFlow Intelligence
        </p>
      </div>
    </div>
  );
};

export default Register;
