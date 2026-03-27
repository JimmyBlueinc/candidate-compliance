<template>
  <div class="space-y-6">
    <div class="glass-dark rounded-[32px] p-8 border border-white/5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <RouterLink :to="{ name: 'dashboard.invoices' }" class="text-xs font-black tracking-widest uppercase text-primary hover:underline">
              Invoices
            </RouterLink>
            <span class="text-slate-600 text-xs">/</span>
            <span class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">
              {{ invoiceNumber }}
            </span>
          </div>
          <h2 class="font-display text-2xl text-white">Invoice Details</h2>
        </div>
        <div class="flex items-center gap-2">
          <Button 
            v-if="invoice?.status === 'draft'"
            type="button" 
            label="Issue Invoice" 
            size="small" 
            :loading="issuing"
            @click="issueInvoice" 
          />
          <Button 
            type="button" 
            label="Refresh" 
            size="small" 
            outlined 
            :loading="loading" 
            @click="load" 
          />
        </div>
      </div>

      <div v-if="error" class="mt-6 text-sm text-red-400">{{ error }}</div>

      <div v-if="loading && !invoice" class="mt-8 text-slate-400">Loading invoice details...</div>

      <div v-else-if="invoice" class="mt-8 space-y-8">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="p-5 rounded-2xl bg-white/[0.03] border border-white/5">
            <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Status</div>
            <div class="mt-2">
              <Tag :value="invoice.status" :severity="statusSeverity(invoice.status)" />
            </div>
          </div>
          <div class="p-5 rounded-2xl bg-white/[0.03] border border-white/5">
            <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Facility</div>
            <div class="mt-2 text-white font-semibold truncate">{{ invoice.facility_name }}</div>
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

        <!-- Assignment Info -->
        <div class="p-6 rounded-2xl bg-white/[0.03] border border-white/5">
          <h3 class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)] mb-4">Assignment Information</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div v-if="invoice.assignment">
              <div class="text-[10px] text-slate-500 uppercase font-black">Candidate</div>
              <div class="text-white">{{ invoice.assignment.candidate_name || '—' }}</div>
            </div>
            <div>
              <div class="text-[10px] text-slate-500 uppercase font-black">Billing Period</div>
              <div class="text-white">{{ weekLabel }}</div>
            </div>
            <div>
              <div class="text-[10px] text-slate-500 uppercase font-black">Bill Rate</div>
              <div class="text-white">{{ money(invoice.bill_rate) }} / hr</div>
            </div>
          </div>
        </div>

        <!-- Line Items -->
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

        <!-- Payments -->
        <div v-if="invoice.payments?.length > 0">
          <h3 class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)] mb-4 px-2">Payment History</h3>
          <DataTable :value="invoice.payments" size="small" class="p-datatable-sm">
            <Column field="payment_date" header="Date">
              <template #body="{ data }">{{ fmtDate(data.payment_date) }}</template>
            </Column>
            <Column field="method" header="Method" />
            <Column field="reference_number" header="Ref #" />
            <Column field="amount" header="Amount">
              <template #body="{ data }">
                <span class="text-green-400 font-semibold">{{ money(data.amount) }}</span>
              </template>
            </Column>
          </DataTable>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { apiGet, apiPost } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

const route = useRoute();
const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const invoice = ref(null);
const loading = ref(false);
const issuing = ref(false);
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
  return `$${n.toFixed(2)}`;
}

function fmtDate(v) {
  if (!v) return '—';
  try {
    const d = new Date(String(v));
    if (isNaN(d.getTime())) return String(v);
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
  if (s === 'cancelled') return 'danger';
  return 'secondary';
}

async function load() {
  const id = route.params.id;
  if (!id) return;

  loading.value = true;
  error.value = '';
  try {
    const res = await apiGet(`/v1/invoices/${id}`);
    invoice.value = res?.data || res;
  } catch (e) {
    error.value = e?.message || 'Failed to load invoice details';
  } finally {
    loading.value = false;
  }
}

async function issueInvoice() {
  if (!invoice.value?.id) return;
  issuing.value = true;
  try {
    await apiPost(`/v1/invoices/${invoice.value.id}/issue`);
    await load();
  } catch (e) {
    error.value = e?.message || 'Failed to issue invoice';
  } finally {
    issuing.value = false;
  }
}

onMounted(load);
</script>
