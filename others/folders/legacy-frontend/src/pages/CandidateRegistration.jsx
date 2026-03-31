import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { User, Mail, Lock, Eye, EyeOff, FileText, Calendar, AlertCircle, CheckCircle } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import api from '../config/api';

const CandidateRegistration = () => {
  const navigate = useNavigate();
  const { login } = useAuth();
  const [formData, setFormData] = useState({
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

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: value,
    }));
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
      const response = await api.post('/register', {
        ...formData,
        role: 'candidate', // Explicitly set role as candidate
      });

      if (response.data.token) {
        // Auto-login after registration
        localStorage.setItem('auth_token', response.data.token);
        api.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
        
        // Update auth context
        await login(formData.email, formData.password);
        
        setSuccess(true);
        
        // Redirect to My Credentials page after 2 seconds
        setTimeout(() => {
          navigate('/credentials/my-credentials');
        }, 2000);
      }
    } catch (err) {
      // Handle validation errors
      if (err.response?.status === 422) {
        const errors = err.response?.data?.errors || {};
        const errorMessages = [];
        
        Object.keys(errors).forEach((key) => {
          if (Array.isArray(errors[key])) {
            errors[key].forEach((msg) => {
              errorMessages.push(`${key}: ${msg}`);
            });
          } else {
            errorMessages.push(`${key}: ${errors[key]}`);
          }
        });
        
        if (errorMessages.length > 0) {
          setError(errorMessages.join('. '));
        } else {
          setError(err.response?.data?.message || 'Validation failed. Please check your input.');
        }
      } else {
        setError(
          err.response?.data?.message ||
          'Failed to create candidate account. Please try again.'
        );
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-goodwill-primary/20 via-goodwill-primary/15 to-goodwill-primary/10 flex items-center justify-center py-8 px-4">
      <div className="max-w-sm w-full">
        <div className="bg-white rounded-lg shadow-lg border border-goodwill-primary/30 overflow-hidden animate-fade-in-up">
          {/* Header */}
          <div className="bg-gradient-to-r from-goodwill-primary via-goodwill-primary to-goodwill-primary px-4 py-3 border-b border-goodwill-primary/20">
            <div className="flex items-center gap-2">
              <div className="h-7 w-7 rounded-lg bg-white/20 backdrop-blur-sm shadow-sm flex items-center justify-center border border-white/30">
                <User className="w-3.5 h-3.5 text-white" strokeWidth={2} />
              </div>
              <div>
                <h1 className="text-sm font-bold tracking-tight text-white">Candidate Registration</h1>
                <p className="text-white/90 text-xs mt-0.5 font-medium">Register to manage your credentials</p>
              </div>
            </div>
          </div>

          {/* Form Content */}
          <form onSubmit={handleSubmit} className="p-4 space-y-3">
            {/* Full Name */}
            <div>
              <label className="block text-xs font-semibold text-goodwill-dark mb-1 flex items-center gap-1.5">
                <User className="w-3 h-3 text-goodwill-primary" />
                Full Name
              </label>
              <input
                type="text"
                name="name"
                value={formData.name}
                onChange={handleInputChange}
                required
                placeholder="Enter your full name"
                className="w-full px-2.5 py-1.5 text-xs border border-goodwill-border/50 rounded-lg bg-white text-goodwill-dark focus:ring-2 focus:ring-goodwill-primary focus:border-transparent"
              />
            </div>

            {/* Email */}
            <div>
              <label className="block text-xs font-semibold text-goodwill-dark mb-1 flex items-center gap-1.5">
                <Mail className="w-3 h-3 text-goodwill-primary" />
                Email Address
              </label>
              <input
                type="email"
                name="email"
                value={formData.email}
                onChange={handleInputChange}
                required
                placeholder="Enter your email address"
                className="w-full px-2.5 py-1.5 text-xs border border-goodwill-border/50 rounded-lg bg-white text-goodwill-dark focus:ring-2 focus:ring-goodwill-primary focus:border-transparent"
              />
              <p className="mt-0.5 text-xs text-goodwill-text-muted">
                This email will be used to track your credentials
              </p>
            </div>

            {/* Password */}
            <div>
              <label className="block text-xs font-semibold text-goodwill-dark mb-1 flex items-center gap-1.5">
                <Lock className="w-3 h-3 text-goodwill-primary" />
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
                  className="w-full px-2.5 py-1.5 pr-8 text-xs border border-goodwill-border/50 rounded-lg bg-white text-goodwill-dark focus:ring-2 focus:ring-goodwill-primary focus:border-transparent"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-2 top-1/2 transform -translate-y-1/2 text-goodwill-text-muted hover:text-goodwill-primary"
                >
                  {showPassword ? (
                    <EyeOff className="w-3.5 h-3.5" />
                  ) : (
                    <Eye className="w-3.5 h-3.5" />
                  )}
                </button>
              </div>
            </div>

            {/* Confirm Password */}
            <div>
              <label className="block text-xs font-semibold text-goodwill-dark mb-1 flex items-center gap-1.5">
                <Lock className="w-3 h-3 text-goodwill-primary" />
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
                  className="w-full px-2.5 py-1.5 pr-8 text-xs border border-goodwill-border/50 rounded-lg bg-white text-goodwill-dark focus:ring-2 focus:ring-goodwill-primary focus:border-transparent"
                />
                <button
                  type="button"
                  onClick={() => setShowPasswordConfirmation(!showPasswordConfirmation)}
                  className="absolute right-2 top-1/2 transform -translate-y-1/2 text-goodwill-text-muted hover:text-goodwill-primary"
                >
                  {showPasswordConfirmation ? (
                    <EyeOff className="w-3.5 h-3.5" />
                  ) : (
                    <Eye className="w-3.5 h-3.5" />
                  )}
                </button>
              </div>
            </div>

            {/* Error Message */}
            {error && (
              <div className="bg-red-50 border-l-4 border-red-500 p-2.5 rounded-lg text-xs animate-fade-in shadow-sm">
                <div className="flex items-start gap-2">
                  <AlertCircle className="w-3.5 h-3.5 text-red-600 flex-shrink-0 mt-0.5" />
                  <div className="flex-1">
                    <p className="text-red-800 font-semibold mb-0.5 text-xs">Error:</p>
                    <p className="text-red-700 text-xs whitespace-pre-wrap break-words">{error}</p>
                  </div>
                </div>
              </div>
            )}

            {/* Success Message */}
            {success && (
              <div className="bg-green-50 border-l-4 border-green-500 p-2.5 rounded-lg text-xs animate-fade-in shadow-sm">
                <div className="flex items-start gap-2">
                  <CheckCircle className="w-3.5 h-3.5 text-green-600 flex-shrink-0 mt-0.5" />
                  <div className="flex-1">
                    <p className="text-green-800 font-semibold mb-0.5 text-xs">Success!</p>
                    <p className="text-green-700 text-xs">Account created successfully. Redirecting to your credentials...</p>
                  </div>
                </div>
              </div>
            )}

            {/* Submit Button */}
            <div className="pt-1">
              <button
                type="submit"
                disabled={loading}
                className={`w-full flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition-all duration-200 shadow-sm hover:shadow-md ${
                  loading
                    ? 'bg-goodwill-primary/70 cursor-not-allowed'
                    : 'bg-gradient-to-r from-goodwill-primary to-goodwill-primary hover:from-goodwill-primary/90 hover:to-goodwill-primary/90 text-white hover-lift'
                }`}
              >
                {loading && (
                  <div className="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                )}
                {loading ? 'Creating Account...' : 'Register as Candidate'}
              </button>
            </div>

            {/* Login Link */}
            <div className="text-center pt-1">
              <p className="text-xs text-goodwill-text-muted">
                Already have an account?{' '}
                <button
                  type="button"
                  onClick={() => navigate('/login')}
                  className="text-goodwill-primary hover:text-goodwill-primary/80 font-semibold underline"
                >
                  Login here
                </button>
              </p>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
};

export default CandidateRegistration;

