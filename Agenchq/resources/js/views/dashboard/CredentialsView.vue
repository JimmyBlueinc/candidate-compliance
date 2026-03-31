<template>
  <div class="space-y-6">
    <Card>
      <template #content>
        <div class="flex items-center justify-between gap-3">
          <div>
            <h2 class="font-display text-2xl">Credentials</h2>
            <p class="text-sm text-[color:var(--p-text-muted-color)]">Select a candidate to view uploaded credentials and update status.</p>
          </div>
          <Button label="Refresh" icon="pi pi-refresh" :loading="candidateLoading" @click="loadCandidates" />
        </div>
      </template>
    </Card>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4">
      <Card class="xl:col-span-2">
        <template #content>
          <div class="space-y-3">
            <InputText v-model="candidateQuery" placeholder="Search candidate name or email" class="w-full" />
            <DataTable
              :value="filteredCandidates"
              :loading="candidateLoading"
              dataKey="id"
              responsiveLayout="scroll"
              scrollable
              scrollHeight="560px"
              selectionMode="single"
              :selection="selectedCandidate"
              @row-select="onSelectCandidate"
            >
              <Column field="name" header="Candidate">
                <template #body="{ data }">
                  <div class="font-semibold">{{ data.name || `${data.first_name || ''} ${data.last_name || ''}`.trim() || '—' }}</div>
                  <div class="text-xs text-[color:var(--p-text-muted-color)]">{{ data.email || '—' }}</div>
                </template>
              </Column>
              <Column field="specialty" header="Specialty" />
              <template #empty>
                <div class="py-4 text-[color:var(--p-text-muted-color)]">No candidates found.</div>
              </template>
            </DataTable>
          </div>
        </template>
      </Card>

      <Card class="xl:col-span-3">
        <template #content>
          <div class="flex items-center justify-between gap-2 mb-3">
            <div class="text-sm text-[color:var(--p-text-muted-color)]">
              <span v-if="selectedCandidate">
                Credentials for <span class="font-semibold text-[color:var(--aq-fg)]">{{ selectedCandidate.name || selectedCandidate.email }}</span>
              </span>
              <span v-else>Select a candidate to view credentials.</span>
            </div>
            <Button
              v-if="selectedCandidate"
              label="Open Candidate"
              icon="pi pi-user"
              severity="secondary"
              outlined
              size="small"
              @click="openCandidateProfile"
            />
          </div>

          <DataTable :value="credentialRows" :loading="credentialLoading" dataKey="id" responsiveLayout="scroll">
            <Column header="Credential">
              <template #body="{ data }">
                <div class="font-semibold">{{ data.credential_type?.name || 'Credential' }}</div>
                <div class="text-xs text-[color:var(--p-text-muted-color)]">
                  Issued: {{ dateOnly(data.issued_at) }} | Expires: {{ dateOnly(data.expires_at) }}
                </div>
              </template>
            </Column>
            <Column header="Status" style="width: 1%; white-space: nowrap">
              <template #body="{ data }">
                <Tag :severity="statusSeverity(data.status)" :value="data.status || '—'" />
                <div
                  v-if="(data.status === 'rejected' || data.status === 'needs_correction') && data.latest_rejection_reason"
                  class="text-xs text-rose-400 mt-1"
                >
                  {{ data.latest_rejection_reason }}
                </div>
              </template>
            </Column>
            <Column header="File" style="width: 1%; white-space: nowrap">
              <template #body="{ data }">
                <a
                  v-if="data.preview_url"
                  :href="data.preview_url"
                  target="_blank"
                  rel="noreferrer"
                  class="text-sm font-semibold underline"
                >
                  Open
                </a>
                <span v-else class="text-sm text-[color:var(--p-text-muted-color)]">—</span>
              </template>
            </Column>
            <Column header="Actions" style="width: 1%; white-space: nowrap">
              <template #body="{ data }">
                <div class="flex items-center gap-2">
                  <Button
                    label="Approve"
                    size="small"
                    severity="success"
                    outlined
                    :disabled="busyCredentialId === data.id || data.status === 'verified'"
                    @click="approveCredential(data)"
                  />
                  <Button
                    label="Reject"
                    size="small"
                    severity="danger"
                    outlined
                    :disabled="busyCredentialId === data.id"
                    @click="rejectCredential(data)"
                  />
                  <Button
                    label="Needs Correction"
                    size="small"
                    severity="warning"
                    outlined
                    :disabled="busyCredentialId === data.id"
                    @click="needsCorrectionCredential(data)"
                  />
                </div>
              </template>
            </Column>
            <template #empty>
              <div class="py-6 text-[color:var(--p-text-muted-color)]">No credentials uploaded for this candidate.</div>
            </template>
          </DataTable>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { apiGet, apiPost, normalizeApiList } from '../../lib/api';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Tag from 'primevue/tag';

