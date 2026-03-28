import { defineStore } from 'pinia';
import { apiGet, apiPut } from '../lib/api';
import { useAuthStore } from './auth';

const THEME_STORAGE_KEY = 'ui.theme';
const SIDEBAR_COLLAPSED_KEY = 'ui.sidebarCollapsed';
const DASHBOARD_WIDGETS_KEY = 'ui.dashboardWidgets';
const DASHBOARD_WIDGET_ORDER_KEY = 'ui.dashboardWidgetOrder';
const DEFAULT_DASHBOARD_WIDGET_ORDER = ['facilityProfitability', 'complianceTrend', 'riskExposure', 'activityFeed', 'notifications'];

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
        dashboardWidgetOrder: [...DEFAULT_DASHBOARD_WIDGET_ORDER],
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

            try {
                const widgetOrderRaw = localStorage.getItem(DASHBOARD_WIDGET_ORDER_KEY);
                if (widgetOrderRaw) {
                    const parsed = JSON.parse(widgetOrderRaw);
                    this.setDashboardWidgetOrder(parsed, { persist: false });
                } else {
                    this.dashboardWidgetOrder = [...DEFAULT_DASHBOARD_WIDGET_ORDER];
                }
            } catch {
                this.dashboardWidgetOrder = [...DEFAULT_DASHBOARD_WIDGET_ORDER];
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

                if (Array.isArray(settings?.dashboard_widget_order)) {
                    this.setDashboardWidgetOrder(settings.dashboard_widget_order, { persist: false });
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

        setDashboardWidgetOrder(order, options = {}) {
            const persist = options.persist !== false;
            const incoming = Array.isArray(order) ? order : [];
            const allowed = new Set(Object.keys(this.dashboardWidgets));
            const deduped = [];

            for (const key of incoming) {
                if (!allowed.has(key) || deduped.includes(key)) continue;
                deduped.push(key);
            }

            for (const key of DEFAULT_DASHBOARD_WIDGET_ORDER) {
                if (allowed.has(key) && !deduped.includes(key)) {
                    deduped.push(key);
                }
            }

            for (const key of Object.keys(this.dashboardWidgets)) {
                if (!deduped.includes(key)) {
                    deduped.push(key);
                }
            }

            this.dashboardWidgetOrder = deduped;
            localStorage.setItem(DASHBOARD_WIDGET_ORDER_KEY, JSON.stringify(this.dashboardWidgetOrder));

            if (persist) {
                this.persistToServer({ dashboard_widget_order: this.dashboardWidgetOrder });
            }
        },
    },
});
