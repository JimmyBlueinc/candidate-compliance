import { defineStore } from 'pinia';
import { getHttp, applyTenantHeader, applyAuthHeader } from '../lib/api';

const STORAGE_VERSION = '2026-03-21-001';
const VERSION_KEY = 'auth.storage_version';
const TOKEN_STORAGE_KEY = 'auth.token';
const USER_STORAGE_KEY = 'auth.user';
const TENANT_STORAGE_KEY = 'auth.tenant_id';

// Get axios instance with proper baseURL
function getAxios() {
    return getHttp();
}

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: null,
        tenantId: null,
        hydrated: false,
        status: 'idle',
        error: null,
        // Temporary in-memory password for forced password change flow
        // NEVER persisted to storage - cleared on logout or after password change
        _tempPassword: null,
    }),

    getters: {
        isAuthenticated: (state) => Boolean(state.token),
        hasTempPassword: (state) => Boolean(state._tempPassword),
    },

    actions: {
        setSession({ token, user }) {
            this.user = user || null;
            this.token = token || null;

            const inferredTenantId = this.user?.organization_id ? String(this.user.organization_id) : null;
            if (inferredTenantId) {
                this.setTenantId(inferredTenantId);
            }

            if (this.token) {
                localStorage.setItem(TOKEN_STORAGE_KEY, this.token);
            } else {
                localStorage.removeItem(TOKEN_STORAGE_KEY);
            }

            if (this.user) {
                localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(this.user));
            } else {
                localStorage.removeItem(USER_STORAGE_KEY);
            }

            this.applyAxiosAuth();
        },

        initFromStorage() {
            console.log('[AUTH] initFromStorage START');
            
            // Check storage version - clear old state if version mismatch
            const storedVersion = localStorage.getItem(VERSION_KEY);
            console.log('[AUTH] storage version:', { stored: storedVersion, expected: STORAGE_VERSION });
            
            if (storedVersion !== STORAGE_VERSION) {
                console.log('[AUTH] VERSION MISMATCH - clearing old storage');
                localStorage.removeItem(TOKEN_STORAGE_KEY);
                localStorage.removeItem(USER_STORAGE_KEY);
                localStorage.removeItem(TENANT_STORAGE_KEY);
                localStorage.setItem(VERSION_KEY, STORAGE_VERSION);
            }
            
            const token = localStorage.getItem(TOKEN_STORAGE_KEY);
            const userRaw = localStorage.getItem(USER_STORAGE_KEY);
            const tenantId = localStorage.getItem(TENANT_STORAGE_KEY);

            console.log('[AUTH] storage contents:', {
                hasToken: !!token,
                tokenPreview: token?.slice(0, 20) + '...',
                hasUser: !!userRaw,
                userPreview: userRaw?.slice(0, 100),
                tenantId
            });

            this.token = token || null;
            
            // Parse user with error handling
            if (userRaw) {
                try {
                    this.user = JSON.parse(userRaw);
                    console.log('[AUTH] parsed user:', {
                        id: this.user?.id,
                        email: this.user?.email,
                        role: this.user?.role,
                        needs_onboarding: this.user?.needs_onboarding,
                        organization_id: this.user?.organization_id
                    });
                } catch (e) {
                    console.error('[AUTH] failed to parse user JSON:', e);
                    this.user = null;
                    localStorage.removeItem(USER_STORAGE_KEY);
                }
            } else {
                this.user = null;
            }
            
            this.tenantId = tenantId || null;

            console.log('[AUTH] final state:', {
                token: this.token ? 'present' : 'null',
                user: this.user ? 'present' : 'null',
                tenantId: this.tenantId,
                isAuthenticated: this.isAuthenticated
            });

            this.applyAxiosAuth();
            this.applyAxiosTenant();

            this.hydrated = true;
            console.log('[AUTH] initFromStorage COMPLETE, hydrated=true');
        },

        applyAxiosAuth() {
            applyAuthHeader(this.token);
        },

        applyAxiosTenant() {
            applyTenantHeader(this.tenantId);
        },

        setTenantId(tenantId) {
            this.tenantId = tenantId || null;

            if (this.tenantId) {
                localStorage.setItem(TENANT_STORAGE_KEY, this.tenantId);
            } else {
                localStorage.removeItem(TENANT_STORAGE_KEY);
            }

            this.applyAxiosTenant();
        },

        async login({ email, password, rememberMe = false, tenantId = null }) {
            console.log('[LOGIN] START', { email, hasTenantId: Boolean(tenantId) });
            this.status = 'loading';
            this.error = null;

            if (tenantId) {
                this.setTenantId(tenantId);
            }

            try {
                // Use API domain for login when on any agenchq.com host (including tenant subdomains)
                const hostname = window.location.hostname;
                const apiBase = (hostname === 'agenchq.com' || hostname.endsWith('.agenchq.com'))
                    ? 'https://api.agenchq.com'
                    : '';
                console.log('[LOGIN] FETCHING', { url: `${apiBase}/api/login` });
                
                const res = await fetch(`${apiBase}/api/login`, {
                    method: 'POST',
                    credentials: 'omit',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        email,
                        password,
                        remember_me: rememberMe,
                    }),
                });

                console.log('[LOGIN] RESPONSE STATUS', { status: res.status, ok: res.ok });

                const rawText = await res.text().catch((e) => { console.log('[LOGIN] TEXT READ ERROR', e); return ''; });
                console.log('[LOGIN] RAW TEXT', { length: rawText.length, preview: rawText.slice(0, 500) });
                
                let payload = null;
                try {
                    payload = rawText ? JSON.parse(rawText) : null;
                    console.log('[LOGIN] PARSED PAYLOAD', payload);
                } catch (parseErr) {
                    console.log('[LOGIN] JSON PARSE ERROR', parseErr);
                    payload = null;
                }

                if (!res.ok) {
                    console.log('[LOGIN] NOT OK, THROWING');
                    const err = new Error(payload?.message || 'Unable to sign in. Please try again.');
                    err.response = { data: payload, status: res.status };
                    throw err;
                }

                const token = payload?.token ?? payload?.access_token ?? payload?.data?.token ?? null;
                const user = payload?.user ?? payload?.data?.user ?? null;
                console.log('[LOGIN] EXTRACTED', { hasToken: Boolean(token), hasUser: Boolean(user), tokenLength: token?.length, userKeys: user ? Object.keys(user) : null });

                if (!token || !user) {
                    console.log('[LOGIN] MISSING TOKEN OR USER, THROWING');
                    const err = new Error(payload?.message || 'Login response missing token or user.');
                    err.response = { data: payload, status: res.status, rawText };
                    throw err;
                }

                console.log('[LOGIN] BEFORE setSession');
                try {
                    this.setSession({ token, user });
                    console.log('[LOGIN] AFTER setSession');
                } catch (sessionErr) {
                    console.log('[LOGIN] setSession ERROR', sessionErr);
                    throw sessionErr;
                }

                if (user?.organization_id) {
                    console.log('[LOGIN] SETTING TENANT', { organization_id: user.organization_id });
                    this.setTenantId(String(user.organization_id));
                }

                console.log('[LOGIN] CHECKING LOCALSTORAGE');
                console.log('[LOGIN] localStorage.token', localStorage.getItem(TOKEN_STORAGE_KEY)?.slice(0, 20) + '...');
                console.log('[LOGIN] localStorage.user', localStorage.getItem(USER_STORAGE_KEY)?.slice(0, 100));

                // Store password in memory if user must change it (never persisted to storage)
                if (user?.must_change_password) {
                    this._tempPassword = password;
                    console.log('[LOGIN] stored temp password for forced change flow');
                }

                this.status = 'authenticated';
                console.log('[LOGIN] SUCCESS, RETURNING payload');
                return payload;
            } catch (error) {
                console.log('[LOGIN] CATCH ERROR', error);
                this.status = 'error';
                this.error = error;
                throw error;
            } finally {
                console.log('[LOGIN] FINALLY, status=', this.status);
            }
        },

        async register({ name, email, password, passwordConfirmation, tenantId = null }) {
            this.status = 'loading';
            this.error = null;

            if (tenantId) {
                this.setTenantId(tenantId);
            }

            try {
                const response = await getAxios().post('/api/register', {
                    name,
                    email,
                    password,
                    password_confirmation: passwordConfirmation,
                    role: 'candidate',
                });

                this.setSession({
                    token: response.data.token,
                    user: response.data.user,
                });

                if (response.data?.user?.organization_id) {
                    this.setTenantId(String(response.data.user.organization_id));
                }

                this.status = 'authenticated';
                return response.data;
            } catch (error) {
                this.status = 'error';
                this.error = error;
                throw error;
            }
        },

        async logout() {
            try {
                await getAxios().post('/api/logout');
            } finally {
                this.user = null;
                this.token = null;
                this.tenantId = null;
                this.status = 'idle';
                this.error = null;
                this._tempPassword = null; // Clear temp password

                localStorage.removeItem(TOKEN_STORAGE_KEY);
                localStorage.removeItem(USER_STORAGE_KEY);
                localStorage.removeItem(TENANT_STORAGE_KEY);

                this.applyAxiosAuth();
                this.applyAxiosTenant();

                // Reset brand store on logout
                const { useBrandStore } = await import('./brand');
                const brand = useBrandStore();
                brand.reset();
            }
        },

        clearTempPassword() {
            this._tempPassword = null;
            console.log('[AUTH] temp password cleared');
        },

        async fetchUser() {
            if (!this.token) return null;

            try {
                const response = await getAxios().get('/api/user');
                this.user = response.data.user;
                localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(this.user));

                if (this.user?.organization_id) {
                    this.setTenantId(String(this.user.organization_id));
                }
                return this.user;
            } catch (error) {
                if (error.response && error.response.status === 401) {
                    try {
                        this.applyAxiosAuth();
                        const retry = await getAxios().get('/api/user');
                        this.user = retry.data.user;
                        localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(this.user));

                        if (this.user?.organization_id) {
                            this.setTenantId(String(this.user.organization_id));
                        }
                        return this.user;
                    } catch (retryError) {
                        this.setSession({ token: null, user: null });
                        this.setTenantId(null);
                        throw retryError;
                    }
                }
                throw error;
            }
        },
    },
});
