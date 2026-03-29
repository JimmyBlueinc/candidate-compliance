// API utilities with explicit baseURL resolution
import axios from 'axios';

// Compute API base URL directly - do NOT rely on window.axios timing
function getApiBase() {
    // Production: use API subdomain for all agenchq.com hosts (including tenant subdomains)
    if (typeof window !== 'undefined') {
        const host = window.location.hostname;
        // Any agenchq.com host (apex, www, app, or tenant subdomain) uses api.agenchq.com
        if (host === 'agenchq.com' || host.endsWith('.agenchq.com')) {
            return 'https://api.agenchq.com';
        }
    }
    // Local dev: empty string (relative to current host)
    return '';
}

// Single shared axios instance
let _http = null;

/**
 * Get the shared axios instance with proper baseURL.
 * This is the ONLY way to get the http client - ensures headers are shared.
 */
export function getHttp() {
    if (_http) return _http;
    
    const base = getApiBase();
    _http = axios.create({
        baseURL: base,
        // Keep a safer default for slower production networks.
        timeout: 30000,
    });
    
    // Log once so we can verify the resolved URL
    console.log('[API] Resolved baseURL:', base, '| full example:', base + '/api/brand');
    
    return _http;
}

/**
 * Apply tenant ID header to the shared axios instance.
 * Called by auth store during initialization.
 */
export function applyTenantHeader(tenantId) {
    const http = getHttp();
    if (tenantId) {
        http.defaults.headers.common['X-Tenant-Id'] = tenantId;
    } else {
        delete http.defaults.headers.common['X-Tenant-Id'];
    }
}

/**
 * Apply auth token header to the shared axios instance.
 * Called by auth store during initialization.
 */
export function applyAuthHeader(token) {
    const http = getHttp();
    if (token) {
        http.defaults.headers.common.Authorization = `Bearer ${token}`;
    } else {
        delete http.defaults.headers.common.Authorization;
    }
}

export function normalizeApiList(res) {
    if (Array.isArray(res)) return res;
    if (res && res.data && Array.isArray(res.data)) return res.data;
    if (res && Array.isArray(res)) return res; // Fallback for raw arrays
    return [];
}

export async function apiGet(path, config = {}) {
    const res = await getHttp().get(`/api${path}`, { ...config });
    return res.data;
}

export async function apiPost(path, data = {}, config = {}) {
    const res = await getHttp().post(`/api${path}`, data, { ...config });
    return res.data;
}

export async function apiPut(path, data = {}, config = {}) {
    const res = await getHttp().put(`/api${path}`, data, { ...config });
    return res.data;
}

export async function apiDelete(path, config = {}) {
    const res = await getHttp().delete(`/api${path}`, { ...config });
    return res.data;
}
