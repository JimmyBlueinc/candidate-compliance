import { defineStore } from 'pinia';
import { apiGet, apiPut } from '../lib/api';
import { useAuthStore } from './auth';

const THEME_STORAGE_KEY = 'ui.theme';
const SIDEBAR_COLLAPSED_KEY = 'ui.sidebarCollapsed';
const DASHBOARD_WIDGETS_KEY = 'ui.dashboardWidgets';

function applyThemeClass() {
    const root = document.documentElement;
    root.classList.remove('theme-light', 'theme-dark');
    root.classList.add('theme-light');
}

export const useUiStore = defineStore('ui', {
    state: () => ({
        theme: 'light',
        sidebarCollapsed: false,
        serverSynced: false,
        dashboardWidgets: {
            facilityProfitability: true,
            complianceTrend: true,
            riskExposure: true,
            activityFeed: true,
            notifications: true,
        },
    }),

    actions: {
        initTheme() {
            // Single colorful mode only
            this.theme = 'light';
            localStorage.setItem(THEME_STORAGE_KEY, 'light');
            applyThemeClass();

            const sidebarStored = localStorage.getItem(SIDEBAR_COLLAPSED_KEY);
            this.sidebarCollapsed = sidebarStored === 'true';

            try {
                const widgetsRaw = localStorage.getItem(DASHBOARD_WIDGETS_KEY);
                if (widgetsRaw) {
                    const parsed = JSON.parse(widgetsRaw);
                    this.dashboardWidgets = {
                        ...this.dashboardWidgets,
                        ...(parsed || {}),
                    };
                }
            } catch {
                // ignore bad local storage payloads
            }
        },

        async syncFromServer() {
            const auth = useAuthStore();
            if (!auth?.isAuthenticated) return;

            try {
                const res = await apiGet('/settings');
                const settings = res?.settings || null;
                if (!settings) return;

                // Consolidated single mode. Ignore remote dark mode if present.
                this.theme = 'light';
                localStorage.setItem(THEME_STORAGE_KEY, 'light');
                applyThemeClass();

                if (typeof settings.sidebar_collapsed === 'boolean') {
                    this.sidebarCollapsed = settings.sidebar_collapsed;
                    localStorage.setItem(SIDEBAR_COLLAPSED_KEY, this.sidebarCollapsed ? 'true' : 'false');
                }

                if (settings?.dashboard_widgets && typeof settings.dashboard_widgets === 'object') {
                    this.dashboardWidgets = {
                        ...this.dashboardWidgets,
                        ...settings.dashboard_widgets,
                    };
                    localStorage.setItem(DASHBOARD_WIDGETS_KEY, JSON.stringify(this.dashboardWidgets));
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

        setTheme() {
            this.theme = 'light';
            localStorage.setItem(THEME_STORAGE_KEY, this.theme);
            applyThemeClass();
            this.persistToServer({ theme: 'light' });
        },

        toggleTheme() {
            // dark mode removed intentionally
            this.setTheme('light');
        },

        setSidebarCollapsed(next) {
            this.sidebarCollapsed = !!next;
            localStorage.setItem(SIDEBAR_COLLAPSED_KEY, this.sidebarCollapsed ? 'true' : 'false');

            this.persistToServer({ sidebar_collapsed: this.sidebarCollapsed });
        },

        toggleSidebar() {
            this.setSidebarCollapsed(!this.sidebarCollapsed);
        },

        setDashboardWidgetVisibility(widgetKey, visible) {
            if (!Object.prototype.hasOwnProperty.call(this.dashboardWidgets, widgetKey)) return;
            this.dashboardWidgets = {
                ...this.dashboardWidgets,
                [widgetKey]: !!visible,
            };
            localStorage.setItem(DASHBOARD_WIDGETS_KEY, JSON.stringify(this.dashboardWidgets));
            this.persistToServer({ dashboard_widgets: this.dashboardWidgets });
        },
    },
});
