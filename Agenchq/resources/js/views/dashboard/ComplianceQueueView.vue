<template>
  <div class="space-y-6">
    <UiPageHeader 
      title="Compliance Queue" 
      subtitle="Review and process pending worker documentation"
    >
      <template #actions>
        <InputText
          v-model="candidateFilter"
          size="small"
          placeholder="Search candidate"
          class="w-48"
          @keyup.enter="refresh"
        />
        <select
          v-model="statusFilter"
          class="px-2.5 py-2 rounded-lg text-xs bg-slate-900 border border-white/10 text-slate-200"
          @change="refresh"
        >
          <option value="pending">Pending only</option>
          <option value="all">All credentials</option>
          <option value="verified">Verified</option>
          <option value="rejected">Rejected</option>
          <option value="needs_correction">Needs correction</option>
          <option value="expired">Expired</option>
        </select>
        <Button 
          label="Refresh" 
          icon="pi pi-refresh" 
          size="small"
          :loading="loading"
          @click="refresh" 
        />
      </template>
    </UiPageHeader>

    <div v-if="loading && !items.length" class="flex justify-center py-12">
      <div class="flex flex-col items-center gap-4">
        <RefreshCw class="w-8 h-8 text-slate-500 animate-spin" />
        <span class="text-sm text-slate-400">Loading queue...</span>
      </div>
    </div>

    <div v-else-if="items.length === 0" class="aq-on-dark py-20 text-center">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-800/50 mb-4">
        <ShieldCheck class="w-8 h-8 text-slate-600" />
      </div>
      <h3 class="text-lg font-medium text-white">All caught up!</h3>
      <p class="text-slate-500 max-w-xs mx-auto mt-1">There are no documents currently awaiting review.</p>
    </div>

    <div v-else class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <UiStatCard 
          label="In view" 
          :value="metrics.pending" 
          :icon="Clock"
          color="amber"
        />
        <UiStatCard 
          label="Current Candidate" 
          :value="metrics.selectedName" 
          :icon="User"
          color="cyan"
        />
        <UiStatCard 
          label="Credential" 
          :value="metrics.selectedCredential" 
          :icon="FileText"
          color="violet"
        />
        <UiStatCard 
          label="Status" 
          :value="metrics.selectedStatus" 
          :icon="Shield"
          color="emerald"
        />
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 min-h-[600px]">
        <!-- Queue Sidebar -->
        <div class="lg:col-span-2">
          <UiCard title="Queue" :subtitle="`${items.length} items pending`" class="h-full">
            <div class="divide-y divide-white/5 -mx-6 -my-2 overflow-y-auto max-h-[600px] custom-scrollbar">
              <button
                v-for="row in items"
                :key="row.id"
                type="button"
                class="w-full text-left px-6 py-4 transition-all hover:bg-white/[0.02] relative group"
                :class="{ 'bg-white/[0.04]': row.id === selectedId }"
                @click="selectRow(row)"
              >
                <div 
                  v-if="row.id === selectedId" 
                  class="absolute left-0 top-0 bottom-0 w-1"
                  :style="{ backgroundColor: primaryColor }"
                ></div>
                
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <div class="font-semibold text-white group-hover:text-white transition-colors truncate">
                      {{ row.candidate?.name || 'Worker' }}
                    </div>
                    <div class="mt-1 text-xs text-slate-500 truncate">
                      {{ row.credential_type?.name || 'Credential' }}
                    </div>
                    <div class="mt-2 flex items-center gap-3 text-[10px] text-slate-600">
                      <span>Issued: {{ formatDate(row.issued_at) }}</span>
                      <span class="w-1 h-1 rounded-full bg-slate-800"></span>
                      <span>Expires: {{ formatDate(row.expires_at) }}</span>
                    </div>
                  </div>
                  <UiBadge variant="outline" class="shrink-0 uppercase text-[9px]">
                    {{ row.status || 'pending' }}
                  </UiBadge>
                </div>
              </button>
            </div>
          </UiCard>
        </div>

        <!-- Preview Area -->
        <div class="lg:col-span-3">
          <UiCard class="h-full flex flex-col">
            <template #header-left>
              <div class="min-w-0">
                <div class="text-[10px] font-black tracking-widest uppercase text-slate-500">Preview</div>
                <div class="mt-1 text-sm font-semibold text-white truncate">{{ selected?.candidate?.name || 'Select an item' }}</div>
                <div v-if="selected" class="mt-1 text-xs text-slate-500 truncate">{{ selected?.credential_type?.name || 'Credential' }}</div>
              </div>
            </template>

            <template #header-right>
              <div v-if="selected" class="flex items-center gap-2">
                <Button
                  v-if="previewUrl"
                  label="Open Document"
                  icon="pi pi-external-link"
                  severity="secondary"
                  size="small"
                  outlined
                  @click="openPreview"
                />
                <Button
                  v-if="selected?.candidate?.user_id"
                  label="Message Candidate"
                  icon="pi pi-comments"
                  severity="secondary"
                  size="small"
                  outlined
                  @click="messageCandidate(selected)"
                />
                <Button
                  label="Review Action"
                  icon="pi pi-verified"
                  severity="contrast"
                  size="small"
                  :disabled="!selected || actingId === selected?.id"
                  @click="openReviewModal(selected)"
                />
              </div>
            </template>

            <div class="flex-1 min-h-[400px] flex flex-col gap-4">
              <div class="aq-on-dark rounded-2xl border border-white/5 bg-black/40 overflow-hidden flex-1 relative group">
                <iframe
                  v-if="previewUrl"
                  :src="previewUrl"
                  class="w-full h-full border-0"
                  title="Document Preview"
                />
                <div v-else class="h-full flex flex-col items-center justify-center text-slate-500 px-6 text-center">
                  <FileX class="w-12 h-12 mb-3 opacity-20" />
                  <p class="text-sm">Preview not available for this document.</p>
                </div>
              </div>

              <Message v-if="error" severity="error" size="small" class="mt-3">{{ error }}</Message>
            </div>
          </UiCard>
        </div>
      </div>
    </div>
  </div>

  <Dialog
    v-model:visible="reviewModalOpen"
    modal
    header="Review Credential"
    :style="{ width: 'min(680px, 96vw)' }"
  >
    <div v-if="selected" class="space-y-4">
      <div class="rounded-xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)]/50 p-3">
        <div class="text-sm font-semibold text-[color:var(--aq-fg)]">{{ selected?.candidate?.name || 'Candidate' }}</div>
        <div class="mt-1 text-xs text-[color:var(--aq-muted)]">{{ selected?.candidate?.email || 'No email' }}</div>
        <div class="mt-2 text-xs text-[color:var(--aq-muted)]">
          Credential: {{ selected?.credential_type?.name || 'Credential' }} | Expires: {{ formatDate(selected?.expires_at) }}
        </div>
      </div>

      <div class="space-y-2">
        <div class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Action</div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
          <button
            type="button"
            class="rounded-lg border px-3 py-2 text-xs font-semibold transition"
            :class="reviewAction === 'approve' ? 'bg-emerald-500/15 border-emerald-400/40 text-emerald-300' : 'border-[color:var(--aq-border)] text-[color:var(--aq-fg)]'"
            @click="setReviewAction('approve')"
          >
            Approve
          </button>
          <button
            type="button"
            class="rounded-lg border px-3 py-2 text-xs font-semibold transition"
            :class="reviewAction === 'reject' ? 'bg-rose-500/15 border-rose-400/40 text-rose-300' : 'border-[color:var(--aq-border)] text-[color:var(--aq-fg)]'"
            @click="setReviewAction('reject')"
          >
            Reject
          </button>
          <button
            type="button"
            class="rounded-lg border px-3 py-2 text-xs font-semibold transition"
            :class="reviewAction === 'request_reupload' ? 'bg-amber-500/15 border-amber-400/40 text-amber-300' : 'border-[color:var(--aq-border)] text-[color:var(--aq-fg)]'"
            @click="setReviewAction('request_reupload')"
          >
            Request Re-upload
          </button>
        </div>
      </div>

      <div v-if="reviewNeedsReason" class="space-y-2">
        <div class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Reason</div>
        <Textarea
          v-model="reviewReason"
          rows="3"
          class="w-full"
          placeholder="Provide clear guidance for candidate"
        />
      </div>

      <Message v-if="error" severity="error" size="small">{{ error }}</Message>

      <div class="flex justify-end gap-2 pt-2">
        <Button label="Cancel" severity="secondary" outlined @click="closeReviewModal" />
        <Button
          :label="reviewAction === 'approve' ? 'Approve Credential' : (reviewAction === 'request_reupload' ? 'Request Re-upload' : 'Reject Credential')"
          :severity="reviewAction === 'approve' ? 'success' : (reviewAction === 'request_reupload' ? 'warn' : 'danger')"
          :loading="actingId === selected?.id"
          @click="submitReviewAction"
        />
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { apiGet, apiPost, normalizeApiList } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';
import UiPageHeader from '../../components/ui/UiPageHeader.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiStatCard from '../../components/ui/UiStatCard.vue';
import UiBadge from '../../components/ui/UiBadge.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import { 
  Clock, 
  User, 
  FileText, 
  Shield, 
  RefreshCw, 
  ShieldCheck, 
  FileX
} from 'lucide-vue-next';

