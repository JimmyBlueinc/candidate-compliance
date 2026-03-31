import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useApi } from '../lib/api';
import { useAuth } from '../context/AuthContext';

const ChangePassword: React.FC = () => {
  const { request } = useApi();
  const { user, login } = useAuth();
  const navigate = useNavigate();

  const [currentPassword, setCurrentPassword] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirm, setPasswordConfirm] = useState('');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();

    try {
      setSaving(true);
      setError('');
      setSuccess('');

      const res: any = await request('/user/password', {
        method: 'PUT',
        body: JSON.stringify({
          current_password: currentPassword,
          password,
          password_confirmation: passwordConfirm,
        }),
      });

      if (res?.user) {
        const token = localStorage.getItem('token');
        if (token) {
          login(token, res.user);
        }
      }

      setSuccess('Password updated successfully');
      setCurrentPassword('');
      setPassword('');
      setPasswordConfirm('');

      setTimeout(() => navigate('/dashboard'), 600);
    } catch (e: any) {
      setError(e?.message || 'Failed to update password');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="space-y-6 max-w-2xl">
      <div className="glass-dark rounded-3xl border border-white/5 p-6">
        <h2 className="font-display text-2xl text-white">Change Password</h2>
        <p className="text-slate-500 text-sm">
          {user?.must_change_password
            ? 'You must change your temporary password before continuing.'
            : 'Update your password.'}
        </p>
      </div>

      {error && (
        <div className="glass-dark rounded-2xl border border-red-500/20 bg-red-500/10 p-4 text-sm text-red-300">
          {error}
        </div>
      )}

      {success && (
        <div className="glass-dark rounded-2xl border border-green-500/20 bg-green-500/10 p-4 text-sm text-green-300">
          {success}
        </div>
      )}

      <div className="glass-dark rounded-3xl border border-white/5 p-8">
        <form onSubmit={submit} className="space-y-4">
          <div className="space-y-2">
            <label className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Current Password</label>
            <input
              type="password"
              className="w-full bg-white/[0.03] border border-white/10 rounded-2xl px-4 py-3 text-white focus:outline-none focus:border-primary/50"
              value={currentPassword}
              onChange={(e) => setCurrentPassword(e.target.value)}
              required
            />
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="space-y-2">
              <label className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">New Password</label>
              <input
                type="password"
                className="w-full bg-white/[0.03] border border-white/10 rounded-2xl px-4 py-3 text-white focus:outline-none focus:border-primary/50"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
              />
            </div>
            <div className="space-y-2">
              <label className="text-[10px] uppercase tracking-[0.25em] text-slate-500 font-black">Confirm</label>
              <input
                type="password"
                className="w-full bg-white/[0.03] border border-white/10 rounded-2xl px-4 py-3 text-white focus:outline-none focus:border-primary/50"
                value={passwordConfirm}
                onChange={(e) => setPasswordConfirm(e.target.value)}
                required
              />
            </div>
          </div>

          <button
            disabled={saving}
            className="w-full bg-white text-black font-black py-3 rounded-2xl hover:scale-[1.01] active:scale-[0.99] transition-all disabled:opacity-50"
          >
            {saving ? 'Saving…' : 'Update Password'}
          </button>
        </form>
      </div>
    </div>
  );
};

export default ChangePassword;
