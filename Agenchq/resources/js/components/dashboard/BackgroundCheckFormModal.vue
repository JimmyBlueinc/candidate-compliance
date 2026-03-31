<template>
  <Dialog
    v-model:visible="dialogOpen"
    modal
    :header="`${isEdit ? 'Edit' : 'Add'} Background Check`"
    :style="{ width: 'min(900px, 95vw)' }"
    @hide="emit('close')"
  >
    <form class="space-y-4" @submit.prevent="submit">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Candidate Name</label>
          <InputText v-model="form.candidate_name" class="w-full" required />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Check Type</label>
          <Dropdown v-model="form.check_type" :options="checkTypeOptions" optionLabel="label" optionValue="value" class="w-full" />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Issue Date</label>
          <InputText v-model="form.issue_date" type="date" class="w-full" />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Expiry Date</label>
          <InputText v-model="form.expiry_date" type="date" class="w-full" />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Verification Status</label>
          <Dropdown v-model="form.verification_status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Document (optional)</label>
          <input type="file" class="block w-full" @change="onFileChange" />
        </div>
      </div>

      <div class="space-y-2">
        <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Notes</label>
        <Textarea v-model="form.notes" rows="3" class="w-full" />
      </div>

      <Message v-if="formError" severity="error" :closable="false">{{ formError }}</Message>

      <div class="flex gap-2 justify-end pt-2">
        <Button type="button" label="Cancel" severity="secondary" outlined @click="emit('close')" />
        <Button type="submit" label="Save" :loading="saving" />
      </div>
    </form>
  </Dialog>
</template>

<script setup>
import axios from 'axios';
import { computed, ref, watch } from 'vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Textarea from 'primevue/textarea';

const props = defineProps({
    isOpen: { type: Boolean, required: true },
    initialData: { type: Object, default: null },
    selectedAdminId: { type: String, default: '' },
    attachUserId: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'success']);

const saving = ref(false);
const formError = ref('');
const documentFile = ref(null);

const form = ref({
    candidate_name: '',
    check_type: 'criminal_record_check',
    issue_date: '',
    expiry_date: '',
    verification_status: 'pending',
    notes: '',
});

const isEdit = computed(() => Boolean(props.initialData && props.initialData.id));

const dialogOpen = computed({
    get: () => props.isOpen,
    set: (v) => {
        if (!v) emit('close');
    },
});

const checkTypeOptions = [
    { label: 'Criminal Record Check', value: 'criminal_record_check' },
    { label: 'Vulnerable Sector Check', value: 'vulnerable_sector_check' },
    { label: 'Security Clearance', value: 'security_clearance' },
    { label: 'Other', value: 'other' },
];

const statusOptions = [
    { label: 'Pending', value: 'pending' },
    { label: 'Verified', value: 'verified' },
    { label: 'Failed', value: 'failed' },
    { label: 'Expired', value: 'expired' },
];

watch(
    () => [props.isOpen, props.initialData],
    ([open]) => {
        if (!open) return;
        const row = props.initialData || {};
        documentFile.value = null;
        formError.value = '';
        form.value = {
            candidate_name: row?.candidate_name || '',
            check_type: row?.check_type || 'criminal_record_check',
            issue_date: row?.issue_date || '',
            expiry_date: row?.expiry_date || '',
            verification_status: row?.verification_status || 'pending',
            notes: row?.notes || '',
        };
    },
    { immediate: true }
);

function onFileChange(e) {
    documentFile.value = e?.target?.files?.[0] || null;
}

async function submit() {
    try {
        saving.value = true;
        formError.value = '';

        const fd = new FormData();
        fd.append('candidate_name', String(form.value.candidate_name || ''));
        fd.append('check_type', String(form.value.check_type || ''));
        if (form.value.issue_date) fd.append('issue_date', String(form.value.issue_date));
        if (form.value.expiry_date) fd.append('expiry_date', String(form.value.expiry_date));
        if (form.value.verification_status) fd.append('verification_status', String(form.value.verification_status));
        if (form.value.notes) fd.append('notes', String(form.value.notes));

        if (props.attachUserId && props.selectedAdminId) {
            fd.append('user_id', String(props.selectedAdminId));
        }

        if (documentFile.value) {
            fd.append('document', documentFile.value);
        }

        if (!isEdit.value) {
            await axios.post('/api/background-checks', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
        } else {
            fd.append('_method', 'PUT');
            await axios.post(`/api/background-checks/${encodeURIComponent(String(props.initialData.id))}`, fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
        }

        emit('success');
        emit('close');
    } catch (e) {
        formError.value = e?.response?.data?.message || e?.message || 'Failed to save record';
    } finally {
        saving.value = false;
    }
}
</script>
