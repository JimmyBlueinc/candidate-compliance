import { defineStore } from 'pinia';
import { apiGet } from '../lib/api';

function normalizeHexColor(value) {
    if (!value) return null;
    const raw = String(value).trim();
    if (!raw) return null;
    if (raw.startsWith('#')) return raw;
    if (/^[0-9a-f]{3,8}$/i.test(raw)) return `#${raw}`;
    return raw;
}

function hexToRgb(hex) {
    const c = String(hex || '').replace('#', '').trim();
    if (!/^[0-9a-f]{3}$|^[0-9a-f]{6}$/i.test(c)) return null;

    const full = c.length === 3 ? c.split('').map((x) => x + x).join('') : c;
    const r = parseInt(full.slice(0, 2), 16);
    const g = parseInt(full.slice(2, 4), 16);
    const b = parseInt(full.slice(4, 6), 16);
    return { r, g, b };
}

function extractSubdomain() {
    const host = window.location.hostname;
    console.log('[BRAND] extractSubdomain - hostname:', host);
    
    // Handle localhost development
    if (host === 'localhost' || host === '127.0.0.1') {
        // Check for subdomain-style localhost (e.g., jimmy.localhost)
        const parts = host.split('.');
        if (parts.length > 1) {
            const subdomain = parts[0];
            console.log('[BRAND] extractSubdomain - localhost subdomain:', subdomain);
            return subdomain;
        }
        console.log('[BRAND] extractSubdomain - localhost, no subdomain');
        return null;
    }
    
    // Check for agenchq.com domain (apex OR subdomain) - MUST come before custom domain check
    if (host === 'agenchq.com' || host === 'www.agenchq.com') {
        console.log('[BRAND] extractSubdomain - apex/www domain: null');
        return null; // Apex or www - no tenant subdomain
    }
    
    if (host.endsWith('.agenchq.com')) {
        // Extract subdomain (e.g., "jimmy" from "jimmy.agenchq.com")
        const subdomain = host.replace('.agenchq.com', '');
        console.log('[BRAND] extractSubdomain - tenant subdomain:', subdomain);
        return subdomain;
    }
    
    // Handle custom domains - extract first part as potential subdomain
    // Only if NOT a known platform domain
    const parts = host.split('.');
    if (parts.length >= 2) {
        const potentialSubdomain = parts[0];
        // Filter out common prefixes that are NOT tenant subdomains
        if (!['www', 'app', 'api', 'mail', 'admin'].includes(potentialSubdomain)) {
            console.log('[BRAND] extractSubdomain - custom domain subdomain:', potentialSubdomain);
            return potentialSubdomain;
        }
    }
    
    console.log('[BRAND] extractSubdomain - no subdomain detected');
    return null;
}

