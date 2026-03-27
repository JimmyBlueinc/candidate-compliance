import { defineStore } from 'pinia';
import { apiGet, apiPut } from '../lib/api';
import { useAuthStore } from './auth';

const THEME_STORAGE_KEY = 'ui.theme';
const SIDEBAR_COLLAPSED_KEY = 'ui.sidebarCollapsed';

function applyThemeClass(theme) {
    const root = document.documentElement;
    root.classList.remove('theme-light', 'theme-dark');
    root.classList.add(theme === 'light' ? 'theme-light' : 'theme-dark');
}

function getSystemTheme() {
    if (typeof window === 'undefined') return 'light';
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

export const useUiStore = defineStore('ui', {
    state: () => ({
        theme: 'light',
        sidebarCollapsed: false,
        serverSynced: false,
    }),

    actions: {
        initTheme() {
            const stored = localStorage.getItem(THEME_STORAGE_KEY);
            this.theme = stored === 'light' || stored === 'dark' ? stored : 'light';
            applyThemeClass(this.theme);

            const sidebarStored = localStorage.getItem(SIDEBAR_COLLAPSED_KEY);
            this.sidebarCollapsed = sidebarStored === 'true';
        },

        async syncFromServer() {
            const auth = useAuthStore();
            if (!auth?.isAuthenticated) return;

            try {
                const res = await apiGet('/settings');
                const settings = res?.settings || null;
                if (!settings) return;

                if (settings.theme === 'light' || settings.theme === 'dark') {
                    this.theme = settings.theme;
                    localStorage.setItem(THEME_STORAGE_KEY, this.theme);
                    applyThemeClass(this.theme);
                }

                if (typeof settings.sidebar_collapsed === 'boolean') {
                    this.sidebarCollapsed = settings.sidebar_collapsed;
                    localStorage.setItem(SIDEBAR_COLLAPSED_KEY, this.sidebarCollapsed ? 'true' : 'false');
                }

                this.serverSynced = true;
            } catch {
                // ignore (fallback to localStorage)
            }
        },

        async persistToServer(patch) {
            const auth = useAuthStore();
            if (!auth?.isAuthenticated) return;

            try {
                await apiPut('/settings', patch);
            } catch {
                // ignore
            }
        },

        setTheme(theme) {
            this.theme = theme === 'light' ? 'light' : 'dark';
            localStorage.setItem(THEME_STORAGE_KEY, this.theme);
            applyThemeClass(this.theme);

            this.persistToServer({ theme: this.theme });
        },

        toggleTheme() {
            this.setTheme(this.theme === 'light' ? 'dark' : 'light');
        },

        setSidebarCollapsed(next) {
            this.sidebarCollapsed = !!next;
            localStorage.setItem(SIDEBAR_COLLAPSED_KEY, this.sidebarCollapsed ? 'true' : 'false');

            this.persistToServer({ sidebar_collapsed: this.sidebarCollapsed });
        },

        toggleSidebar() {
            this.setSidebarCollapsed(!this.sidebarCollapsed);
        },
    },
});
