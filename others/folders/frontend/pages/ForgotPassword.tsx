import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useApi } from '../lib/api';
import { Key, Mail, ArrowLeft, Loader2 } from 'lucide-react';

const ForgotPassword: React.FC = () => {
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const navigate = useNavigate();
  const { request } = useApi();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    setSuccess('');

    try {
      await request('/forgot-password', {
        method: 'POST',
        body: JSON.stringify({ email }),
      });
      setSuccess('If your email is registered, you will receive a reset link shortly.');
    } catch (err: any) {
      setError(err.message || 'Failed to send reset link');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#050507] text-white flex items-center justify-center px-6 py-12 selection:bg-purple-500/30">
      <div className="orb absolute w-[300px] h-[300px] bg-purple-900/10 top-[-150px] left-[-150px] rounded-full blur-[80px] pointer-events-none" />
      <div className="orb absolute w-[400px] h-[400px] bg-blue-900/10 bottom-[-200px] right-[-200px] rounded-full blur-[80px] pointer-events-none" />

      <div className="w-full max-w-md space-y-8 relative z-10 animate-[fadeInUp_0.7s_ease-out_both]">
        <div className="text-center space-y-4">
          <div className="inline-flex items-center justify-center w-16 h-16 bg-white/5 rounded-2xl border border-white/10 mb-2">
            <Key size={32} className="text-primary" />
          </div>
          <h1 className="serif text-3xl font-bold tracking-tight">Reset Password</h1>
          <p className="text-gray-400 text-sm">Enter your email and we'll send you a link to get back into your account.</p>
        </div>

        <form onSubmit={handleSubmit} className="glass p-8 rounded-[32px] space-y-6 border border-white/10">
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

          <div className="space-y-2">
            <label className="text-sm font-medium text-gray-300 ml-1">Email Address</label>
            <div className="relative">
              <Mail size={18} className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-600" />
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full bg-white/5 border border-white/10 rounded-2xl pl-12 pr-5 py-4 text-white placeholder:text-gray-600 focus:outline-none focus:border-purple-500/50 focus:ring-1 focus:ring-purple-500/50 transition-all"
                placeholder="admin@healthflow.com"
              />
            </div>
          </div>

          <button
            type="submit"
            disabled={loading}
            className="w-full bg-white text-black font-bold py-4 rounded-2xl hover:scale-[1.02] active:scale-[0.98] transition-all shadow-xl shadow-white/5 disabled:opacity-50 disabled:hover:scale-100 flex items-center justify-center gap-2"
          >
            {loading && <Loader2 size={18} className="animate-spin" />}
            Send Reset Link
          </button>

          <button
            type="button"
            onClick={() => navigate('/login')}
            className="w-full flex items-center justify-center gap-2 text-sm text-gray-500 hover:text-white transition-colors"
          >
            <ArrowLeft size={16} />
            Back to Login
          </button>
        </form>
      </div>
    </div>
  );
};

export default ForgotPassword;
