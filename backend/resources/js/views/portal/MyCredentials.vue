<template>
  <div class="space-y-8">
    <UiPageHeader
      title="My Credentials"
      subtitle="Your uploaded documents and expiry status."
    >
      <template #actions>
        <button
          type="button"
          class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
          :style="{
            backgroundColor: primarySoftBg,
            borderColor: primarySoftBorder,
            color: primaryColor,
          }"
          @click="openCreate"
        >
          Add Credential
        </button>

        <button
          type="button"
          class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
          :style="{
            backgroundColor: primarySoftBg,
            borderColor: primarySoftBorder,
            color: primaryColor,
          }"
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
      class="p-8"
    >

      <div v-if="loading" class="mt-6 text-sm text-[color:var(--p-text-muted-color)]">Loading...</div>
      <div v-else-if="items.length === 0" class="mt-6 text-sm text-[color:var(--p-text-muted-color)]">No credentials found yet.</div>

      <div v-else class="mt-6 space-y-3">
        <div
          v-for="row in items"
          :key="row.id"
          class="p-4 rounded-2xl bg-white/[0.03] border border-white/5 hover:bg-white/[0.06] hover:border-white/10 transition-all"
        >
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <div class="font-semibold text-white truncate">{{ row.credential_type?.name || 'Credential' }}</div>
              <div class="mt-1 text-xs text-[color:var(--p-text-muted-color)]">
                Expires: {{ dateOnly(row.expires_at) }}
              </div>
              <div v-if="row.status === 'rejected' && row.latest_rejection_reason" class="mt-2 text-xs text-red-400">
                Rejected: {{ row.latest_rejection_reason }}
              </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <input
                :ref="(el) => setFileInputRef(row.id, el)"
                type="file"
                accept="application/pdf,image/*"
                class="hidden"
                @change="(e) => onFileSelected(row, e)"
              />

              <button
                type="button"
                class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
                :style="{
                  backgroundColor: primarySoftBg,
                  borderColor: primarySoftBorder,
                  color: primaryColor,
                }"
                :disabled="uploadingId === row.id"
                @click="openFilePicker(row.id)"
              >
                {{ uploadingId === row.id ? 'Uploading…' : 'Upload' }}
              </button>

              <a
                v-if="row.preview_url"
                class="px-3 py-1.5 rounded-full text-xs font-bold border border-white/10 bg-white/5 text-slate-300 hover:bg-white/10 transition-colors"
                :href="row.preview_url"
                target="_blank"
                rel="noreferrer"
              >
                View
              </a>

              <div
                class="text-[10px] font-black tracking-widest uppercase px-2 py-1 rounded-md border whitespace-nowrap"
                :style="statusStyle(row)"
              >
                {{ statusLabel(row) }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </UiCard>

    <UiCard
      v-motion
      :initial="{ opacity: 0, y: 10 }"
      :enter="{ opacity: 1, y: 0, transition: { delay: 0.06, duration: 0.35 } }"
      class="p-8"
    >
      <div class="flex items-start justify-between gap-4">
        <div>
          <h3 class="font-display text-xl text-[color:var(--aq-fg)]">Required Credentials</h3>
          <p class="text-sm text-[color:var(--p-text-muted-color)] mt-1">Your required hospital compliance checklist.</p>
        </div>
        <button
          type="button"
          class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
          :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
          @click="refreshRequirements"
        >
          Refresh
        </button>
      </div>

      <div v-if="reqLoading" class="mt-6 text-sm text-[color:var(--p-text-muted-color)]">Loading...</div>
      <div v-else-if="reqError" class="mt-6 text-sm text-red-400">{{ reqError }}</div>
      <div v-else-if="requirements.length === 0" class="mt-6 text-sm text-[color:var(--p-text-muted-color)]">No requirements configured yet.</div>

      <div v-else class="mt-6 space-y-3">
        <div
          v-for="r in requirements"
          :key="String(r.id)"
          class="p-4 rounded-2xl bg-white/[0.03] border border-white/5 hover:bg-white/[0.06] hover:border-white/10 transition-all"
        >
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <div class="font-semibold text-white truncate">{{ r.name }}</div>
              <div class="mt-1 text-xs text-[color:var(--p-text-muted-color)]">Type: {{ r.credential_type }}</div>
              <div v-if="r.default_days" class="mt-1 text-xs text-[color:var(--p-text-muted-color)]">Renewal cycle: {{ r.default_days }} days</div>
              <div v-if="r.rejection_reason" class="mt-2 text-xs text-red-300">Rejected: {{ r.rejection_reason }}</div>
              <div v-else-if="r.credential?.expiry_date" class="mt-2 text-xs text-[color:var(--p-text-muted-color)]">
                Expiry: {{ r.credential.expiry_date }}
              </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <div
                class="text-[10px] font-black tracking-widest uppercase px-2 py-1 rounded-md border whitespace-nowrap"
                :style="requirementStatusStyle(r)"
              >
                {{ requirementStatusLabel(r) }}
              </div>

              <button
                v-if="r.status === 'missing'"
                type="button"
                class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
                :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
                @click="openCreatePrefill(r)"
              >
                Add
              </button>
            </div>
          </div>
        </div>
      </div>
    </UiCard>

    <Dialog v-model:visible="createDialogOpen" modal header="Add Credential" :style="{ width: 'min(720px, 95vw)' }">
      <div class="space-y-4">
        <div class="text-sm text-[color:var(--p-text-muted-color)]">Create the credential first, then upload the supporting document for review.</div>

        <div v-if="createError" class="text-sm text-red-400">{{ createError }}</div>
        <div v-if="createFieldErrors.length" class="rounded-2xl border border-red-500/20 bg-red-500/10 p-3 text-sm text-red-200">
          <div v-for="(msg, idx) in createFieldErrors" :key="idx" class="leading-6">{{ msg }}</div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Type</label>
            <Dropdown
              v-model="createType"
              :options="credentialTypeOptions"
              optionLabel="label"
              optionValue="value"
              filter
              class="w-full"
              placeholder="Select credential"
            />
          </div>

          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Issue Date</label>
            <input
              v-model="createIssueDate"
              type="date"
              class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white outline-none focus:border-white/20"
            />
          </div>

          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Expiry Date (required)</label>
            <input
              v-model="createExpiryDate"
              type="date"
              class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white outline-none focus:border-white/20"
            />
          </div>
        </div>

        <div class="flex gap-2 justify-end">
          <button
            type="button"
            class="px-4 py-3 rounded-2xl text-xs font-bold border border-white/10 bg-white/5 text-slate-300 hover:bg-white/10"
            @click="createDialogOpen = false"
          >
            Cancel
          </button>
          <button
            type="button"
            class="px-4 py-3 rounded-2xl text-xs font-black tracking-widest uppercase border transition-colors"
            :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
            :disabled="creating"
            @click="submitCreate"
          >
            {{ creating ? 'Creating…' : 'Create' }}
          </button>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { apiGet, apiPost } from '../../lib/api';
import UiCard from '../../components/ui/UiCard.vue';
import UiPageHeader from '../../components/ui/UiPageHeader.vue';
import { useBrandStore } from '../../stores/brand';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';

const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const items = ref([]);
const loading = ref(false);
const uploadingId = ref(null);

const requirements = ref([]);
const reqLoading = ref(false);
const reqError = ref('');

function dateOnly(v) {
    if (!v) return '—';
    const d = new Date(v);
    if (Number.isNaN(d.getTime())) return String(v);
    return d.toLocaleDateString();
}

const credentialTypeOptions = computed(() => {
    const opts = (Array.isArray(requirements.value) ? requirements.value : [])
        .map((r) => ({
            label: r?.name ? `${r.name} (${r.credential_type})` : String(r?.credential_type || ''),
            value: String(r?.credential_type || '').trim(),
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

const createDialogOpen = ref(false);
const createType = ref('');
const createIssueDate = ref('');
const createExpiryDate = ref('');
const creating = ref(false);
const createError = ref('');
const createFieldErrors = ref([]);

const fileInputs = new Map();

function setFileInputRef(id, el) {
    if (!id) return;
    if (!el) {
        fileInputs.delete(id);
        return;
    }
    fileInputs.set(id, el);
}

function openFilePicker(id) {
    const el = fileInputs.get(id);
    if (el) el.click();
}

function openCreate() {
    createError.value = '';
    createFieldErrors.value = [];
    createType.value = '';
    createIssueDate.value = '';
    createExpiryDate.value = '';
    createDialogOpen.value = true;
}

function openCreatePrefill(req) {
    createError.value = '';
    createFieldErrors.value = [];
    createType.value = String(req?.credential_type || '').trim();
    createIssueDate.value = '';
    createExpiryDate.value = '';
    createDialogOpen.value = true;
}

function requirementStatusLabel(r) {
    const s = String(r?.status || 'missing');
    return s.replaceAll('_', ' ');
}

function requirementStatusStyle(r) {
    const s = String(r?.status || 'missing');
    if (s === 'approved') return { borderColor: 'rgba(34,197,94,0.35)', backgroundColor: 'rgba(34,197,94,0.10)', color: 'rgba(74,222,128,1)' };
    if (s === 'pending_review' || s === 'pending') return { borderColor: 'rgba(251,191,36,0.35)', backgroundColor: 'rgba(251,191,36,0.10)', color: 'rgba(251,191,36,1)' };
    if (s === 'rejected') return { borderColor: 'rgba(239,68,68,0.35)', backgroundColor: 'rgba(239,68,68,0.10)', color: 'rgba(248,113,113,1)' };
    if (s === 'expired') return { borderColor: 'rgba(239,68,68,0.35)', backgroundColor: 'rgba(239,68,68,0.10)', color: 'rgba(248,113,113,1)' };
    return { borderColor: 'rgba(148,163,184,0.25)', backgroundColor: 'rgba(148,163,184,0.08)', color: 'rgba(148,163,184,1)' };
}

async function refreshRequirements() {
    reqLoading.value = true;
    reqError.value = '';
    try {
        const res = await apiGet('/v1/portal/requirements');
        requirements.value = Array.isArray(res?.data) ? res.data : [];
    } catch (e) {
        requirements.value = [];
        reqError.value = e?.response?.data?.message || e?.message || 'Failed to load requirements.';
    } finally {
        reqLoading.value = false;
    }
}

async function submitCreate() {
    createError.value = '';
    createFieldErrors.value = [];
    if (!createType.value.trim() || !createIssueDate.value || !createExpiryDate.value) {
        createError.value = 'Type, issue date, and expiry date are required.';
        return;
    }

    creating.value = true;
    try {
        await apiPost('/v1/portal/credentials', {
            credential_type: createType.value.trim(),
            issue_date: createIssueDate.value,
            expiry_date: createExpiryDate.value,
        });
        createDialogOpen.value = false;
        await refresh();
    } catch (e) {
        const errors = e?.response?.data?.errors;
        if (errors && typeof errors === 'object') {
            const msgs = [];
            for (const key of Object.keys(errors)) {
                const arr = errors[key];
                if (Array.isArray(arr)) {
                    for (const m of arr) msgs.push(String(m));
                }
            }
            createFieldErrors.value = msgs;
        }
        createError.value = e?.response?.data?.message || e?.message || 'Failed to create credential.';
    } finally {
        creating.value = false;
    }
}

function statusLabel(row) {
    const s = String(row?.status || 'pending');
    if (s === 'verified') {
        const exp = row?.expires_at ? new Date(row.expires_at) : null;
        if (exp && !Number.isNaN(exp.getTime()) && exp.getTime() < Date.now()) return 'expired';
        return 'approved';
    }
    if (s === 'pending') return 'pending review';
    return s.replaceAll('_', ' ');
}

function statusStyle(row) {
    const s = String(row?.status || 'pending');
    if (s === 'pending') {
        return { backgroundColor: primarySoftBg.value, borderColor: primarySoftBorder.value, color: primaryColor.value };
    }
    if (s === 'verified') {
        const exp = row?.expires_at ? new Date(row.expires_at) : null;
        if (exp && !Number.isNaN(exp.getTime()) && exp.getTime() < Date.now()) {
            return { backgroundColor: 'rgba(239, 68, 68, 0.12)', borderColor: 'rgba(239, 68, 68, 0.25)', color: 'rgb(239, 68, 68)' };
        }
        return { backgroundColor: 'rgba(34, 197, 94, 0.12)', borderColor: 'rgba(34, 197, 94, 0.25)', color: 'rgb(34, 197, 94)' };
    }
    if (s === 'rejected') {
        return { backgroundColor: 'rgba(239, 68, 68, 0.12)', borderColor: 'rgba(239, 68, 68, 0.25)', color: 'rgb(239, 68, 68)' };
    }
    return { borderColor: 'rgba(148,163,184,0.25)', backgroundColor: 'rgba(148,163,184,0.08)', color: 'rgba(148,163,184,1)' };
}

async function onFileSelected(row, e) {
    const file = e?.target?.files?.[0] || null;
    if (!file) return;

    uploadingId.value = row.id;
    try {
        const form = new FormData();
        form.append('file', file);

        await apiPost(`/v1/portal/credentials/${row.id}/upload`, form, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        await refresh();
    } finally {
        uploadingId.value = null;
        if (e?.target) e.target.value = '';
    }
}

async function refresh() {
    loading.value = true;
    try {
        const res = await apiGet('/v1/portal/credentials');
        items.value = Array.isArray(res?.data) ? res.data : [];
    } finally {
        loading.value = false;
    }
}

refresh();
refreshRequirements();
</script>
