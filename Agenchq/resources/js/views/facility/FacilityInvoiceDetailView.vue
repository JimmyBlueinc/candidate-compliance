<template>
  <div class="space-y-8">
    <UiPageHeader title="Invoice Details">
      <template #subtitle>
        <div class="flex items-center gap-2">
          <RouterLink :to="{ name: 'facility.invoices' }" class="text-xs font-black tracking-widest uppercase text-primary hover:underline">
            Invoices
          </RouterLink>
          <span class="text-slate-600 text-xs">/</span>
          <span class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">
            {{ invoiceNumber }}
          </span>
        </div>
      </template>
      <template #actions>
        <button
          type="button"
          class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
          :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
          :disabled="loading"
          @click="load"
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
      <div v-if="error" class="text-sm text-red-400">{{ error }}</div>

      <div v-if="loading && !invoice" class="text-slate-400">Loading invoice details...</div>

      <div v-else-if="invoice" class="space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="p-5 rounded-2xl bg-white/[0.03] border border-white/5">
            <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Status</div>
            <div class="mt-2">
              <Tag :value="invoice.status" :severity="statusSeverity(invoice.status)" />
            </div>
          </div>
          <div class="p-5 rounded-2xl bg-white/[0.03] border border-white/5">
            <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Billing Period</div>
            <div class="mt-2 text-white font-semibold truncate">{{ weekLabel }}</div>
          </div>
          <div class="p-5 rounded-2xl bg-white/[0.03] border border-white/5">
            <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Total Amount</div>
            <div class="mt-2 text-xl font-display text-white">{{ money(invoice.total_amount) }}</div>
          </div>
          <div class="p-5 rounded-2xl bg-white/[0.03] border border-white/5">
            <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Total Hours</div>
            <div class="mt-2 text-xl font-display text-white">{{ invoice.total_hours }} hrs</div>
          </div>
        </div>

        <div>
          <h3 class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)] mb-4 px-2">Invoice Line Items</h3>
          <DataTable :value="invoice.line_items || []" size="small" class="p-datatable-sm">
            <Column field="description" header="Description">
              <template #body="{ data }">
                <span class="text-slate-200">{{ data.description || 'Hours Worked' }}</span>
              </template>
            </Column>
            <Column field="hours" header="Hours" style="width: 100px">
              <template #body="{ data }">
                <span class="text-white">{{ data.hours }}</span>
              </template>
            </Column>
            <Column field="bill_rate" header="Rate" style="width: 120px">
              <template #body="{ data }">
                <span class="text-slate-300">{{ money(data.bill_rate) }}</span>
              </template>
            </Column>
            <Column field="amount" header="Subtotal" style="width: 120px">
              <template #body="{ data }">
                <span class="text-white font-semibold">{{ money(data.amount) }}</span>
              </template>
            </Column>
            <template #empty>
              <div class="py-4 text-center text-slate-500">No line items found.</div>
            </template>
          </DataTable>
        </div>
      </div>
    </UiCard>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { apiGet } from '../../lib/api';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import UiCard from '../../components/ui/UiCard.vue';
import UiPageHeader from '../../components/ui/UiPageHeader.vue';
import { useBrandStore } from '../../stores/brand';

const route = useRoute();

const brand = useBrandStore();
const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const invoice = ref(null);
const loading = ref(false);
const error = ref('');

const invoiceNumber = computed(() => {
  if (invoice.value?.invoice_number) return invoice.value.invoice_number;
  const id = invoice.value?.id;
  if (!id && id !== 0) return '—';
  return `INV-${String(id).padStart(6, '0')}`;
});

const weekLabel = computed(() => {
  const start = invoice.value?.week_start_date || invoice.value?.week_start;
  const end = invoice.value?.week_end_date || invoice.value?.week_end;
  if (!start && !end) return '—';
  if (start && end) return `${fmtDate(start)} - ${fmtDate(end)}`;
  return fmtDate(start || end);
});

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

function statusSeverity(status) {
  const s = String(status || '').toLowerCase();
  if (s === 'paid') return 'success';
  if (s === 'issued') return 'info';
  if (s === 'draft') return 'warning';
  if (s === 'cancelled' || s === 'canceled') return 'danger';
  return 'secondary';
}

async function load() {
  const id = route.params.id;
  if (!id) return;

  loading.value = true;
  error.value = '';
  try {
    const res = await apiGet(`/v1/facility/invoices/${id}`);
    invoice.value = res?.data || res;
  } catch (e) {
    invoice.value = null;
    error.value = e?.response?.data?.message || e?.message || 'Failed to load invoice details';
  } finally {
    loading.value = false;
  }
}

onMounted(load);
</script>
