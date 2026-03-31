<template>
  <div class="space-y-6">
    <div class="glass-dark rounded-[32px] p-8 border border-white/5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="font-display text-2xl text-white">Accounts Receivable</h2>
          <p class="text-sm text-[color:var(--p-text-muted-color)] mt-1">AR aging grouped by facility.</p>
        </div>
        <button
          type="button"
          class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
          :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
          @click="load"
        >
          Refresh
        </button>
      </div>

      <div v-if="error" class="mt-6 text-sm text-red-400">{{ error }}</div>

      <div class="mt-6">
        <DataTable
          :value="rows"
          :loading="loading"
          dataKey="facility_name"
          stripedRows
          responsiveLayout="scroll"
          size="small"
        >
          <Column field="facility_name" header="Facility">
            <template #body="{ data }">
              <span class="font-medium">{{ data.facility_name || '—' }}</span>
            </template>
          </Column>
          <Column header="0-30">
            <template #body="{ data }">
              <span class="text-slate-200">{{ money(data.bucket_0_30) }}</span>
            </template>
          </Column>
          <Column header="31-60">
            <template #body="{ data }">
              <span class="text-slate-200">{{ money(data.bucket_31_60) }}</span>
            </template>
          </Column>
          <Column header="61-90">
            <template #body="{ data }">
              <span class="text-slate-200">{{ money(data.bucket_61_90) }}</span>
            </template>
          </Column>
          <Column header="90+">
            <template #body="{ data }">
              <span class="text-slate-200">{{ money(data.bucket_90_plus) }}</span>
            </template>
          </Column>
          <Column header="Total AR">
            <template #body="{ data }">
              <span class="font-semibold" :style="{ color: primaryColor }">{{ money(data.total_ar) }}</span>
            </template>
          </Column>
        </DataTable>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { apiGet } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';

const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const rows = ref([]);
const loading = ref(false);
const error = ref('');

function money(v) {
  const n = Number(v || 0);
  return `$${n.toFixed(2)}`;
}

function normalizeAgingToRows(aging) {
  const byFacility = aging?.by_facility || {};
  return Object.entries(byFacility).map(([facility, data]) => {
    const buckets = data?.buckets || {};
    return {
      facility_name: facility,
      bucket_0_30: Number(buckets['0-30'] || 0),
      bucket_31_60: Number(buckets['31-60'] || 0),
      bucket_61_90: Number(buckets['61-90'] || 0),
      bucket_90_plus: Number(buckets['90+'] || 0),
      total_ar: Number(data?.total_ar || 0),
    };
  });
}

async function load() {
  loading.value = true;
  error.value = '';
  try {
    const res = await apiGet('/v1/billing/analytics');
    const aging = res?.data?.aging || {};
    rows.value = normalizeAgingToRows(aging);
  } catch (e) {
    rows.value = [];
    error.value = e?.response?.data?.message || e?.message || 'Failed to load accounts receivable analytics';
  } finally {
    loading.value = false;
  }
}

onMounted(load);
</script>