export const useBrandStore = defineStore('brand', {
    state: () => ({
        loaded: false,
        loading: false,
        error: null,
        tenantId: null,
        name: null,
        slug: null,
        subdomain: null,
        primaryColor: null,
        logoUrl: null,
    }),

    getters: {
        primaryColorCss: (state) => state.primaryColor || 'var(--p-primary-color)',
    },

    actions: {
        initFromStorage() {
            try {
                // Check if we're on apex domain - if so, don't load cached tenant brand
                const host = window.location.hostname;
                const isApexDomain = host === 'agenchq.com' || host === 'www.agenchq.com';
                
                if (isApexDomain) {
                    // Clear any cached tenant brand on apex domain
                    localStorage.removeItem('brand');
                    this.loaded = true;
                    console.log('[BRAND] Apex domain - cleared cached brand');
                    return;
                }
                
                const stored = localStorage.getItem('brand');
                if (stored) {
                    const data = JSON.parse(stored);
                    this.tenantId = data.tenantId ?? null;
                    this.name = data.name ?? null;
                    this.slug = data.slug ?? null;
                    this.subdomain = data.subdomain ?? null;
                    this.primaryColor = data.primaryColor ?? null;
                    this.logoUrl = data.logoUrl ?? null;
                    this.loaded = true;

                    if (this.primaryColor) {
                        document.documentElement.style.setProperty('--brand-primary', this.primaryColor);
                        // Also set PrimeVue theme variable for UI components
                        document.documentElement.style.setProperty('--p-primary-color', this.primaryColor);
                        const rgb = hexToRgb(this.primaryColor);
                        if (rgb) {
                            document.documentElement.style.setProperty('--brand-primary-rgb', `${rgb.r} ${rgb.g} ${rgb.b}`);
                        }
                    }
                    console.log('[BRAND] Loaded from storage:', data);
                }
            } catch (e) {
                console.log('[BRAND] Failed to load from storage:', e);
            }
        },

        saveToStorage() {
            try {
                localStorage.setItem('brand', JSON.stringify({
                    tenantId: this.tenantId,
                    name: this.name,
                    slug: this.slug,
                    subdomain: this.subdomain,
                    primaryColor: this.primaryColor,
                    logoUrl: this.logoUrl,
                }));
            } catch (e) {
                console.log('[BRAND] Failed to save to storage:', e);
            }
        },

        async load() {
            if (this.loading) return;

            console.log('[BRAND] LOAD START');
            this.loading = true;
            this.error = null;

            try {
                console.log('[BRAND] CALLING apiGet /brand');

                // Get subdomain from current window location
                const currentSubdomain = extractSubdomain();
                console.log('[BRAND] Current subdomain:', currentSubdomain);

                // Pass subdomain as query param for unauthenticated brand loading
                const params = {};
                if (currentSubdomain) {
                    params.subdomain = currentSubdomain;
                }

                const res = await apiGet('/brand', {
                    params,
                    timeout: 5000,
                    headers: currentSubdomain ? { 'X-Subdomain': currentSubdomain } : {}
                });
                console.log('[BRAND] RESPONSE', res);
                const brand = res?.brand || null;

                this.tenantId = brand?.tenant_id ?? null;
                this.name = brand?.name ?? null;
                this.slug = brand?.slug ?? null;
                this.subdomain = brand?.subdomain ?? null;
                this.primaryColor = normalizeHexColor(brand?.primary_color) || null;
                this.logoUrl = brand?.logo_url ?? null;
                this.loaded = true;

                if (this.primaryColor) {
                    document.documentElement.style.setProperty('--brand-primary', this.primaryColor);
                    // Also set PrimeVue theme variable for UI components
                    document.documentElement.style.setProperty('--p-primary-color', this.primaryColor);

                    const rgb = hexToRgb(this.primaryColor);
                    if (rgb) {
                        document.documentElement.style.setProperty('--brand-primary-rgb', `${rgb.r} ${rgb.g} ${rgb.b}`);
                    }
                }

                this.saveToStorage();
                console.log('[BRAND] LOAD SUCCESS');
            } catch (e) {
                console.log('[BRAND] LOAD ERROR', e);
                this.error = e;
                // Don't block app on brand failure - still mark as loaded
                this.loaded = true;
            } finally {
                this.loading = false;
            }
        },

        // Reset brand to default state - call on logout
        reset() {
            console.log('[BRAND] RESET - clearing all brand state');
            this.loaded = false;
            this.loading = false;
            this.error = null;
            this.tenantId = null;
            this.name = null;
            this.slug = null;
            this.subdomain = null;
            this.primaryColor = null;
            this.logoUrl = null;

            // Clear CSS variables
            document.documentElement.style.removeProperty('--brand-primary');
            document.documentElement.style.removeProperty('--brand-primary-rgb');

            // Clear localStorage
            localStorage.removeItem('brand');
        },

        // Update brand from API response - call after save operations
        updateFromResponse(brandData) {
            console.log('[BRAND] updateFromResponse:', brandData);
            if (!brandData) return;

            this.tenantId = brandData.tenant_id ?? this.tenantId;
            this.name = brandData.name ?? this.name;
            this.slug = brandData.slug ?? this.slug;
            this.subdomain = brandData.subdomain ?? this.subdomain;
            this.primaryColor = normalizeHexColor(brandData.primary_color) || this.primaryColor;
            this.logoUrl = brandData.logo_url ?? this.logoUrl;
            this.loaded = true;

            if (this.primaryColor) {
                document.documentElement.style.setProperty('--brand-primary', this.primaryColor);
                // Also set PrimeVue theme variable for UI components
                document.documentElement.style.setProperty('--p-primary-color', this.primaryColor);
                const rgb = hexToRgb(this.primaryColor);
                if (rgb) {
                    document.documentElement.style.setProperty('--brand-primary-rgb', `${rgb.r} ${rgb.g} ${rgb.b}`);
                }
            }

            this.saveToStorage();
        },
    },
});