const brand = useBrandStore();
const router = useRouter();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');

const items = ref([]);
const loading = ref(false);
const actingId = ref(null);
const selectedId = ref(null);
const statusFilter = ref('pending');
const candidateFilter = ref('');

const selected = computed(() => items.value.find((i) => i.id === selectedId.value) || null);

const metrics = computed(() => {
    const list = Array.isArray(items.value) ? items.value : [];
    const sel = selected.value;
    return {
        pending: list.length,
        selectedName: sel?.candidate?.name || '—',
        selectedCredential: sel?.credential_type?.name || '—',
        selectedStatus: String(sel?.status || 'pending'),
    };
});

const previewUrl = computed(() => {
    const row = selected.value;
    if (!row) return '';
    return (
        row.preview_url ||
        row.document_url ||
        row.file_url ||
        row.url ||
        ''
    );
});

function formatDate(v) {
    if (!v) return '—';
    const d = new Date(v);
    if (Number.isNaN(d.getTime())) return String(v);
    return d.toLocaleDateString();
}

const reviewModalOpen = ref(false);
const reviewAction = ref('approve');
const reviewReason = ref('');
const error = ref(null);
const reviewNeedsReason = computed(() => reviewAction.value !== 'approve');

async function refresh() {
    loading.value = true;
    try {
        const res = await apiGet('/v1/compliance-queue', {
            status: statusFilter.value,
            candidate: candidateFilter.value.trim() || undefined,
        });
        items.value = normalizeApiList(res);
        if (!selectedId.value && items.value.length > 0) {
            selectedId.value = items.value[0].id;
        }
        if (selectedId.value && !items.value.some((i) => i.id === selectedId.value)) {
            selectedId.value = items.value[0]?.id || null;
        }
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    refresh();
});

