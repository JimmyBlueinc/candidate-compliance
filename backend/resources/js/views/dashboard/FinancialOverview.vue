<template>
  <div class="space-y-8">
    <UiPageHeader title="Dashboard" subtitle="Today’s overview across finance, compliance, and operations">
      <template #actions>
        <Button label="Refresh" icon="pi pi-refresh" size="small" outlined :loading="loading || analyticsLoading" @click="refresh" />
      </template>
    </UiPageHeader>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <UiStatCard label="Gross Revenue" :value="money(totals.gross_revenue)" :icon="CircleDollarSign" />
      <UiStatCard label="Labor Cost" :value="money(totals.labor_cost)" :icon="Wallet" />
      <UiStatCard label="Net Margin" :value="money(totals.margin)" :icon="TrendingUp" />
      <UiStatCard label="Projected Profit" :value="money(projectedProfit)" :icon="Sparkles" />
    </div>

    <div class="grid grid-cols-12 gap-6">
      <div class="col-span-12 lg:col-span-7">
        <UiCard title="Facility Profitability" class="relative overflow-hidden">
          <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="text-sm text-[color:var(--aq-muted)]">Where you make (and lose) money</div>
            <UiBadge variant="outline" class="border-[color:var(--aq-border)] text-[color:var(--aq-muted)]">Active placements</UiBadge>
          </div>

          <div v-if="loading" class="py-8 text-sm text-[color:var(--aq-muted)]">Loading...</div>
          <div v-else-if="facility.length === 0" class="py-10 text-sm text-[color:var(--aq-muted)]">No active placements yet.</div>

          <div v-else class="-mx-6 overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="text-left text-[10px] font-black tracking-widest uppercase text-[color:var(--aq-muted)]">
                  <th class="py-3 px-6">Facility</th>
                  <th class="py-3 pr-6">Placements</th>
                  <th class="py-3 pr-6">Revenue</th>
                  <th class="py-3 pr-6">Labor</th>
                  <th class="py-3 pr-6">Margin</th>
                  <th class="py-3 pr-6">Margin %</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in facility" :key="row.facility_name" class="border-t border-[color:var(--aq-border)]">
                  <td class="py-4 px-6 font-semibold text-[color:var(--aq-fg)]">{{ row.facility_name || '—' }}</td>
                  <td class="py-4 pr-6 text-[color:var(--aq-muted)]">{{ row.placements_count ?? row.count ?? '—' }}</td>
                  <td class="py-4 pr-6 text-[color:var(--aq-muted)]">{{ money(row.gross_revenue ?? row.revenue) }}</td>
                  <td class="py-4 pr-6 text-[color:var(--aq-muted)]">{{ money(row.labor_cost ?? row.labor) }}</td>
                  <td class="py-4 pr-6 font-semibold" :style="{ color: marginColor(row.margin) }">{{ money(row.margin) }}</td>
                  <td class="py-4 pr-6 font-semibold" :style="{ color: primaryColor }">{{ pct(row.margin_pct ?? marginPct(row)) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="error" class="mt-4 text-sm text-red-400">{{ error }}</div>
        </UiCard>
      </div>

      <div class="col-span-12 lg:col-span-5 space-y-6">
        <UiCard title="Compliance Trend" class="relative overflow-hidden">
          <div class="mb-4 flex items-center justify-between gap-4">
            <div class="text-sm text-[color:var(--aq-muted)]">Status distribution snapshot</div>
            <SelectButton v-model="mode" :options="modeOptions" optionLabel="label" optionValue="value" />
          </div>

          <div v-if="analyticsLoading" class="py-8 text-sm text-[color:var(--aq-muted)]">Loading analytics...</div>

          <div v-else-if="analyticsData.length === 0" class="py-10 text-sm text-[color:var(--aq-muted)]">No analytics data</div>

          <div v-else class="space-y-3">
            <div v-for="row in analyticsData" :key="row.name" class="p-4 rounded-2xl border border-[color:var(--aq-border)] bg-white/[0.02]">
              <div class="flex items-center justify-between gap-4">
                <div class="font-semibold text-[color:var(--aq-fg)]">{{ row.name }}</div>
                <div class="font-black text-[color:var(--aq-fg)]">{{ row.value }}</div>
              </div>
              <div class="mt-3">
                <ProgressBar :value="progressValue(row.value)" :showValue="false" style="height: 0.5rem" />
              </div>
            </div>
          </div>

          <div class="mt-6 grid grid-cols-2 gap-4">
            <div class="p-4 rounded-2xl border border-[color:var(--aq-border)] bg-white/[0.02]">
              <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--aq-muted)] font-black">Expiring (30d)</div>
              <div class="text-2xl font-black mt-2 text-[color:var(--aq-fg)]">{{ analytics?.expiring_next_30 ?? 0 }}</div>
            </div>
            <div class="p-4 rounded-2xl border border-[color:var(--aq-border)] bg-white/[0.02]">
              <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--aq-muted)] font-black">Avg days to expiry</div>
              <div class="text-2xl font-black mt-2 text-[color:var(--aq-fg)]">{{ analytics?.average_days_to_expiry ?? 0 }}</div>
            </div>
          </div>
        </UiCard>

        <UiCard title="Risk Exposure">
          <div class="text-sm text-[color:var(--aq-muted)] mb-4">Quantified compliance risk across departments</div>
          <div class="space-y-6">
            <div v-for="metric in riskMetrics" :key="metric.label">
              <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-[color:var(--aq-muted)]">{{ metric.label }}</span>
                <span class="text-sm font-bold" :class="metric.color">{{ metric.value }}% Risk</span>
              </div>
              <ProgressBar :value="metric.value" :showValue="false" style="height: 0.5rem" />
            </div>
          </div>
          <div class="mt-6">
            <Button label="Generate Risk Report" severity="secondary" outlined class="w-full" />
          </div>
        </UiCard>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { apiGet } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';
import Button from 'primevue/button';
import SelectButton from 'primevue/selectbutton';
import ProgressBar from 'primevue/progressbar';
import UiPageHeader from '../../components/ui/UiPageHeader.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiStatCard from '../../components/ui/UiStatCard.vue';
import UiBadge from '../../components/ui/UiBadge.vue';
import { CircleDollarSign, Wallet, TrendingUp, Sparkles } from 'lucide-vue-next';

const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const loading = ref(false);
const error = ref('');

const mode = ref('by_status');
const analytics = ref(null);
const analyticsLoading = ref(false);

const modeOptions = [
    { label: 'By status', value: 'by_status' },
    { label: 'By type', value: 'by_type' },
    { label: 'Expiring soon', value: 'expiring_soon' },
];

const riskMetrics = [
    { label: 'Emergency Dept', value: 12, color: 'text-green-600' },
    { label: 'Pediatrics', value: 45, color: 'text-amber-600' },
    { label: 'Radiology', value: 8, color: 'text-green-600' },
    { label: 'Surgical', value: 68, color: 'text-red-600' },
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
    if (n < 0) return 'rgb(239, 68, 68)';
    if (n === 0) return 'var(--p-text-muted-color)';
    return 'rgb(34, 197, 94)';
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
}

refresh();
</script>
