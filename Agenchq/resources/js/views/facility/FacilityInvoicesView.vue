<template>
  <div class="space-y-8">
    <UiPageHeader title="Invoices" subtitle="Read-only invoices issued to your facility.">
      <template #actions>
        <button
          type="button"
          class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
          :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
          @click="refresh"
        >
          Refresh
        </button>
      </template>
    </UiPageHeader>

    <UiCard
      v-motion
      :initial="{ opacity: 0, y: 10 }"
      :enter="{ opacity: 1, y: 0, transition: { duration: 0.35 } }"
      class="p-6"
    >
      <DataTable
        :value="rows"
        :loading="loading"
        dataKey="id"
        class="p-datatable-sm"
        responsiveLayout="stack"
        breakpoint="960px"
      >
          <Column header="Invoice #" sortable>
            <template #body="{ data }">
              <RouterLink
                :to="{ name: 'facility.invoice_detail', params: { id: data.id } }"
                class="font-semibold text-primary hover:underline"
              >
                {{ invoiceNumber(data) }}
              </RouterLink>
            </template>
          </Column>
          <Column header="Billing Period" sortable>
            <template #body="{ data }">
              <span class="text-slate-300 text-xs">{{ data.billing_period || weekLabel(data) }}</span>
            </template>
          </Column>
          <Column header="Amount">
            <template #body="{ data }">
              <span class="font-bold text-white">{{ money(data.amount ?? data.total_amount) }}</span>
            </template>
          </Column>
          <Column field="status" header="Status">
            <template #body="{ data }">
              <Tag :value="String(data.status || 'unknown')" :severity="statusSeverity(data.status)" />
            </template>
          </Column>

          <template #empty>
            <div class="py-6 text-center text-slate-500">No invoices found.</div>
          </template>
      </DataTable>
    </UiCard>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { apiGet, normalizeApiList } from '../../lib/api';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import UiCard from '../../components/ui/UiCard.vue';
import UiPageHeader from '../../components/ui/UiPageHeader.vue';
import { useBrandStore } from '../../stores/brand';

const brand = useBrandStore();
const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const rows = ref([]);
const loading = ref(false);

function money(v) {
  const n = Number(v || 0);
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(n);
}

function fmtDate(v) {
  if (!v) return '—';
  try {
    const d = new Date(String(v));
    if (Number.isNaN(d.getTime())) return String(v);
    return d.toLocaleDateString();
  } catch {
    return String(v);
  }
}

function invoiceNumber(row) {
  if (row?.invoice_number) return String(row.invoice_number);
  const id = row?.id;
  if (!id && id !== 0) return '—';
  return `INV-${String(id).padStart(6, '0')}`;
}

function weekLabel(row) {
  const start = row?.week_start_date || row?.week_start;
  const end = row?.week_end_date || row?.week_end;
  if (!start && !end) return '—';
  if (start && end) return `${fmtDate(start)} - ${fmtDate(end)}`;
  return fmtDate(start || end);
}

function statusSeverity(status) {
  const s = String(status || '').toLowerCase();
  if (s === 'paid') return 'success';
  if (s === 'issued') return 'info';
  if (s === 'draft') return 'warning';
  if (s === 'cancelled' || s === 'canceled') return 'danger';
  return 'secondary';
}

async function refresh() {
  loading.value = true;
  try {
    const res = await apiGet('/v1/facility/invoices');
    rows.value = normalizeApiList(res);
  } finally {
    loading.value = false;
  }
}

onMounted(refresh);
</script>