function selectRow(row) {
    selectedId.value = row?.id || null;
    error.value = null;
}

function openPreview() {
    if (!previewUrl.value) return;
    window.open(previewUrl.value, '_blank', 'noopener,noreferrer');
}

function messageCandidate(row) {
    const recipientId = Number(row?.candidate?.user_id || 0);
    if (!recipientId) return;
    router.push({ name: 'dashboard.messages', query: { recipient_id: String(recipientId) } });
}

async function approve(row) {
    actingId.value = row.id;
    try {
        await apiPost(`/v1/compliance-queue/${row.id}/approve`);
        await refresh();
    } finally {
        actingId.value = null;
    }
}

async function reject(row, reason) {
    const normalizedReason = String(reason || '').trim();
    if (!normalizedReason) {
        throw new Error('Please provide a reason.');
    }
    actingId.value = row.id;
    try {
        await apiPost(`/v1/compliance-queue/${row.id}/reject`, {
            reason: normalizedReason
        });
        await refresh();
    } finally {
        actingId.value = null;
    }
}

async function markNeedsCorrection(row, reason) {
    const normalizedReason = String(reason || '').trim();
    if (!normalizedReason) {
        throw new Error('Please provide a reason.');
    }
    actingId.value = row.id;
    try {
        await apiPost(`/v1/compliance-queue/${row.id}/needs-correction`, {
            reason: normalizedReason
        });
        await refresh();
    } finally {
        actingId.value = null;
    }
}

function openReviewModal(row) {
    if (!row?.id) return;
    selectedId.value = row.id;
    error.value = null;
    reviewAction.value = 'approve';
    reviewReason.value = '';
    reviewModalOpen.value = true;
}

function closeReviewModal() {
    reviewModalOpen.value = false;
    reviewReason.value = '';
    error.value = null;
}

function setReviewAction(action) {
    reviewAction.value = action;
    if (action === 'request_reupload' && !reviewReason.value.trim()) {
        reviewReason.value = 'Please re-upload a clearer or valid document.';
    }
}

async function submitReviewAction() {
    const row = selected.value;
    if (!row?.id) return;
    error.value = null;
    try {
        if (reviewAction.value === 'approve') {
            await approve(row);
        } else if (reviewAction.value === 'request_reupload') {
            const reason = reviewReason.value.trim() || 'Please re-upload this credential document.';
            await markNeedsCorrection(row, reason);
        } else {
            await reject(row, reviewReason.value);
        }
        closeReviewModal();
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Failed to submit review action.';
    }
}
</script>
