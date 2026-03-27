<template>
  <div class="space-y-6">
    <Card>
      <template #content>
        <div class="flex items-start justify-between gap-6">
          <div>
            <h2 class="font-display text-2xl">Predictive Analytics</h2>
            <p class="text-sm text-[color:var(--p-text-muted-color)]">Compliance trend analysis and risk exposure.</p>
          </div>
          <Button label="Refresh" size="small" severity="secondary" outlined @click="load" />
        </div>
      </template>
    </Card>

    <div v-if="loading" class="text-[color:var(--p-text-muted-color)]">Loading analytics...</div>

    <div v-else class="grid grid-cols-12 gap-6">
      <div class="col-span-12 lg:col-span-8">
        <Card>
          <template #content>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
              <h3 class="font-display text-2xl">Compliance Trend Analysis</h3>
              <SelectButton v-model="mode" :options="modeOptions" optionLabel="label" optionValue="value" />
            </div>

            <div v-if="data.length === 0" class="text-[color:var(--p-text-muted-color)]">No analytics data</div>
            <div v-else class="space-y-3">
              <div v-for="row in data" :key="row.name" class="p-4 rounded-2xl border border-[color:var(--p-surface-border)]">
                <div class="flex items-center justify-between gap-4">
                  <div class="font-semibold">{{ row.name }}</div>
                  <div class="font-black">{{ row.value }}</div>
                </div>
                <div class="mt-3">
                  <ProgressBar :value="progressValue(row.value)" :showValue="false" style="height: 0.5rem" />
                </div>
              </div>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-4">
              <div class="p-4 rounded-2xl border border-[color:var(--p-surface-border)]">
                <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Expiring next 30 days</div>
                <div class="text-2xl font-black mt-2">{{ analytics?.expiring_next_30 ?? 0 }}</div>
              </div>
              <div class="p-4 rounded-2xl border border-[color:var(--p-surface-border)]">
                <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Average days to expiry</div>
                <div class="text-2xl font-black mt-2">{{ analytics?.average_days_to_expiry ?? 0 }}</div>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <div class="col-span-12 lg:col-span-4">
        <Card>
          <template #content>
            <div class="flex flex-col h-full justify-between gap-6">
              <div>
                <h3 class="font-display text-2xl mb-2">Risk Exposure</h3>
                <p class="text-sm text-[color:var(--p-text-muted-color)]">Quantified compliance risk across departments.</p>
              </div>

              <div class="space-y-6">
                <div v-for="metric in riskMetrics" :key="metric.label">
                  <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-[color:var(--p-text-muted-color)]">{{ metric.label }}</span>
                    <span class="text-sm font-bold" :class="metric.color">{{ metric.value }}% Risk</span>
                  </div>
                  <ProgressBar :value="metric.value" :showValue="false" style="height: 0.5rem" />
                </div>
              </div>

              <Button label="Generate Risk Report" severity="secondary" outlined class="w-full" />
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { apiGet } from '../../lib/api';
import Card from 'primevue/card';
import Button from 'primevue/button';
import SelectButton from 'primevue/selectbutton';
import ProgressBar from 'primevue/progressbar';

const mode = ref('by_status');
const analytics = ref(null);
const loading = ref(true);

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

async function load() {
    try {
        loading.value = true;
        const response = await apiGet('/analytics');
        analytics.value = response || null;
    } catch {
        analytics.value = null;
    } finally {
        loading.value = false;
    }
}

const data = computed(() => {
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
    if (data.value.length === 0) return 1;
    return Math.max(...data.value.map((d) => d.value), 1);
});

function barWidth(v) {
    return `${Math.round((Number(v) / maxValue.value) * 100)}%`;
}

function progressValue(v) {
    return Math.round((Number(v) / maxValue.value) * 100);
}

onMounted(load);
</script>
