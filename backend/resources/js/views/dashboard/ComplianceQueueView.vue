<template>
  <div class="space-y-6">
    <UiPageHeader 
      title="Compliance Queue" 
      subtitle="Review and process pending worker documentation"
    >
      <template #actions>
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
          label="Pending" 
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
                  label="Approve"
                  icon="pi pi-check"
                  severity="success"
                  size="small"
                  :disabled="actingId === selected?.id"
                  @click="approve(selected)"
                />
                <Button
                  label="Reject"
                  icon="pi pi-times"
                  severity="danger"
                  size="small"
                  outlined
                  :disabled="actingId === selected?.id"
                  @click="startReject(selected)"
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

              <!-- Reject Form Overlay/Drawer -->
              <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-4"
              >
                <div v-if="rejectingId === selected?.id" class="aq-on-dark p-5 rounded-2xl bg-slate-900 border border-red-500/20 shadow-2xl mt-4">
                  <div class="flex items-center gap-2 mb-3">
                    <AlertCircle class="w-4 h-4 text-red-500" />
                    <span class="text-xs font-bold text-slate-300 uppercase tracking-wider">Rejection Details</span>
                  </div>
                  
                  <div class="flex flex-col gap-4">
                    <InputText
                      v-model="rejectReason"
                      class="w-full"
                      placeholder="Reason for rejection (e.g. Image too blurry, expired document...)"
                      @keyup.enter="confirmReject(selected)"
                    />
                    
                    <div class="mt-1 flex justify-end gap-3 border-t border-white/10 pt-4">
                      <Button label="Cancel" size="small" text @click="cancelReject" />
                      <Button 
                        label="Confirm Rejection" 
                        severity="danger" 
                        size="small" 
                        :loading="actingId === selected?.id"
                        @click="confirmReject(selected)" 
                      />
                    </div>
                  </div>
                  <Message v-if="error" severity="error" size="small" class="mt-3">{{ error }}</Message>
                </div>
              </transition>
            </div>
          </UiCard>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import { apiGet, apiPost, normalizeApiList } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';
import UiPageHeader from '../../components/ui/UiPageHeader.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiStatCard from '../../components/ui/UiStatCard.vue';
import UiBadge from '../../components/ui/UiBadge.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import { 
  Clock, 
  User, 
  FileText, 
  Shield, 
  RefreshCw, 
  ShieldCheck, 
  FileX, 
  AlertCircle 
} from 'lucide-vue-next';

const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const items = ref([]);
const loading = ref(false);
const actingId = ref(null);
const selectedId = ref(null);

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

const rejectingId = ref(null);
const rejectReason = ref('');
const error = ref(null);

async function refresh() {
    loading.value = true;
    try {
        const res = await apiGet('/v1/compliance-queue');
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
    cancelReject();
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

function startReject(row) {
    error.value = null;
    rejectingId.value = row.id;
    rejectReason.value = '';
}

function cancelReject() {
    rejectingId.value = null;
    rejectReason.value = '';
    error.value = null;
}

async function confirmReject(row) {
    if (!rejectReason.value) {
        error.value = 'Please provide a reason for rejection.';
        return;
    }
    actingId.value = row.id;
    error.value = null;
    try {
        await apiPost(`/v1/compliance-queue/${row.id}/reject`, {
            reason: rejectReason.value
        });
        await refresh();
        cancelReject();
    } catch (e) {
        error.value = e?.message || 'Failed to reject document.';
    } finally {
        actingId.value = null;
    }
}
</script>
