import React, { useEffect, useState } from 'react';
import { useApi } from '../lib/api';
import { useAuth } from '../context/AuthContext';
import { Save, User as UserIcon, Mail, Shield, Camera, Loader2 } from 'lucide-react';

const Profile: React.FC = () => {
  const { request } = useApi();
  const { user, login } = useAuth();
  const [formData, setFormData] = useState({
    name: user?.name || '',
    email: user?.email || '',
  });
  const [loading, setLoading] = useState(false);
  const [fetching, setFetching] = useState(true);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [avatar, setAvatar] = useState<File | null>(null);

  useEffect(() => {
    const fetchProfile = async () => {
      try {
        setFetching(true);
        const res = await request('/user');
        setFormData({
          name: res.name || '',
          email: res.email || '',
        });
      } catch (err: any) {
        setError(err.message || 'Failed to load profile');
      } finally {
        setFetching(false);
      }
    };
    fetchProfile();
  }, [request]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    setSuccess('');

    try {
      const data = new FormData();
      data.append('name', formData.name);
      data.append('email', formData.email);
      data.append('_method', 'PUT');
      if (avatar) {
        data.append('avatar', avatar);
      }

      const res = await request('/user/profile', {
        method: 'POST',
        body: data,
      });

      setSuccess('Profile updated successfully');
      // Update local auth state if necessary
      if (res.user) {
        const token = localStorage.getItem('token');
        if (token) login(token, res.user);
      }
    } catch (err: any) {
      setError(err.message || 'Failed to update profile');
    } finally {
      setLoading(false);
    }
  };

  if (fetching) {
    return <div className="p-8 text-slate-500">Loading profile...</div>;
  }

  return (
    <div className="space-y-6 max-w-4xl">
      <div className="glass-dark rounded-3xl border border-white/5 p-6">
        <h2 className="font-display text-2xl text-white">Your Profile</h2>
        <p className="text-slate-500 text-sm">Manage your personal information and account security.</p>
      </div>

      {error && <div className="p-4 bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-xl">{error}</div>}
      {success && <div className="p-4 bg-green-500/10 border border-green-500/20 text-green-400 text-sm rounded-xl">{success}</div>}

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-1 space-y-6">
          <div className="glass-dark rounded-3xl border border-white/5 p-8 flex flex-col items-center text-center">
            <div className="relative group mb-4">
              <div className="w-32 h-32 rounded-full overflow-hidden border-4 border-white/10 group-hover:border-primary/50 transition-all">
                <img 
                  src={`https://ui-avatars.com/api/?name=${formData.name}&background=8B5CF6&color=fff&size=128`}
                  alt="Avatar"
                  className="w-full h-full object-cover"
                />
              </div>
              <label className="absolute bottom-0 right-0 p-2 bg-primary rounded-full cursor-pointer hover:scale-110 transition-all shadow-lg shadow-primary/30">
                <Camera size={18} className="text-white" />
                <input type="file" className="hidden" accept="image/*" onChange={(e) => setAvatar(e.target.files?.[0] || null)} />
              </label>
            </div>
            <h3 className="text-white font-semibold text-lg">{formData.name}</h3>
            <p className="text-slate-500 text-xs uppercase font-bold tracking-widest mt-1">{user?.role}</p>
          </div>
        </div>

        <div className="lg:col-span-2">
          <div className="glass-dark rounded-3xl border border-white/5 p-8">
            <form onSubmit={handleSubmit} className="space-y-6">
              <div className="grid grid-cols-1 gap-6">
                <div className="space-y-2">
                  <label className="text-xs font-bold uppercase tracking-widest text-slate-500">Full Name</label>
                  <div className="relative">
                    <UserIcon size={18} className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500" />
                    <input
                      value={formData.name}
                      onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                      className="w-full bg-white/5 border border-white/10 rounded-xl pl-12 pr-4 py-3 text-sm text-white focus:outline-none focus:border-primary/50 transition-all"
                      required
                    />
                  </div>
                </div>
                <div className="space-y-2">
                  <label className="text-xs font-bold uppercase tracking-widest text-slate-500">Email Address</label>
                  <div className="relative">
                    <Mail size={18} className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500" />
                    <input
                      type="email"
                      value={formData.email}
                      onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                      className="w-full bg-white/5 border border-white/10 rounded-xl pl-12 pr-4 py-3 text-sm text-white focus:outline-none focus:border-primary/50 transition-all"
                      required
                    />
                  </div>
                </div>
              </div>

              <div className="pt-4 border-t border-white/5">
                <button
                  type="submit"
                  disabled={loading}
                  className="px-8 py-3 rounded-xl bg-primary text-white font-semibold hover:bg-primary/80 transition-all disabled:opacity-50 flex items-center gap-2"
                >
                  {loading && <Loader2 size={18} className="animate-spin" />}
                  Save Changes
                  <Save size={18} />
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Profile;
