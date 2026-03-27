<template>
  <div class="space-y-6">
    <div class="glass-dark rounded-[32px] p-8 border border-white/5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="font-display text-2xl text-white">Invoices</h2>
          <p class="text-sm text-[color:var(--p-text-muted-color)] mt-1">Invoice tracking by week and facility.</p>
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
          dataKey="id"
          stripedRows
          responsiveLayout="scroll"
          size="small"
        >
          <Column header="Invoice #">
            <template #body="{ data }">
              <RouterLink 
                :to="{ name: 'dashboard.invoice_detail', params: { id: data.id } }"
                class="font-medium text-primary hover:underline"
              >
                {{ invoiceNumber(data) }}
              </RouterLink>
            </template>
          </Column>

          <Column header="Facility">
            <template #body="{ data }">
              <span class="text-slate-200">{{ data.facility_name || '—' }}</span>
            </template>
          </Column>

          <Column header="Week">
            <template #body="{ data }">
              <span class="text-slate-200">{{ weekLabel(data) }}</span>
            </template>
          </Column>

          <Column header="Total">
            <template #body="{ data }">
              <span class="text-slate-200">{{ money(data.total_amount) }}</span>
            </template>
          </Column>

          <Column header="Paid">
            <template #body="{ data }">
              <span class="text-slate-200">{{ money(amountPaid(data)) }}</span>
            </template>
          </Column>

          <Column header="Balance">
            <template #body="{ data }">
              <span class="font-semibold" :style="{ color: balanceColor(balanceDue(data)) }">{{ money(balanceDue(data)) }}</span>
            </template>
          </Column>

          <Column header="Status">
            <template #body="{ data }">
              <Tag :value="String(data.status || 'unknown')" :severity="statusSeverity(data.status)" />
            </template>
          </Column>

          <Column header="">
            <template #body="{ data }">
              <Button
                label="Record Payment"
                size="small"
                severity="secondary"
                outlined
                @click="openRecordPayment(data)"
              />
            </template>
          </Column>
        </DataTable>
      </div>
    </div>

    <RecordPaymentModal
      :isOpen="isPaymentModalOpen"
      :invoiceId="selectedInvoiceId"
      @close="closeRecordPayment"
      @success="onPaymentRecorded"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { apiGet, normalizeApiList } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';
import RecordPaymentModal from '../../components/dashboard/RecordPaymentModal.vue';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Tag from 'primevue/tag';

const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const rows = ref([]);
const loading = ref(false);
const error = ref('');

const isPaymentModalOpen = ref(false);
const selectedInvoiceId = ref('');

function money(v) {
  const n = Number(v || 0);
  return `$${n.toFixed(2)}`;
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
  const numeric = String(id).padStart(6, '0');
  return `INV-${numeric}`;
}

function weekLabel(row) {
  const start = row?.week_start_date || row?.week_start;
  const end = row?.week_end_date || row?.week_end;
  if (!start && !end) return '—';
  if (start && end) return `${fmtDate(start)} - ${fmtDate(end)}`;
  return fmtDate(start || end);
}

function amountPaid(row) {
  if (row?.amount_paid != null) return Number(row.amount_paid || 0);
  if (Array.isArray(row?.payments)) {
    return row.payments.reduce((sum, p) => sum + Number(p?.amount || 0), 0);
  }
  return 0;
}

function balanceDue(row) {
  if (row?.balance_due != null) return Number(row.balance_due || 0);
  const total = Number(row?.total_amount || 0);
  return total - amountPaid(row);
}

function balanceColor(v) {
  const n = Number(v || 0);
  if (n <= 0) return 'rgb(34, 197, 94)';
  return 'rgb(239, 68, 68)';
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
  loading.value = true;
  error.value = '';
  try {
    const res = await apiGet('/v1/invoices');
    rows.value = normalizeApiList(res);
  } catch (e) {
    rows.value = [];
    error.value = e?.response?.data?.message || e?.message || 'Failed to load invoices';
  } finally {
    loading.value = false;
  }
}

function openRecordPayment(row) {
  selectedInvoiceId.value = String(row?.id ?? '');
  if (!selectedInvoiceId.value) return;
  isPaymentModalOpen.value = true;
}

function closeRecordPayment() {
  isPaymentModalOpen.value = false;
}

function onPaymentRecorded(updatedInvoice) {
  const inv = updatedInvoice || {};
  const id = String(inv?.id ?? '');
  if (!id) return;

  const idx = rows.value.findIndex((r) => String(r?.id ?? '') === id);
  if (idx === -1) return;

  rows.value[idx] = {
    ...rows.value[idx],
    ...inv,
  };
}

onMounted(load);
</script>
