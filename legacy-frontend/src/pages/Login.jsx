import { useState, useEffect } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { Eye, EyeOff, Mail, Lock, User, Shield } from 'lucide-react';

const Login = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [isRegister, setIsRegister] = useState(false);
  const [name, setName] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [role, setRole] = useState('recruiter');
  const [avatar, setAvatar] = useState(null);
  const [showPassword, setShowPassword] = useState(false);
  const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);
  const [rememberMe, setRememberMe] = useState(false);

  const { login, register, isAuthenticated } = useAuth();
  const navigate = useNavigate();

  // Redirect if already authenticated
  useEffect(() => {
    if (isAuthenticated) {
      navigate('/dashboard', { replace: true });
    }
  }, [isAuthenticated, navigate]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    
    // Client-side validation
    if (isRegister) {
      if (password !== passwordConfirmation) {
        setError('Passwords do not match');
        return;
      }
      if (password.length < 8) {
        setError('Password must be at least 8 characters long');
        return;
      }
    }
    
    setLoading(true);

    try {
      let result;
      if (isRegister) {
        result = await register(name, email, password, passwordConfirmation, role, avatar);
      } else {
        result = await login(email, password, rememberMe);
      }

      if (result.success) {
        navigate('/dashboard');
      } else {
        // Format error message for better UX
        let errorMessage = result.error || 'Authentication failed';
        if (typeof errorMessage === 'object') {
          // Handle validation errors from backend
          const errors = Object.values(errorMessage).flat();
          errorMessage = errors.join(', ');
        }
        setError(errorMessage);
      }
    } catch (err) {
      console.error('Login error:', err);
      
      // Handle connection timeout errors specifically
      if (err?.type === 'connection_error' || 
          err?.message?.includes('timeout') || 
          err?.code === 'ECONNABORTED' || 
          err?.code === 'ERR_NETWORK' ||
          err?.message === 'Network Error' ||
          err?.originalError?.code === 'ERR_CONNECTION_TIMED_OUT') {
        setError('Connection timeout. The server may be offline or unreachable. Please check if the backend server is running and try again.');
      } else if (err?.response?.status === 0 || err?.message?.includes('Failed to fetch')) {
        setError('Unable to connect to the server. Please check your network connection and ensure the backend server is running.');
      } else if (err?.response?.data?.message) {
        setError(err.response.data.message);
      } else if (err?.message) {
        setError(err.message);
      } else {
        setError('An unexpected error occurred. Please try again.');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-goodwill-primary/20 via-goodwill-primary/15 to-goodwill-primary/10 flex items-center justify-center p-4">
      <div className="bg-white rounded-lg shadow-lg w-full max-w-sm p-5 border border-goodwill-primary/30">
        <div className="text-center mb-5">
          <h1 className="text-xl font-bold text-goodwill-dark mb-1">
            Goodwill Staffing
          </h1>
          <p className="text-xs text-goodwill-primary font-semibold mb-1">Security & Compliance System</p>
          <p className="text-xs text-goodwill-text-muted">
            {isRegister ? 'Create your account' : 'Sign in to your account'}
          </p>
        </div>

        {error && (
          <div className="mb-3 p-2.5 bg-red-50 border-l-4 border-red-500 text-goodwill-dark rounded-lg text-xs font-medium shadow-sm flex items-start gap-2">
            <div className="flex-shrink-0 mt-0.5">
              <svg className="w-3.5 h-3.5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
              </svg>
            </div>
            <div className="flex-1">
              <p className="font-semibold text-red-800 mb-0.5 text-xs">Error</p>
              <p className="text-red-700 text-xs">{typeof error === 'object' ? JSON.stringify(error) : error}</p>
            </div>
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-3">
          {isRegister && (
            <>
              <div>
                <label className="block text-xs font-semibold text-goodwill-dark mb-1 flex items-center gap-1.5">
                  <User className="w-3 h-3 text-goodwill-primary" />
                  Full Name
                </label>
                <input
                  type="text"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  required
                  className="w-full px-2.5 py-1.5 text-xs border border-goodwill-border/50 rounded-lg bg-white focus:ring-2 focus:ring-goodwill-primary focus:border-goodwill-primary transition-all text-goodwill-dark placeholder:text-goodwill-text-muted"
                  placeholder="John Doe"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-goodwill-dark mb-1 flex items-center gap-1.5">
                  <Shield className="w-3 h-3 text-goodwill-primary" />
                  Role
                </label>
                <select
                  value={role}
                  onChange={(e) => setRole(e.target.value)}
                  className="w-full px-2.5 py-1.5 text-xs border border-goodwill-border/50 rounded-lg bg-white focus:ring-2 focus:ring-goodwill-primary focus:border-goodwill-primary transition-all text-goodwill-dark"
                >
                  <option value="recruiter">Recruiter</option>
                  <option value="admin">Admin</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-goodwill-dark mb-1">
                  Profile Photo (optional)
                </label>
                <input
                  type="file"
                  accept="image/*"
                  onChange={(e) => setAvatar(e.target.files?.[0] || null)}
                  className="w-full px-2.5 py-1.5 border border-goodwill-border/50 rounded-lg bg-white focus:ring-2 focus:ring-goodwill-primary focus:border-goodwill-primary transition-all text-goodwill-dark text-xs file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-goodwill-primary file:text-white hover:file:bg-goodwill-primary/90 file:transition-all file:cursor-pointer"
                />
              </div>
            </>
          )}

          <div>
            <label className="block text-xs font-semibold text-goodwill-dark mb-1 flex items-center gap-1.5">
              <Mail className="w-3 h-3 text-goodwill-primary" />
              Email Address
            </label>
            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              className="w-full px-2.5 py-1.5 text-xs border border-goodwill-border/50 rounded-lg bg-white focus:ring-2 focus:ring-goodwill-primary focus:border-goodwill-primary transition-all text-goodwill-dark placeholder:text-goodwill-text-muted"
              placeholder="you@example.com"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-goodwill-dark mb-1 flex items-center gap-1.5">
              <Lock className="w-3 h-3 text-goodwill-primary" />
              Password
            </label>
            <div className="relative">
              <input
                type={showPassword ? 'text' : 'password'}
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
                minLength={8}
                className="w-full px-2.5 py-1.5 pr-8 text-xs border border-goodwill-border/50 rounded-lg bg-white focus:ring-2 focus:ring-goodwill-primary focus:border-goodwill-primary transition-all text-goodwill-dark placeholder:text-goodwill-text-muted"
                placeholder="••••••••"
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-2 top-1/2 -translate-y-1/2 text-goodwill-text-muted hover:text-goodwill-primary transition-colors"
                tabIndex={-1}
              >
                {showPassword ? (
                  <EyeOff className="w-3.5 h-3.5" strokeWidth={2} />
                ) : (
                  <Eye className="w-3.5 h-3.5" strokeWidth={2} />
                )}
              </button>
            </div>
            {!isRegister && (
              <p className="mt-0.5 text-xs text-goodwill-text-muted">
                Must be at least 8 characters
              </p>
            )}
          </div>

          {!isRegister && (
            <div className="flex items-center justify-between">
              <label className="flex items-center gap-1.5 cursor-pointer">
                <input
                  type="checkbox"
                  checked={rememberMe}
                  onChange={(e) => setRememberMe(e.target.checked)}
                  className="w-3 h-3 text-goodwill-primary border-goodwill-border rounded focus:ring-goodwill-primary focus:ring-1"
                />
                <span className="text-xs text-goodwill-text-muted">Remember me</span>
              </label>
              <Link
                to="/forgot-password"
                className="text-xs text-goodwill-primary hover:text-goodwill-secondary font-semibold transition-colors"
              >
                Forgot password?
              </Link>
            </div>
          )}

          {isRegister && (
            <div>
              <label className="block text-xs font-semibold text-goodwill-dark mb-1 flex items-center gap-1.5">
                <Lock className="w-3 h-3 text-goodwill-primary" />
                Confirm Password
              </label>
              <div className="relative">
                <input
                  type={showPasswordConfirmation ? 'text' : 'password'}
                  value={passwordConfirmation}
                  onChange={(e) => setPasswordConfirmation(e.target.value)}
                  required
                  minLength={8}
                  className="w-full px-2.5 py-1.5 pr-8 text-xs border border-goodwill-border/50 rounded-lg bg-white focus:ring-2 focus:ring-goodwill-primary focus:border-goodwill-primary transition-all text-goodwill-dark placeholder:text-goodwill-text-muted"
                  placeholder="••••••••"
                />
                <button
                  type="button"
                  onClick={() => setShowPasswordConfirmation(!showPasswordConfirmation)}
                  className="absolute right-2 top-1/2 -translate-y-1/2 text-goodwill-text-muted hover:text-goodwill-primary transition-colors"
                  tabIndex={-1}
                >
                  {showPasswordConfirmation ? (
                    <EyeOff className="w-3.5 h-3.5" strokeWidth={2} />
                  ) : (
                    <Eye className="w-3.5 h-3.5" strokeWidth={2} />
                  )}
                </button>
              </div>
              {passwordConfirmation && password !== passwordConfirmation && (
                <p className="mt-0.5 text-xs text-red-600">
                  Passwords do not match
                </p>
              )}
            </div>
          )}

          <button
            type="submit"
            disabled={loading || (isRegister && password !== passwordConfirmation)}
            className="w-full bg-gradient-to-r from-goodwill-primary to-goodwill-primary/90 hover:from-goodwill-primary/90 hover:to-goodwill-primary text-white font-semibold py-2 px-3 rounded-lg text-xs transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm hover:shadow-md transform hover:scale-[1.01] active:scale-[0.99] flex items-center justify-center gap-1.5"
          >
            {loading ? (
              <>
                <div className="animate-spin rounded-full h-3.5 w-3.5 border-b-2 border-white"></div>
                <span>Processing...</span>
              </>
            ) : isRegister ? (
              <>
                <User className="w-3.5 h-3.5" strokeWidth={2} />
                <span>Create Account</span>
              </>
            ) : (
              <>
                <Lock className="w-3.5 h-3.5" strokeWidth={2} />
                <span>Sign In</span>
              </>
            )}
          </button>
        </form>

        <div className="mt-4 space-y-2">
          <div className="text-center">
            <button
              type="button"
              onClick={() => {
                setIsRegister(!isRegister);
                setError('');
              }}
              className="text-goodwill-primary hover:text-goodwill-secondary text-xs font-semibold transition-colors"
            >
              {isRegister
                ? 'Already have an account? Sign in'
                : "Don't have an account? Sign up"}
            </button>
          </div>
          
          {!isRegister && (
            <div className="text-center pt-2 border-t border-goodwill-border/50">
              <p className="text-xs text-goodwill-text-muted mb-1">Are you a candidate?</p>
              <Link
                to="/candidate/register"
                className="text-goodwill-primary hover:text-goodwill-secondary text-xs font-semibold transition-colors inline-flex items-center gap-1"
              >
                <User className="w-3 h-3" strokeWidth={2} />
                Register as Candidate
              </Link>
            </div>
          )}
        </div>

      </div>
    </div>
  );
};

export default Login;


