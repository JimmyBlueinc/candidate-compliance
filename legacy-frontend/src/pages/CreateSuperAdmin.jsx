import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import api from '../config/api';
import { Shield, User, Mail, Lock, Key, AlertCircle, CheckCircle, Activity, HeartPulse, Stethoscope } from 'lucide-react';

const CreateSuperAdmin = () => {
  const navigate = useNavigate();
  const { isSuperAdmin } = useAuth();
  const [formData, setFormData] = useState({
    secret_key: '',
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);

  // Check if user is already super admin (for creating additional super admins)
  const isExistingSuperAdmin = isSuperAdmin;

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: value,
    }));
    // Clear errors when user types
    if (error) setError('');
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    setSuccess(false);

    // Client-side validation
    if (!formData.name || formData.name.trim() === '') {
      setError('Full name is required');
      setLoading(false);
      return;
    }

    if (!formData.email || formData.email.trim() === '') {
      setError('Email is required');
      setLoading(false);
      return;
    }

    if (!isExistingSuperAdmin && (!formData.secret_key || formData.secret_key.trim() === '')) {
      setError('Secret key is required');
      setLoading(false);
      return;
    }

    if (!formData.password || formData.password.length < 8) {
      setError('Password must be at least 8 characters');
      setLoading(false);
      return;
    }

    if (formData.password !== formData.password_confirmation) {
      setError('Passwords do not match');
      setLoading(false);
      return;
    }

    try {
      const payload = isExistingSuperAdmin
        ? {
            name: formData.name,
            email: formData.email,
            password: formData.password,
            password_confirmation: formData.password_confirmation,
          }
        : {
            secret_key: formData.secret_key,
            name: formData.name,
            email: formData.email,
            password: formData.password,
            password_confirmation: formData.password_confirmation,
          };

      // Debug: Log payload (remove secret_key from log for security)
      const debugPayload = { ...payload };
      if (debugPayload.secret_key) {
        debugPayload.secret_key = '***hidden***';
      }
      console.log('Submitting super admin creation:', debugPayload);

      // If existing super admin, the API will use the auth token from headers
      const response = await api.post('/super-admin/create', payload);

      if (response.data.token) {
        // Auto-login if this is the first super admin
        localStorage.setItem('auth_token', response.data.token);
        api.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
        setSuccess(true);
        
        // Redirect after 2 seconds
        setTimeout(() => {
          navigate('/dashboard');
        }, 2000);
      } else {
        setSuccess(true);
        // If logged in as super admin, just show success
        setTimeout(() => {
          navigate('/admin/users');
        }, 2000);
      }
    } catch (err) {
      // Debug: Log full error response
      console.error('Super admin creation error:', {
        status: err.response?.status,
        data: err.response?.data,
        errors: err.response?.data?.errors,
      });

      // Handle validation errors
      if (err.response?.status === 422) {
        const errors = err.response?.data?.errors || {};
        const errorMessages = [];
        
        // Collect all validation error messages
        Object.keys(errors).forEach((key) => {
          if (Array.isArray(errors[key])) {
            errors[key].forEach((msg) => {
              errorMessages.push(`${key}: ${msg}`);
            });
          } else {
            errorMessages.push(`${key}: ${errors[key]}`);
          }
        });
        
        // If we have specific errors, show them; otherwise show generic message
        if (errorMessages.length > 0) {
          setError(errorMessages.join('. '));
        } else {
          setError(err.response?.data?.message || 'Validation failed. Please check your input.');
        }
      } else if (err.response?.status === 403) {
        // Handle 403 Forbidden - super admin already exists
        const message = err.response?.data?.message || 'Unauthorized. Super admin access required.';
        const hint = err.response?.data?.hint || '';
        setError(
          hint 
            ? `${message} ${hint}` 
            : message + ' If you are trying to create the first super admin, a super admin may already exist in the database. Please check the database or contact support.'
        );
      } else {
        // Handle other errors
        setError(
          err.response?.data?.message ||
          err.response?.data?.errors?.secret_key?.[0] ||
          err.response?.data?.errors?.email?.[0] ||
          err.response?.data?.errors?.password?.[0] ||
          err.response?.data?.errors?.password_confirmation?.[0] ||
          'Failed to create super admin account'
        );
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-purple-50 via-white to-blue-50 flex items-center justify-center py-8 px-4">
      <div className="max-w-md w-full">
        <div className="bg-white rounded-lg shadow-lg border border-purple-200/50 overflow-hidden animate-fade-in-up">
          {/* Header */}
          <div className="bg-gradient-to-r from-purple-600 via-purple-500 to-purple-600 px-6 py-4 border-b border-purple-700/30">
            <div className="flex items-center gap-3">
              <div className="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                <Shield className="w-5 h-5 text-white" strokeWidth={2.5} />
              </div>
              <div>
                <h1 className="text-xl font-bold text-white">Create Super Admin</h1>
                <p className="text-white/90 text-xs mt-0.5">
                  {isExistingSuperAdmin 
                    ? 'Create an additional super admin account' 
                    : 'Create the first super admin account'}
                </p>
              </div>
            </div>
          </div>

          {/* Content */}
          <div className="p-6">
            {success ? (
              <div className="text-center py-4 animate-fade-in">
                <CheckCircle className="w-12 h-12 text-green-500 mx-auto mb-3" />
                <h2 className="text-lg font-semibold text-goodwill-dark mb-2">Success!</h2>
                <p className="text-sm text-goodwill-text-muted">
                  Super admin account created successfully.
                  {isExistingSuperAdmin ? ' Redirecting to user management...' : ' Redirecting to dashboard...'}
                </p>
              </div>
            ) : (
              <form onSubmit={handleSubmit} className="space-y-4">
                {/* Secret Key (only for first super admin) */}
                {!isExistingSuperAdmin && (
                  <div className="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-4">
                    <div className="flex items-start gap-2">
                      <AlertCircle className="w-4 h-4 text-purple-600 mt-0.5 flex-shrink-0" />
                      <div className="text-xs text-purple-800">
                        <p className="font-semibold mb-1">First Super Admin Setup</p>
                        <p>You need the secret key to create the first super admin account. Check your backend <code className="bg-purple-100 px-1 rounded">.env</code> file for <code className="bg-purple-100 px-1 rounded">SUPER_ADMIN_SECRET_KEY</code>.</p>
                      </div>
                    </div>
                  </div>
                )}

                {!isExistingSuperAdmin && (
                  <div>
                    <label className="block text-xs font-semibold text-goodwill-dark mb-1.5 flex items-center gap-1.5">
                      <Key className="w-3.5 h-3.5 text-purple-600" />
                      Secret Key
                    </label>
                    <input
                      type="password"
                      name="secret_key"
                      value={formData.secret_key}
                      onChange={handleInputChange}
                      required={!isExistingSuperAdmin}
                      placeholder="Enter secret key"
                      className="w-full px-3 py-2 text-xs border border-purple-300 rounded-lg bg-white text-goodwill-dark focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                    />
                  </div>
                )}

                {/* Full Name */}
                <div>
                  <label className="block text-xs font-semibold text-goodwill-dark mb-1.5 flex items-center gap-1.5">
                    <User className="w-3.5 h-3.5 text-goodwill-primary" />
                    Full Name
                  </label>
                  <input
                    type="text"
                    name="name"
                    value={formData.name}
                    onChange={handleInputChange}
                    required
                    placeholder="Enter full name"
                    className="w-full px-3 py-2 text-xs border border-goodwill-border/50 rounded-lg bg-white text-goodwill-dark focus:ring-2 focus:ring-goodwill-primary focus:border-transparent"
                  />
                </div>

                {/* Email */}
                <div>
                  <label className="block text-xs font-semibold text-goodwill-dark mb-1.5 flex items-center gap-1.5">
                    <Mail className="w-3.5 h-3.5 text-goodwill-primary" />
                    Email Address
                  </label>
                  <input
                    type="email"
                    name="email"
                    value={formData.email}
                    onChange={handleInputChange}
                    required
                    placeholder="Enter email address"
                    className="w-full px-3 py-2 text-xs border border-goodwill-border/50 rounded-lg bg-white text-goodwill-dark focus:ring-2 focus:ring-goodwill-primary focus:border-transparent"
                  />
                </div>

                {/* Password */}
                <div>
                  <label className="block text-xs font-semibold text-goodwill-dark mb-1.5 flex items-center gap-1.5">
                    <Lock className="w-3.5 h-3.5 text-goodwill-primary" />
                    Password
                  </label>
                  <div className="relative">
                    <input
                      type={showPassword ? 'text' : 'password'}
                      name="password"
                      value={formData.password}
                      onChange={handleInputChange}
                      required
                      placeholder="Enter password (min 8 characters)"
                      className="w-full px-3 py-2 pr-10 text-xs border border-goodwill-border/50 rounded-lg bg-white text-goodwill-dark focus:ring-2 focus:ring-goodwill-primary focus:border-transparent"
                    />
                    <button
                      type="button"
                      onClick={() => setShowPassword(!showPassword)}
                      className="absolute right-2 top-1/2 transform -translate-y-1/2 text-goodwill-text-muted hover:text-goodwill-primary"
                    >
                      {showPassword ? (
                        <Lock className="w-3.5 h-3.5" />
                      ) : (
                        <Lock className="w-3.5 h-3.5" />
                      )}
                    </button>
                  </div>
                </div>

                {/* Confirm Password */}
                <div>
                  <label className="block text-xs font-semibold text-goodwill-dark mb-1.5 flex items-center gap-1.5">
                    <Lock className="w-3.5 h-3.5 text-goodwill-primary" />
                    Confirm Password
                  </label>
                  <div className="relative">
                    <input
                      type={showPasswordConfirmation ? 'text' : 'password'}
                      name="password_confirmation"
                      value={formData.password_confirmation}
                      onChange={handleInputChange}
                      required
                      placeholder="Confirm password"
                      className="w-full px-3 py-2 pr-10 text-xs border border-goodwill-border/50 rounded-lg bg-white text-goodwill-dark focus:ring-2 focus:ring-goodwill-primary focus:border-transparent"
                    />
                    <button
                      type="button"
                      onClick={() => setShowPasswordConfirmation(!showPasswordConfirmation)}
                      className="absolute right-2 top-1/2 transform -translate-y-1/2 text-goodwill-text-muted hover:text-goodwill-primary"
                    >
                      {showPasswordConfirmation ? (
                        <Lock className="w-3.5 h-3.5" />
                      ) : (
                        <Lock className="w-3.5 h-3.5" />
                      )}
                    </button>
                  </div>
                </div>

                {/* Error Message */}
                {error && (
                  <div className="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg text-xs animate-fade-in shadow-sm">
                    <div className="flex items-start gap-2">
                      <AlertCircle className="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
                      <div className="flex-1">
                        <p className="text-red-800 font-semibold mb-1">Error:</p>
                        <p className="text-red-700 whitespace-pre-wrap break-words">{error}</p>
                      </div>
                    </div>
                  </div>
                )}

                {/* Submit Button */}
                <div className="pt-2">
                  <button
                    type="submit"
                    disabled={loading}
                    className="w-full bg-gradient-to-r from-purple-600 to-purple-500 text-white px-4 py-2.5 rounded-lg text-xs font-semibold hover:from-purple-700 hover:to-purple-600 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-md hover:shadow-lg"
                  >
                    {loading ? (
                      <>
                        <div className="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                        <span>Creating...</span>
                      </>
                    ) : (
                      <>
                        <Shield className="w-3.5 h-3.5" />
                        <span>Create Super Admin Account</span>
                      </>
                    )}
                  </button>
                </div>

                {/* Back to Login */}
                <div className="text-center pt-2">
                  <button
                    type="button"
                    onClick={() => navigate(isExistingSuperAdmin ? '/admin/users' : '/login')}
                    className="text-xs text-goodwill-text-muted hover:text-goodwill-primary transition-colors"
                  >
                    {isExistingSuperAdmin ? 'Back to User Management' : 'Back to Login'}
                  </button>
                </div>
              </form>
            )}
          </div>
        </div>

        {/* Admin About Box with medical-style visuals */}
        <div className="mt-4 bg-white rounded-xl shadow-md border border-purple-200/60 p-4 text-xs text-goodwill-text-muted relative overflow-hidden">
          {/* Subtle animated medical background */}
          <div className="pointer-events-none absolute inset-0 opacity-70">
            <div className="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-purple-100/70 blur-2xl animate-pulse-slow" />
            <div className="absolute -left-10 bottom-0 w-32 h-32 rounded-full bg-blue-100/70 blur-2xl animate-pulse-slower" />
            <div className="absolute inset-x-6 bottom-1 h-px bg-gradient-to-r from-transparent via-purple-300/70 to-transparent" />
          </div>

          <div className="relative z-10 space-y-3">
            <div className="flex items-start gap-2">
              <div className="flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-indigo-500 shadow-sm">
                <HeartPulse className="w-4 h-4 text-white animate-pulse" strokeWidth={2.3} />
              </div>
              <div>
                <p className="font-semibold text-goodwill-dark mb-0.5 flex items-center gap-1.5">
                  About the Admin Role
                  <span className="inline-flex items-center gap-1 rounded-full bg-purple-50 text-purple-700 px-2 py-0.5 text-[10px] font-semibold border border-purple-100">
                    <Activity className="w-3 h-3" />
                    System Lead
                  </span>
                </p>
                <p className="leading-relaxed text-xs">
                  Super admins and admins act as the <strong>clinical guardians</strong> of this compliance system. 
                  They make sure every nurse, carer and healthcare professional in the platform stays <strong>safe, cleared and ready to work</strong>.
                </p>
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-2 mt-1">
              <div className="rounded-lg border border-purple-100 bg-purple-50/60 px-3 py-2.5">
                <p className="text-[11px] font-semibold text-purple-800 flex items-center gap-1.5">
                  <Stethoscope className="w-3.5 h-3.5" />
                  Clinical Oversight
                </p>
                <p className="mt-1 text-[11px] text-purple-900/90 leading-snug">
                  Review and approve compliance for all staff linked to your organisation.
                </p>
              </div>
              <div className="rounded-lg border border-indigo-100 bg-indigo-50/60 px-3 py-2.5">
                <p className="text-[11px] font-semibold text-indigo-900 flex items-center gap-1.5">
                  <Shield className="w-3.5 h-3.5" />
                  Access & Security
                </p>
                <p className="mt-1 text-[11px] text-indigo-900/90 leading-snug">
                  Control who can log in, what they can see, and how data is protected.
                </p>
              </div>
              <div className="rounded-lg border border-emerald-100 bg-emerald-50/60 px-3 py-2.5">
                <p className="text-[11px] font-semibold text-emerald-900 flex items-center gap-1.5">
                  <CheckCircle className="w-3.5 h-3.5" />
                  Audit Ready
                </p>
                <p className="mt-1 text-[11px] text-emerald-900/90 leading-snug">
                  Keep a clear history of approvals, updates and credential decisions.
                </p>
              </div>
            </div>

            <div className="mt-1 rounded-lg border border-purple-100 bg-gradient-to-r from-purple-50 to-blue-50 px-3 py-2 flex items-start gap-2">
              <div className="mt-0.5">
                <AlertCircle className="w-3.5 h-3.5 text-purple-600" />
              </div>
              <p className="text-[11px] leading-snug text-goodwill-text-muted/95">
                Use this page to create the <strong>first super admin</strong> or additional super admins. 
                We recommend limiting this level of access to a small number of trusted clinical or HR leads.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default CreateSuperAdmin;

