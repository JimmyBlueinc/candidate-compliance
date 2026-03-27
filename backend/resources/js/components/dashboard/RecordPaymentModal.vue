<template>
  <Dialog
    v-model:visible="dialogOpen"
    modal
    header="Record Payment"
    :style="{ width: 'min(720px, 95vw)' }"
    @hide="emit('close')"
  >
    <form class="space-y-4" @submit.prevent="handleSubmit">
      <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Invoice ID</label>
          <InputText v-model="form.invoice_id" class="w-full" disabled />
        </div>

        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Amount</label>
          <InputText v-model="form.amount" class="w-full" inputmode="decimal" required />
        </div>

        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Payment Date</label>
          <InputText v-model="form.payment_date" type="date" class="w-full" required />
        </div>

        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Payment Method</label>
          <InputText v-model="form.payment_method" class="w-full" required />
        </div>

        <div class="space-y-2 md:col-span-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Reference Number (Optional)</label>
          <InputText v-model="form.reference_number" class="w-full" />
        </div>
      </div>

      <div class="flex gap-2 justify-end pt-2">
        <Button type="button" label="Cancel" severity="secondary" outlined :disabled="loading" @click="emit('close')" />
        <Button type="submit" label="Save Payment" :loading="loading" />
      </div>
    </form>
  </Dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { apiPost } from '../../lib/api';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';

const props = defineProps({
  isOpen: { type: Boolean, required: true },
  invoiceId: { type: [String, Number], required: true },
});

const emit = defineEmits(['close', 'success']);

const loading = ref(false);
const error = ref('');

const dialogOpen = computed({
  get: () => props.isOpen,
  set: (v) => {
    if (!v) emit('close');
  },
});

const form = ref({
  invoice_id: '',
  amount: '',
  payment_date: '',
  payment_method: '',
  reference_number: '',
});

function todayISO() {
  const d = new Date();
  const yyyy = d.getFullYear();
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  return `${yyyy}-${mm}-${dd}`;
}

function hydrate() {
  form.value = {
    invoice_id: String(props.invoiceId ?? ''),
    amount: '',
    payment_date: todayISO(),
    payment_method: '',
    reference_number: '',
  };
  loading.value = false;
  error.value = '';
}

watch(
  () => [props.isOpen, props.invoiceId],
  ([open]) => {
    if (open) hydrate();
  },
  { immediate: true }
);

async function handleSubmit() {
  loading.value = true;
  error.value = '';

  try {
    const payload = {
      invoice_id: form.value.invoice_id,
      amount: form.value.amount,
      payment_date: form.value.payment_date,
      payment_method: form.value.payment_method,
      reference_number: form.value.reference_number || undefined,
    };

    const res = await apiPost('/v1/payments', payload);

    emit('success', res?.data);
    emit('close');
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to record payment';
  } finally {
    loading.value = false;
  }
}
</script>