const router = useRouter();

const error = ref('');
const candidateLoading = ref(false);
const credentialLoading = ref(false);
const busyCredentialId = ref(null);

const candidates = ref([]);
const candidateQuery = ref('');
const selectedCandidate = ref(null);
const credentialRows = ref([]);

const filteredCandidates = computed(() => {
  const q = String(candidateQuery.value || '').trim().toLowerCase();
  if (!q) return candidates.value;
  return candidates.value.filter((c) => {
    const name = String(c?.name || `${c?.first_name || ''} ${c?.last_name || ''}`).toLowerCase();
    const email = String(c?.email || '').toLowerCase();
    return name.includes(q) || email.includes(q);
  });
});

function dateOnly(v) {
  if (!v) return '—';
  const d = new Date(v);
  if (Number.isNaN(d.getTime())) return String(v);
  return d.toLocaleDateString();
}

function statusSeverity(status) {
  const s = String(status || '').toLowerCase();
  if (s === 'verified') return 'success';
  if (s === 'pending') return 'warning';
  if (s === 'rejected') return 'danger';
  if (s === 'needs_correction') return 'warning';
  if (s === 'expired') return 'danger';
  return 'secondary';
}

async function loadCandidates() {
  candidateLoading.value = true;
  error.value = '';
  try {
    const res = await apiGet('/v1/candidates');
    const payload = res?.data || res || {};
    candidates.value = Array.isArray(payload) ? payload : [];

    if (!selectedCandidate.value && candidates.value.length > 0) {
      selectedCandidate.value = candidates.value[0];
      await loadCandidateCredentials(selectedCandidate.value.id);
    } else if (selectedCandidate.value) {
      const found = candidates.value.find((c) => Number(c.id) === Number(selectedCandidate.value?.id));
      if (found) {
        selectedCandidate.value = found;
      } else {
        selectedCandidate.value = null;
        credentialRows.value = [];
      }
    }
  } catch (e) {
    candidates.value = [];
    credentialRows.value = [];
    error.value = e?.response?.data?.message || e?.message || 'Failed to load candidates.';
  } finally {
    candidateLoading.value = false;
  }
}

async function loadCandidateCredentials(candidateId) {
  if (!candidateId) {
    credentialRows.value = [];
    return;
  }
  credentialLoading.value = true;
  error.value = '';
  try {
    const res = await apiGet(`/v1/candidates/${encodeURIComponent(String(candidateId))}/credentials`);
    const payload = res?.data || res || {};
    credentialRows.value = normalizeApiList(payload?.credentials);
  } catch (e) {
    credentialRows.value = [];
    error.value = e?.response?.data?.message || e?.message || 'Failed to load candidate credentials.';
  } finally {
    credentialLoading.value = false;
  }
}

async function onSelectCandidate(event) {
  const row = event?.data || null;
  selectedCandidate.value = row;
  await loadCandidateCredentials(row?.id);
}

async function approveCredential(row) {
  if (!row?.id) return;
  busyCredentialId.value = row.id;
  error.value = '';
  try {
    await apiPost(`/v1/compliance-queue/${row.id}/approve`, {});
    await loadCandidateCredentials(selectedCandidate.value?.id);
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to approve credential.';
  } finally {
    busyCredentialId.value = null;
  }
}

async function rejectCredential(row) {
  if (!row?.id) return;
  const reason = window.prompt('Reason for rejection (required):', row?.latest_rejection_reason || '');
  if (!reason || !String(reason).trim()) return;
  busyCredentialId.value = row.id;
  error.value = '';
  try {
    await apiPost(`/v1/compliance-queue/${row.id}/reject`, { reason: String(reason).trim() });
    await loadCandidateCredentials(selectedCandidate.value?.id);
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to reject credential.';
  } finally {
    busyCredentialId.value = null;
  }
}

async function needsCorrectionCredential(row) {
  if (!row?.id) return;
  const reason = window.prompt('Reason for re-upload request (required):', 'Please re-upload this credential document.');
  if (!reason || !String(reason).trim()) return;
  busyCredentialId.value = row.id;
  error.value = '';
  try {
    await apiPost(`/v1/compliance-queue/${row.id}/needs-correction`, { reason: String(reason).trim() });
    await loadCandidateCredentials(selectedCandidate.value?.id);
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to request correction.';
  } finally {
    busyCredentialId.value = null;
  }
}

function openCandidateProfile() {
  if (!selectedCandidate.value?.id) return;
  router.push({ name: 'dashboard.candidate_profile', params: { id: selectedCandidate.value.id } });
}

onMounted(loadCandidates);
</script>
