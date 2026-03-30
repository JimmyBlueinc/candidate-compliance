<template>
  <div class="space-y-6">
    <div class="glass-dark rounded-[32px] p-8 border border-white/5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="font-display text-2xl text-white">Credentials</h2>
          <p class="text-sm text-[color:var(--p-text-muted-color)] mt-1">
            <span v-if="candidateName">Reviewing uploads for</span>
            <span v-else>Reviewing candidate uploads.</span>
            <span v-if="candidateName" class="font-semibold text-white">{{ candidateName }}</span>
          </p>
        </div>

        <div class="flex items-center gap-2">
          <Button type="button" label="Refresh" size="small" outlined :loading="loading" @click="refresh" />
          <Button type="button" label="Back" size="small" severity="secondary" outlined @click="goBack" />
        </div>
      </div>

      <div v-if="error" class="mt-4">
        <Message severity="error" :closable="false">{{ error }}</Message>
      </div>

      <div class="mt-6">
        <DataTable :value="rows" :loading="loading" paginator :rows="12" responsiveLayout="scroll" dataKey="id">
          <Column field="created_at" header="Uploaded" style="width: 170px">
            <template #body="{ data }">{{ dateTime(data.created_at) }}</template>
          </Column>

          <Column header="Credential" style="width: 220px">
            <template #body="{ data }">
              <div class="text-white font-semibold">{{ data.credential_type?.name || 'Credential' }}</div>
              <div class="text-xs text-[color:var(--p-text-muted-color)]" v-if="data.expires_at">Expiry: {{ dateOnly(data.expires_at) }}</div>
            </template>
          </Column>

          <Column field="status" header="Status" style="width: 160px">
            <template #body="{ data }">
              <Tag :severity="statusSeverity(data.status)" :value="data.status || '—'" />
              <div v-if="data.status === 'rejected' && data.latest_rejection_reason" class="text-xs text-red-300 mt-1">
                {{ data.latest_rejection_reason }}
              </div>
            </template>
          </Column>

          <Column header="File">
            <template #body="{ data }">
              <a
                v-if="data.preview_url"
                class="text-sm font-semibold underline"
                :href="data.preview_url"
                target="_blank"
                rel="noreferrer"
                :style="{ color: primaryColor }"
              >
                Open
              </a>
              <span v-else class="text-sm text-[color:var(--p-text-muted-color)]">—</span>
            </template>
          </Column>

          <Column header="Actions" style="width: 200px">
            <template #body="{ data }">
              <div class="flex items-center justify-end gap-2">
                <Button
                  type="button"
                  label="Accredit"
                  size="small"
                  severity="success"
                  outlined
                  :disabled="busyId === data.id"
                  @click="approve(data)"
                />
                <Button
                  type="button"
                  label="Reject"
                  size="small"
                  severity="danger"
                  outlined
                  :disabled="busyId === data.id"
                  @click="reject(data)"
                />
              </div>
            </template>
          </Column>

          <template #empty>
            <div class="py-6 text-[color:var(--p-text-muted-color)]">No uploads found for this candidate.</div>
          </template>
        </DataTable>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiGet, apiPost, normalizeApiList } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Message from 'primevue/message';
import Tag from 'primevue/tag';

const route = useRoute();
const router = useRouter();
const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');

const loading = ref(false);
const error = ref('');
const rows = ref([]);
const candidateName = ref('');

const busyId = ref(null);

function dateTime(v) {
    if (!v) return '—';
    const d = new Date(v);
    if (Number.isNaN(d.getTime())) return String(v);
    return d.toLocaleString();
}

function dateOnly(v) {
    if (!v) return '—';
    const d = new Date(v);
    if (Number.isNaN(d.getTime())) return String(v);
    return d.toLocaleDateString();
}

function statusSeverity(v) {
    if (!v) return 'secondary';
    if (v === 'verified') return 'success';
    if (v === 'rejected') return 'danger';
    if (v === 'pending') return 'warning';
    return 'secondary';
}

async function refresh() {
    loading.value = true;
    error.value = '';
    try {
        const res = await apiGet(`/v1/candidates/${encodeURIComponent(String(route.params.id))}/credentials`);
        rows.value = normalizeApiList(res?.credentials);
        candidateName.value = res?.candidate?.name || '';
    } catch (e) {
        rows.value = [];
        candidateName.value = '';
        error.value = e?.response?.data?.message || e?.message || 'Failed to load candidate uploads.';
    } finally {
        loading.value = false;
    }
}

function goBack() {
    router.push({ name: 'dashboard.candidate_profile', params: { id: route.params.id } });
}

async function approve(row) {
    if (!row?.id) return;
    busyId.value = row.id;
    try {
        await apiPost(`/v1/compliance-queue/${row.id}/approve`, {});
        await refresh();
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Failed to approve.';
    } finally {
        busyId.value = null;
    }
}

async function reject(row) {
    if (!row?.id) return;
    const reason = window.prompt('Reason for rejection (required):', row?.latest_rejection_reason || '');
    if (!reason || !String(reason).trim()) return;

    busyId.value = row.id;
    try {
        await apiPost(`/v1/compliance-queue/${row.id}/reject`, { reason: String(reason).trim() });
        await refresh();
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Failed to reject.';
    } finally {
        busyId.value = null;
    }
}

onMounted(refresh);
</script>
