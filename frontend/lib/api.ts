import { useCallback } from 'react';
import { useAuth } from '../context/AuthContext';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';
const ORG_HOST_OVERRIDE = import.meta.env.VITE_ORG_HOST as string | undefined;

class UnauthorizedError extends Error {
  constructor(message = 'Unauthorized') {
    super(message);
    this.name = 'UnauthorizedError';
  }
}

const UNAUTHORIZED_LOGOUT_COOLDOWN_MS = 5_000;
let lastUnauthorizedLogoutAt = 0;

export const useApi = () => {
  const { token, logout } = useAuth();

  const request = useCallback(async (endpoint: string, options: RequestInit = {}) => {
    const headers: Record<string, string> = {
      'Accept': 'application/json',
      ...(options.headers as Record<string, string>),
    };

    const orgHost = ORG_HOST_OVERRIDE || window.location.host;
    if (orgHost) {
      headers['X-Org-Host'] = orgHost;
    }

    if (options.body && !(options.body instanceof FormData)) {
      headers['Content-Type'] = 'application/json';
    }

    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    const url = endpoint.startsWith('http') ? endpoint : `${API_BASE_URL}${endpoint}`;
    
    const response = await fetch(url, {
      ...options,
      headers,
      credentials: 'include',
    });

    if (response.status === 401) {
      const now = Date.now();
      if (now - lastUnauthorizedLogoutAt > UNAUTHORIZED_LOGOUT_COOLDOWN_MS) {
        lastUnauthorizedLogoutAt = now;
        logout();
      }
      throw new UnauthorizedError('Unauthorized');
    }

    let data;
    const contentType = response.headers.get('content-type');
    if (contentType && contentType.includes('application/json')) {
      data = await response.json();
    } else {
      data = await response.text();
    }

    if (!response.ok) {
      throw new Error(data?.message || (typeof data === 'string' ? data : 'API request failed'));
    }

    return data;
  }, [token, logout]);

  const normalize = useCallback((res: any) => {
    if (Array.isArray(res)) return res;
    if (res && Array.isArray(res.data)) return res.data;
    return [];
  }, []);

  return { request, normalize };
};
