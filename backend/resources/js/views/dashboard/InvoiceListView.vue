<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <AppPageHeader title="Invoices" subtitle="Invoice tracking by week and facility.">
      <template #actions>
        <AppButton variant="secondary" size="sm" @click="load">
          <RefreshCw class="w-4 h-4" />
          Refresh
        </AppButton>
      </template>
    </AppPageHeader>

    <!-- Error Message -->
    <div v-if="error" class="px-4 py-3 rounded-[var(--radius-lg)] bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm">
      {{ error }}
    </div>

    <!-- Invoices Table -->
    <AppCard title="Invoice List" subtitle="All invoices organized by billing period.">
      <div class="mb-5 rounded-[var(--radius-lg)] border border-[color:var(--aq-border)] p-4">
        <div class="mb-3 text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">
          Create Manual Invoice
        </div>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
          <input v-model="newInvoice.facility_name" type="text" class="auth-like-input" placeholder="Facility name" />
          <input v-model.number="newInvoice.total_hours" type="number" min="0" step="0.25" class="auth-like-input" placeholder="Total hours" />
          <input v-model.number="newInvoice.bill_rate" type="number" min="0" step="0.01" class="auth-like-input" placeholder="Bill rate" />
          <input v-model="newInvoice.week_start_date" type="date" class="auth-like-input" />
          <input v-model="newInvoice.week_end_date" type="date" class="auth-like-input" />
          <input v-model="newInvoice.due_at" type="date" class="auth-like-input" />
        </div>
        <div class="mt-4 flex items-center justify-end">
          <AppButton size="sm" :loading="creatingInvoice" @click="createInvoice">
            Create Invoice
          </AppButton>
        </div>
      </div>

      <div v-if="loading" class="py-8">
        <div class="space-y-3">
          <AppSkeleton v-for="i in 5" :key="i" variant="text" />
        </div>
      </div>

      <AppEmpty
        v-else-if="rows.length === 0"
        title="No invoices"
        description="Invoices will appear here once generated."
        :icon="FileText"
      />

      <div v-else class="overflow-x-auto -mx-6">
        <table class="w-full">
          <thead>
            <tr class="border-b border-[color:var(--aq-border)]">
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Invoice #</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Facility</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Week</th>
              <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Total</th>
              <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Paid</th>
              <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Balance</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Status</th>
              <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[color:var(--aq-border)]">
            <tr v-for="row in rows" :key="row.id" class="hover:bg-[color:var(--aq-surface-2)]/50 transition-colors">
              <td class="px-6 py-4">
                <RouterLink
                  :to="{ name: 'dashboard.invoice_detail', params: { id: row.id } }"
                  class="font-semibold text-[color:var(--aq-primary)] hover:underline"
                >
                  {{ invoiceNumber(row) }}
                </RouterLink>
              </td>
              <td class="px-6 py-4 text-sm text-[color:var(--aq-muted)]">{{ row.facility_name || '—' }}</td>
              <td class="px-6 py-4 text-sm text-[color:var(--aq-muted)]">{{ weekLabel(row) }}</td>
              <td class="px-6 py-4 text-right text-sm text-[color:var(--aq-muted)]">{{ money(row.total_amount) }}</td>
              <td class="px-6 py-4 text-right text-sm text-[color:var(--aq-muted)]">{{ money(amountPaid(row)) }}</td>
              <td class="px-6 py-4 text-right font-semibold" :style="{ color: balanceColor(balanceDue(row)) }">
                {{ money(balanceDue(row)) }}
              </td>
              <td class="px-6 py-4">
                <AppBadge :variant="statusVariant(row.status)" size="sm">
                  {{ row.status || 'unknown' }}
                </AppBadge>
              </td>
              <td class="px-6 py-4 text-right">
                <AppButton variant="ghost" size="sm" @click="openRecordPayment(row)">
                  <CreditCard class="w-4 h-4" />
                  Payment
                </AppButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </AppCard>

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
import { apiGet, apiPost, normalizeApiList } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';
import { RefreshCw, FileText, CreditCard } from 'lucide-vue-next';
import AppPageHeader from '../../components/ui/AppPageHeader.vue';
import AppCard from '../../components/ui/AppCard.vue';
import AppButton from '../../components/ui/AppButton.vue';
import AppBadge from '../../components/ui/AppBadge.vue';
import AppEmpty from '../../components/ui/AppEmpty.vue';
import AppSkeleton from '../../components/ui/AppSkeleton.vue';
import RecordPaymentModal from '../../components/dashboard/RecordPaymentModal.vue';

const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--aq-primary)');

const rows = ref([]);
const loading = ref(false);
const error = ref('');

const isPaymentModalOpen = ref(false);
const selectedInvoiceId = ref('');
const creatingInvoice = ref(false);
const newInvoice = ref({
  facility_name: '',
  week_start_date: '',
  week_end_date: '',
  total_hours: '',
  bill_rate: '',
  due_at: '',
});

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
  if (n <= 0) return 'rgb(52, 211, 153)';
  return 'rgb(251, 113, 133)';
}

function statusVariant(status) {
  const s = String(status || '').toLowerCase();
  if (s === 'paid') return 'success';
  if (s === 'issued') return 'info';
  if (s === 'draft') return 'warning';
  if (s === 'cancelled' || s === 'canceled') return 'danger';
  return 'default';
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

async function createInvoice() {
  if (creatingInvoice.value) return;
  creatingInvoice.value = true;
  error.value = '';
  try {
    await apiPost('/v1/invoices', {
      facility_name: String(newInvoice.value.facility_name || '').trim(),
      week_start_date: newInvoice.value.week_start_date,
      week_end_date: newInvoice.value.week_end_date,
      total_hours: Number(newInvoice.value.total_hours || 0),
      bill_rate: Number(newInvoice.value.bill_rate || 0),
      due_at: newInvoice.value.due_at || null,
    });
    newInvoice.value = {
      facility_name: '',
      week_start_date: '',
      week_end_date: '',
      total_hours: '',
      bill_rate: '',
      due_at: '',
    };
    await load();
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to create invoice';
  } finally {
    creatingInvoice.value = false;
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

<style scoped>
.auth-like-input {
  width: 100%;
  border-radius: 0.75rem;
  border: 1px solid color-mix(in srgb, var(--aq-border) 88%, transparent);
  background: color-mix(in srgb, var(--aq-surface-1) 88%, transparent);
  padding: 0.55rem 0.7rem;
  font-size: 0.8rem;
}
</style>
