import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';
console.log(API_BASE_URL)

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true, // For Sanctum cookie-based auth
  timeout: 10000, // 10 second timeout
});

// Request interceptor to add auth token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    
    // If the data is FormData, remove Content-Type header so browser sets it with boundary
    if (config.data instanceof FormData) {
      delete config.headers['Content-Type'];
    }
    
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor to handle auth errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    // Handle connection timeout or network errors
    if (error.code === 'ECONNABORTED' || error.message === 'Network Error' || error.code === 'ERR_NETWORK' || error.message?.includes('timeout')) {
      const errorMessage = {
        message: 'Connection timeout. Please check if the server is running and try again.',
        type: 'connection_error',
        originalError: error
      };
      return Promise.reject(errorMessage);
    }

    if (error.response?.status === 401) {
      // Unauthorized - token expired or invalid
      // Clear token immediately
      localStorage.removeItem('auth_token');
      delete api.defaults.headers.common['Authorization'];
      
      // Dispatch custom event for AuthContext to handle
      window.dispatchEvent(new CustomEvent('auth:logout', { 
        detail: { reason: 'token_expired' } 
      }));
      
      // Redirect to login if not already there
      if (window.location.pathname !== '/login' && !window.location.pathname.startsWith('/forgot-password') && !window.location.pathname.startsWith('/reset-password')) {
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  }
);

export default api;

