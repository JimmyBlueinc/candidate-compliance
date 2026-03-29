<template>
  <div class="space-y-8 pb-6 md:pb-10">
    <!-- Page Header -->
    <AppPageHeader title="Dashboard" subtitle="Today's overview across finance, compliance, and operations">
      <template #actions>
        <div class="relative">
          <AppButton variant="secondary" size="sm" @click="widgetMenuOpen = !widgetMenuOpen">
            <i class="pi pi-sliders-h text-xs" />
            Customize
          </AppButton>
          <div
            v-if="widgetMenuOpen"
            class="absolute right-0 mt-2 w-64 rounded-[var(--radius-lg)] border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-card)] p-3 z-30 shadow-xl"
          >
            <div class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)] mb-2">Dashboard Widgets</div>
            <label v-for="w in widgetToggles" :key="w.key" class="flex items-center justify-between py-1.5 text-sm">
              <span class="text-[color:var(--aq-fg)]">{{ w.label }}</span>
              <input
                type="checkbox"
                :checked="widgetEnabled(w.key)"
                @change="toggleWidget(w.key, $event.target.checked)"
              />
            </label>
          </div>
        </div>
        <AppButton v-if="featureFlagStore.enabled('dashboard.advanced_exports', true)" variant="secondary" size="sm" @click="exportFacilities">
          <i class="pi pi-download text-xs" />
          Export Facilities
        </AppButton>
        <AppButton variant="secondary" size="sm" :loading="loading || analyticsLoading" @click="refresh">
          <RefreshCw class="w-4 h-4" />
          Refresh
        </AppButton>
      </template>
    </AppPageHeader>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <AppStatCard
        label="Gross Revenue"
        :value="money(totals.gross_revenue)"
        :icon="CircleDollarSign"
        format="currency"
        color="emerald"
        :style="{ background: 'linear-gradient(145deg, color-mix(in srgb, var(--aq-primary) 15%, var(--aq-surface-card)), var(--aq-surface-card))' }"
      />
      <AppStatCard
        label="Labor Cost"
        :value="money(totals.labor_cost)"
        :icon="Wallet"
        format="currency"
        color="amber"
        :style="{ background: 'linear-gradient(145deg, color-mix(in srgb, var(--aq-accent-4) 14%, var(--aq-surface-card)), var(--aq-surface-card))' }"
      />
      <AppStatCard
        label="Net Margin"
        :value="money(totals.margin)"
        :icon="TrendingUp"
        format="currency"
        color="violet"
        :style="{ background: 'linear-gradient(145deg, color-mix(in srgb, var(--aq-accent-2) 15%, var(--aq-surface-card)), var(--aq-surface-card))' }"
      />
      <AppStatCard
        label="Projected Profit"
        :value="money(projectedProfit)"
        :icon="Sparkles"
        format="currency"
        color="cyan"
        :style="{ background: 'linear-gradient(145deg, color-mix(in srgb, var(--aq-accent-5) 14%, var(--aq-surface-card)), var(--aq-surface-card))' }"
      />
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-12 gap-8">
      <div
        v-for="widgetKey in orderedWidgetKeys"
        :key="widgetKey"
        :class="[widgetLayoutClass(widgetKey), 'dashboard-widget-card', dragOverKey === widgetKey && dragSourceKey !== widgetKey ? 'dashboard-widget-card--over' : '']"
        draggable="true"
        @dragstart="onDragStart(widgetKey, $event)"
        @dragover.prevent="onDragOver(widgetKey)"
        @dragleave="onDragLeave(widgetKey)"
        @drop.prevent="onDrop(widgetKey)"
        @dragend="onDragEnd"
      >
        <div class="dashboard-widget-handle" :title="`Drag to reorder: ${widgetLabel(widgetKey)}`">
          <i class="pi pi-bars text-[10px]" />
          <span>{{ widgetLabel(widgetKey) }}</span>
        </div>

        <AppCard v-if="widgetKey === 'facilityProfitability'" title="Facility Profitability" subtitle="Where you make (and lose) money">
          <template #actions>
            <AppBadge variant="default" size="sm">Active placements</AppBadge>
          </template>

          <div v-if="loading" class="py-8">
            <div class="space-y-3">
              <AppSkeleton v-for="i in 4" :key="i" variant="text" />
            </div>
          </div>

          <AppEmpty
            v-else-if="facility.length === 0"
            title="No active placements"
            description="Placements will appear here once created."
            :icon="Building2"
            compact
          />

          <div v-else class="overflow-x-auto -mx-6">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="border-b border-[color:var(--aq-border)]">
                  <th class="py-3 px-6 text-left text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Facility</th>
                  <th class="py-3 pr-6 text-right text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Placements</th>
                  <th class="py-3 pr-6 text-right text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Revenue</th>
                  <th class="py-3 pr-6 text-right text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Labor</th>
                  <th class="py-3 pr-6 text-right text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Margin</th>
                  <th class="py-3 pr-6 text-right text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Margin %</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[color:var(--aq-border)]">
                <tr v-for="row in facility" :key="row.facility_name" class="hover:bg-[color:var(--aq-surface-2)]/50 transition-colors">
                  <td class="py-4 px-6 font-semibold text-[color:var(--aq-fg)]">{{ row.facility_name || '—' }}</td>
                  <td class="py-4 pr-6 text-right text-[color:var(--aq-muted)]">{{ row.placements_count ?? row.count ?? '—' }}</td>
                  <td class="py-4 pr-6 text-right text-[color:var(--aq-muted)]">{{ money(row.gross_revenue ?? row.revenue) }}</td>
                  <td class="py-4 pr-6 text-right text-[color:var(--aq-muted)]">{{ money(row.labor_cost ?? row.labor) }}</td>
                  <td class="py-4 pr-6 text-right font-semibold" :style="{ color: marginColor(row.margin) }">{{ money(row.margin) }}</td>
                  <td class="py-4 pr-6 text-right font-semibold text-[color:var(--aq-primary)]">{{ pct(row.margin_pct ?? marginPct(row)) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="error" class="mt-4 px-4 py-3 rounded-[var(--radius-lg)] bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm">
            {{ error }}
          </div>
        </AppCard>

        <AppCard v-else-if="widgetKey === 'complianceTrend'" title="Compliance Trend" subtitle="Status distribution snapshot">
          <template #actions>
            <div class="flex items-center gap-1 p-1 rounded-[var(--radius-lg)] bg-[color:var(--aq-surface-2)]">
              <button
                v-for="opt in modeOptions"
                :key="opt.value"
                type="button"
                class="px-3 py-1.5 text-xs font-medium rounded-[var(--radius-md)] transition-colors"
                :class="mode === opt.value ? 'bg-[color:var(--aq-primary)] text-white' : 'text-[color:var(--aq-muted)] hover:text-[color:var(--aq-fg)]'"
                @click="mode = opt.value"
              >
                {{ opt.label }}
              </button>
            </div>
          </template>

          <div v-if="analyticsLoading" class="py-8">
            <div class="space-y-3">
              <AppSkeleton v-for="i in 3" :key="i" variant="text" />
            </div>
          </div>

          <AppEmpty
            v-else-if="analyticsData.length === 0"
            title="No analytics data"
            description="Analytics will appear here once data is available."
            :icon="BarChart3"
            compact
          />

          <div v-else class="space-y-3">
            <div
              v-for="row in analyticsData"
              :key="row.name"
              class="p-4 rounded-[var(--radius-lg)] border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)]/50"
            >
              <div class="flex items-center justify-between gap-4">
                <div class="font-medium text-[color:var(--aq-fg)]">{{ row.name }}</div>
                <div class="font-bold text-[color:var(--aq-fg)]">{{ row.value }}</div>
              </div>
              <div class="mt-3 h-2 rounded-full bg-[color:var(--aq-surface-1)] overflow-hidden">
                <div
                  class="h-full rounded-full bg-[color:var(--aq-primary)] transition-all duration-500"
                  :style="{ width: `${progressValue(row.value)}%` }"
                />
              </div>
            </div>
          </div>

          <div class="mt-6 grid grid-cols-2 gap-4">
            <div class="p-4 rounded-[var(--radius-lg)] border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)]/50">
              <div class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Expiring (30d)</div>
              <div class="text-2xl font-bold mt-2 text-[color:var(--aq-fg)]">{{ analytics?.expiring_next_30 ?? 0 }}</div>
            </div>
            <div class="p-4 rounded-[var(--radius-lg)] border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)]/50">
              <div class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Avg days to expiry</div>
              <div class="text-2xl font-bold mt-2 text-[color:var(--aq-fg)]">{{ analytics?.average_days_to_expiry ?? 0 }}</div>
            </div>
          </div>
        </AppCard>

        <AppCard v-else-if="widgetKey === 'riskExposure'" title="Risk Exposure" subtitle="Quantified compliance risk across departments">
          <div class="space-y-5">
            <div v-for="metric in riskMetrics" :key="metric.label">
              <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-[color:var(--aq-muted)]">{{ metric.label }}</span>
                <span class="text-sm font-bold" :class="metric.color">{{ metric.value }}% Risk</span>
              </div>
              <div class="h-2 rounded-full bg-[color:var(--aq-surface-1)] overflow-hidden">
                <div
                  class="h-full rounded-full transition-all duration-500"
                  :class="metric.barColor"
                  :style="{ width: `${metric.value}%` }"
                />
              </div>
            </div>
          </div>
          <div class="mt-6">
            <AppButton variant="secondary" class="w-full">
              <FileText class="w-4 h-4" />
              Generate Risk Report
            </AppButton>
          </div>
        </AppCard>

        <AppCard v-else-if="widgetKey === 'activityFeed'" title="Operational Activity" subtitle="Live system actions and change stream">
          <DashboardActivityFeed />
        </AppCard>

        <AppCard v-else-if="widgetKey === 'notifications'" title="Alert Snapshot" subtitle="Unread and actionable notifications">
          <div class="space-y-3">
            <div class="p-4 rounded-[var(--radius-lg)] border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)]/50">
              <div class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Unread Notifications</div>
              <div class="text-3xl font-bold mt-2 text-[color:var(--aq-fg)]">{{ unreadNotifications }}</div>
            </div>
            <AppButton variant="secondary" class="w-full" @click="goNotifications">
              <i class="pi pi-bell text-xs" />
              Open Notification Center
            </AppButton>
          </div>
        </AppCard>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { apiGet } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';
import { useUiStore } from '../../stores/ui';
import { useFeatureFlagStore } from '../../stores/featureFlags';
import { CircleDollarSign, Wallet, TrendingUp, Sparkles, RefreshCw, Building2, BarChart3, FileText } from 'lucide-vue-next';
import AppPageHeader from '../../components/ui/AppPageHeader.vue';
import AppCard from '../../components/ui/AppCard.vue';
import AppStatCard from '../../components/ui/AppStatCard.vue';
import AppBadge from '../../components/ui/AppBadge.vue';
import AppButton from '../../components/ui/AppButton.vue';
import AppEmpty from '../../components/ui/AppEmpty.vue';
import AppSkeleton from '../../components/ui/AppSkeleton.vue';
import DashboardActivityFeed from '../../components/dashboard/DashboardActivityFeed.vue';

const brand = useBrandStore();
const ui = useUiStore();
const featureFlagStore = useFeatureFlagStore();
const router = useRouter();

const primaryColor = computed(() => brand.primaryColor || 'var(--aq-primary)');

const loading = ref(false);
const error = ref('');
const widgetMenuOpen = ref(false);
const unreadNotifications = ref(0);

const mode = ref('by_status');
const analytics = ref(null);
const analyticsLoading = ref(false);
const dragSourceKey = ref('');
const dragOverKey = ref('');

const widgetMeta = {
  facilityProfitability: { label: 'Facility Profitability', layoutClass: 'col-span-12 lg:col-span-7' },
  complianceTrend: { label: 'Compliance Trend', layoutClass: 'col-span-12 lg:col-span-5' },
  riskExposure: { label: 'Risk Exposure', layoutClass: 'col-span-12 lg:col-span-5' },
  activityFeed: { label: 'Activity Feed', layoutClass: 'col-span-12 lg:col-span-5' },
  notifications: { label: 'Notifications', layoutClass: 'col-span-12 lg:col-span-5' },
};

const modeOptions = [
  { label: 'By status', value: 'by_status' },
  { label: 'By type', value: 'by_type' },
  { label: 'Expiring', value: 'expiring_soon' },
];

const riskMetrics = [
  { label: 'Emergency Dept', value: 12, color: 'text-emerald-400', barColor: 'bg-emerald-500' },
  { label: 'Pediatrics', value: 45, color: 'text-amber-400', barColor: 'bg-amber-500' },
  { label: 'Radiology', value: 8, color: 'text-emerald-400', barColor: 'bg-emerald-500' },
  { label: 'Surgical', value: 68, color: 'text-rose-400', barColor: 'bg-rose-500' },
];

const totals = ref({
  gross_revenue: 0,
  labor_cost: 0,
  margin: 0,
  margin_pct: 0,
  projected_revenue: 0,
});

const facility = ref([]);

const projectedProfit = computed(() => {
  const projectedRevenue = Number(totals.value?.projected_revenue || 0);
  const laborCost = Number(totals.value?.labor_cost || 0);
  return projectedRevenue - laborCost;
});

const widgetToggles = Object.entries(widgetMeta).map(([key, meta]) => ({ key, label: meta.label }));

function widgetEnabled(key) {
  return ui.dashboardWidgets?.[key] !== false;
}

function toggleWidget(key, visible) {
  ui.setDashboardWidgetVisibility(key, visible);
}

function widgetLabel(key) {
  return widgetMeta[key]?.label || key;
}

function widgetLayoutClass(key) {
  return widgetMeta[key]?.layoutClass || 'col-span-12';
}

function widgetAllowedByFlags(key) {
  if (key === 'activityFeed') {
    return featureFlagStore.enabled('dashboard.live_activity_feed', true);
  }
  return true;
}

function widgetVisible(key) {
  return widgetEnabled(key) && widgetAllowedByFlags(key);
}

const normalizedWidgetOrder = computed(() => {
  const allowedKeys = Object.keys(widgetMeta);
  const incomingOrder = Array.isArray(ui.dashboardWidgetOrder) ? ui.dashboardWidgetOrder : [];
  const ordered = [];

  for (const key of incomingOrder) {
    if (allowedKeys.includes(key) && !ordered.includes(key)) {
      ordered.push(key);
    }
  }

  for (const key of allowedKeys) {
    if (!ordered.includes(key)) {
      ordered.push(key);
    }
  }

  return ordered;
});

const orderedWidgetKeys = computed(() => normalizedWidgetOrder.value.filter((key) => widgetVisible(key)));

function onDragStart(widgetKey, event) {
  dragSourceKey.value = widgetKey;
  dragOverKey.value = '';
  if (event?.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', widgetKey);
  }
}

function onDragOver(widgetKey) {
  if (!dragSourceKey.value || dragSourceKey.value === widgetKey) return;
  dragOverKey.value = widgetKey;
}

function onDragLeave(widgetKey) {
  if (dragOverKey.value === widgetKey) {
    dragOverKey.value = '';
  }
}

function onDrop(targetKey) {
  const sourceKey = dragSourceKey.value;
  if (!sourceKey || sourceKey === targetKey) {
    onDragEnd();
    return;
  }

  const next = [...normalizedWidgetOrder.value];
  const fromIndex = next.indexOf(sourceKey);
  const toIndex = next.indexOf(targetKey);
  if (fromIndex === -1 || toIndex === -1) {
    onDragEnd();
    return;
  }

  next.splice(fromIndex, 1);
  next.splice(toIndex, 0, sourceKey);
  ui.setDashboardWidgetOrder(next);
  onDragEnd();
}

function onDragEnd() {
  dragSourceKey.value = '';
  dragOverKey.value = '';
}

function money(v) {
  const n = Number(v || 0);
  return `$${n.toFixed(2)}`;
}

function pct(v) {
  const n = Number(v || 0);
  return `${n.toFixed(2)}%`;
}

function marginPct(row) {
  const revenue = Number(row?.gross_revenue ?? row?.revenue ?? 0);
  const margin = Number(row?.margin ?? 0);
  if (!revenue) return 0;
  return (margin / revenue) * 100;
}

function marginColor(v) {
  const n = Number(v || 0);
  if (n < 0) return 'rgb(251, 113, 133)';
  if (n === 0) return 'var(--aq-muted)';
  return 'rgb(52, 211, 153)';
}

const analyticsData = computed(() => {
  if (!analytics.value) return [];

  if (mode.value === 'by_status') {
    const byStatus = analytics.value.by_status || {};
    return Object.entries(byStatus).map(([name, value]) => ({ name, value: Number(value) }));
  }

  if (mode.value === 'by_type') {
    const byType = analytics.value.by_type || {};
    return Object.entries(byType).map(([name, value]) => ({ name, value: Number(value) }));
  }

  const expiring = analytics.value.expiring_next_30 ?? 0;
  const notExpiring = Math.max(0, (analytics.value.total ?? 0) - expiring);
  return [
    { name: 'Expiring (30d)', value: Number(expiring) },
    { name: 'Not expiring', value: Number(notExpiring) },
  ];
});

const maxValue = computed(() => {
  if (analyticsData.value.length === 0) return 1;
  return Math.max(...analyticsData.value.map((d) => d.value), 1);
});

function progressValue(v) {
  return Math.round((Number(v) / maxValue.value) * 100);
}

async function loadAnalytics() {
  analyticsLoading.value = true;
  try {
    const response = await apiGet('/analytics');
    analytics.value = response || null;
  } catch {
    analytics.value = null;
  } finally {
    analyticsLoading.value = false;
  }
}

async function refresh() {
  loading.value = true;
  error.value = '';
  try {
    const res = await apiGet('/v1/finance/summary');
    totals.value = res?.data?.totals || totals.value;
    facility.value = Array.isArray(res?.data?.facility_profitability) ? res.data.facility_profitability : [];
  } catch (e) {
    error.value = e?.message || 'Failed to load.';
  } finally {
    loading.value = false;
  }

  await loadAnalytics();
  await loadNotificationCount();
}

async function loadNotificationCount() {
  try {
    const res = await apiGet('/notifications');
    const list = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : []);
    unreadNotifications.value = list.filter((n) => !n.read_at).length;
  } catch {
    unreadNotifications.value = 0;
  }
}

function goNotifications() {
  router.push({ name: 'dashboard.notifications' });
}

function exportFacilities() {
  window.open('/api/v1/facilities/export', '_blank');
}

refresh();
</script>

<style scoped>
.dashboard-widget-card {
  transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}

.dashboard-widget-card--over {
  border-radius: var(--radius-xl);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--aq-primary) 35%, transparent);
}

.dashboard-widget-handle {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  margin: 0 0 0.45rem 0.25rem;
  padding: 0.2rem 0.5rem;
  border-radius: var(--radius-md);
  border: 1px dashed color-mix(in srgb, var(--aq-border) 85%, transparent);
  color: var(--aq-muted);
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.03em;
  text-transform: uppercase;
  user-select: none;
  cursor: grab;
}

.dashboard-widget-card:active .dashboard-widget-handle {
  cursor: grabbing;
}
</style>
