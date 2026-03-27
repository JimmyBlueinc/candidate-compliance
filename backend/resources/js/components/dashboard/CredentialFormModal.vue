<template>
  <Dialog
    v-model:visible="dialogOpen"
    modal
    :header="isEdit ? 'Edit Credential' : 'Add New Credential'"
    :style="{ width: 'min(900px, 95vw)' }"
    @hide="emit('close')"
  >
    <form class="space-y-4" @submit.prevent="handleSubmit">
      <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Candidate Name</label>
          <InputText v-model="form.candidate_name" class="w-full" required />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Email</label>
          <InputText v-model="form.email" type="email" class="w-full" required />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Position</label>
          <InputText v-model="form.position" class="w-full" required />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Specialty</label>
          <InputText v-model="form.specialty" class="w-full" />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Credential Type</label>
          <Dropdown
            v-model="form.credential_type"
            :options="credentialTypeOptions"
            optionLabel="label"
            optionValue="value"
            filter
            class="w-full"
            placeholder="Select credential"
          />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Province</label>
          <InputText v-model="form.province" class="w-full" />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Issue Date</label>
          <InputText v-model="form.issue_date" type="date" class="w-full" required />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Expiry Date</label>
          <InputText v-model="form.expiry_date" type="date" class="w-full" required />
        </div>
      </div>

      <div class="space-y-2">
        <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Document (PDF/DOC)</label>
        <div class="flex items-center gap-3">
          <input type="file" accept=".pdf,.doc,.docx" @change="onFileChange" />
          <span class="text-sm text-[color:var(--p-text-muted-color)]">{{ fileLabel }}</span>
        </div>
      </div>

      <div class="flex gap-2 justify-end pt-2">
        <Button type="button" label="Cancel" severity="secondary" outlined :disabled="loading" @click="emit('close')" />
        <Button type="submit" :label="isEdit ? 'Update Credential' : 'Create Credential'" :loading="loading" />
      </div>
    </form>
  </Dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { apiGet, apiPost, normalizeApiList } from '../../lib/api';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';

const props = defineProps({
    isOpen: { type: Boolean, required: true },
    initialData: { type: Object, default: null },
    targetUserId: { type: [String, Number], default: null },
    targetEmail: { type: String, default: '' },
});

const emit = defineEmits(['close', 'success']);

const loading = ref(false);
const error = ref('');
const file = ref(null);

const templates = ref([]);

const credentialTypeOptions = computed(() => {
    const opts = (Array.isArray(templates.value) ? templates.value : [])
        .map((t) => ({
            label: t?.name ? `${t.name} (${t.credential_type})` : String(t?.credential_type || ''),
            value: String(t?.credential_type || '').trim(),
        }))
        .filter((o) => o.value);

    const seen = new Set();
    return opts.filter((o) => {
        const k = o.value.toLowerCase();
        if (seen.has(k)) return false;
        seen.add(k);
        return true;
    });
});

const form = ref({
    candidate_name: '',
    position: '',
    specialty: '',
    credential_type: '',
    email: '',
    issue_date: '',
    expiry_date: '',
    status: 'pending',
    province: '',
});

const isEdit = computed(() => Boolean(props.initialData && props.initialData.id));

const dialogOpen = computed({
    get: () => props.isOpen,
    set: (v) => {
        if (!v) emit('close');
    },
});

const fileLabel = computed(() => {
    if (file.value?.name) return file.value.name;
    if (props.initialData?.document_url) return 'Replace existing document';
    return 'Upload credential document';
});

function hydrate() {
    const d = props.initialData || {};
    form.value = {
        candidate_name: d.candidate_name || '',
        position: d.position || '',
        specialty: d.specialty || '',
        credential_type: d.credential_type || '',
        email: d.email || props.targetEmail || '',
        issue_date: d.issue_date || '',
        expiry_date: d.expiry_date || '',
        status: d.status || 'pending',
        province: d.province || '',
    };
    error.value = '';
    loading.value = false;
    file.value = null;
}

async function loadTemplates() {
    try {
        const res = await apiGet('/templates');
        templates.value = normalizeApiList(res);
    } catch {
        templates.value = [];
    }
}

watch(
    () => [props.isOpen, props.initialData, props.targetEmail],
    ([open]) => {
        if (open) {
            hydrate();
            loadTemplates();
        }
    },
    { immediate: true }
);

function onFileChange(e) {
    file.value = e?.target?.files?.[0] || null;
}

async function handleSubmit() {
    loading.value = true;
    error.value = '';

    try {
        const data = new FormData();
        Object.entries(form.value).forEach(([k, v]) => {
            if (v !== undefined && v !== null) data.append(k, String(v));
        });

        if (!isEdit.value && props.targetUserId) {
            data.append('user_id', String(props.targetUserId));
        }

        if (file.value) {
            data.append('document', file.value);
        }

        const endpoint = isEdit.value ? `/credentials/${encodeURIComponent(String(props.initialData.id))}` : '/credentials';
        if (isEdit.value) data.append('_method', 'PUT');

        await apiPost(endpoint, data);

        emit('success');
        emit('close');
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Failed to save credential';
    } finally {
        loading.value = false;
    }
}
</script>
