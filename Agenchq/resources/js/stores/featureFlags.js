import { defineStore } from 'pinia';
import { apiGet } from '../lib/api';

export const useFeatureFlagStore = defineStore('featureFlags', {
    state: () => ({
        loaded: false,
        loading: false,
        flags: {},
    }),

    actions: {
        async load() {
            if (this.loading) return;
            this.loading = true;
            try {
                const res = await apiGet('/feature-flags');
                const payload = res?.data || res;
                this.flags = payload?.flags || {};
                this.loaded = true;
            } catch {
                this.flags = {};
            } finally {
                this.loading = false;
            }
        },

        enabled(key, defaultValue = false) {
            const value = this.flags?.[key];
            if (value && typeof value.enabled === 'boolean') return value.enabled;
            return defaultValue;
        },
    },
});
