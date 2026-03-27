<template>
  <div class="space-y-6">
    <div class="glass-dark rounded-[32px] p-8 border border-white/5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="font-display text-2xl text-white">Candidate Profile</h2>
          <p class="text-sm text-[color:var(--p-text-muted-color)] mt-1">Contact + compliance snapshot.</p>
        </div>
        <button
          type="button"
          class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
          :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
          @click="refresh"
        >
          Refresh
        </button>
      </div>

      <div v-if="loading" class="mt-6 text-sm text-[color:var(--p-text-muted-color)]">Loading...</div>

      <div v-else class="mt-6 space-y-6">
        <div v-if="!candidate" class="text-sm text-[color:var(--p-text-muted-color)]">Candidate not found.</div>

        <template v-else>
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
              <button
                type="button"
                class="px-4 py-2 rounded-2xl text-xs font-black tracking-widest uppercase border transition-colors"
                :style="{ backgroundColor: activeTab === 'profile' ? primarySoftBg : 'transparent', borderColor: activeTab === 'profile' ? primarySoftBorder : 'rgba(255,255,255,0.10)', color: activeTab === 'profile' ? primaryColor : 'rgba(226,232,240,0.85)' }"
                @click="activeTab = 'profile'"
              >
                Profile
              </button>
              <button
                type="button"
                class="px-4 py-2 rounded-2xl text-xs font-black tracking-widest uppercase border transition-colors"
                :style="{ backgroundColor: activeTab === 'submissions' ? primarySoftBg : 'transparent', borderColor: activeTab === 'submissions' ? primarySoftBorder : 'rgba(255,255,255,0.10)', color: activeTab === 'submissions' ? primaryColor : 'rgba(226,232,240,0.85)' }"
                @click="openSubmissionsTab"
              >
                Submission History
              </button>
            </div>

            <button
              type="button"
              class="px-4 py-2 rounded-2xl text-xs font-black tracking-widest uppercase border transition-colors"
              :style="{ backgroundColor: primaryColor, borderColor: primaryColor, color: '#fff' }"
              :disabled="!candidate || submissionCreating"
              @click="openSubmitModal"
            >
              {{ submissionCreating ? 'Generating…' : 'Submit to Job' }}
            </button>
          </div>

          <div v-if="activeTab === 'submissions'" class="p-6 rounded-2xl bg-white/[0.03] border border-white/5">
            <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Submission History</div>

            <div v-if="submissionLoading" class="mt-4 text-sm text-[color:var(--p-text-muted-color)]">Loading...</div>
            <div v-else-if="submissions.length === 0" class="mt-4 text-sm text-[color:var(--p-text-muted-color)]">No submissions yet.</div>

            <div v-else class="mt-4 space-y-3">
              <div
                v-for="s in submissions"
                :key="s.id"
                class="p-4 rounded-2xl border border-white/5 bg-white/[0.02]"
              >
                <div class="flex items-start justify-between gap-4">
                  <div class="min-w-0">
                    <div class="font-semibold text-white truncate">{{ s.job_order?.facility_name || '—' }}</div>
                    <div class="mt-1 text-xs text-[color:var(--p-text-muted-color)] truncate">
                      {{ s.job_order?.title || 'Job' }}
                      <span class="opacity-40">•</span>
                      {{ s.job_order?.specialty || '—' }}
                    </div>
                    <div class="mt-2 text-xs text-slate-300">
                      Views: <span class="font-bold">{{ s.view_count ?? 0 }}</span>
                      <span class="opacity-40">•</span>
                      Created: {{ formatDateTime(s.created_at) }}
                    </div>
                  </div>

                  <button
                    type="button"
                    class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors shrink-0"
                    :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
                    @click="copyLink(s.url)"
                  >
                    Copy Link
                  </button>
                </div>

                <div v-if="s.expires_at" class="mt-2 text-xs text-[color:var(--p-text-muted-color)]">
                  Expires: {{ formatDateTime(s.expires_at) }}
                </div>
              </div>
            </div>

            <div v-if="submissionMessage" class="mt-4 text-sm text-[color:var(--p-text-muted-color)]">{{ submissionMessage }}</div>
          </div>

          <template v-else>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
              <div class="p-6 rounded-2xl bg-white/[0.03] border border-white/5">
                <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Candidate</div>
                <div class="mt-2 text-xl font-display text-white">{{ candidate.name || (candidate.first_name + ' ' + candidate.last_name) }}</div>
                <div class="mt-4 space-y-1 text-sm text-slate-300">
                  <div><span class="text-[color:var(--p-text-muted-color)]">Email:</span> {{ candidate.email || '—' }}</div>
                  <div><span class="text-[color:var(--p-text-muted-color)]">Phone:</span> {{ candidate.phone || '—' }}</div>
                  <div><span class="text-[color:var(--p-text-muted-color)]">Specialty:</span> {{ candidate.specialty || '—' }}</div>
                  <div><span class="text-[color:var(--p-text-muted-color)]">License:</span> {{ candidate.license_type || '—' }}</div>
                  <div><span class="text-[color:var(--p-text-muted-color)]">Experience:</span> {{ candidate.years_experience ? (candidate.years_experience + ' yrs') : '—' }}</div>
                  <div><span class="text-[color:var(--p-text-muted-color)]">Location:</span> {{ ((candidate.city || '') + (candidate.city && candidate.state ? ', ' : '') + (candidate.state || '')) || '—' }}</div>
                  <div><span class="text-[color:var(--p-text-muted-color)]">Source:</span> {{ candidate.source || '—' }}</div>
                </div>
                <div class="mt-4 flex flex-wrap gap-1">
                  <span
                    v-for="t in (Array.isArray(candidate.tags) ? candidate.tags : [])"
                    :key="t"
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold border border-white/10 bg-white/5 text-slate-200"
                  >
                    {{ t }}
                  </span>
                </div>
              </div>

              <div class="p-6 rounded-2xl bg-white/[0.03] border border-white/5">
                <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Compliance Status</div>
                <div class="mt-3 flex items-center justify-between gap-3">
                  <div class="text-xs text-[color:var(--p-text-muted-color)]">Readiness</div>
                  <span
                    class="px-2 py-1 rounded-full text-[10px] font-black tracking-widest uppercase border"
                    :style="{ borderColor: primarySoftBorder, backgroundColor: primarySoftBg, color: primaryColor }"
                  >
                    {{ readiness.status || '—' }}
                  </span>
                </div>
                <div v-if="readinessReason" class="mt-2 text-xs text-slate-300 truncate">{{ readinessReason }}</div>
                <div class="mt-4 grid grid-cols-2 gap-3">
                  <div class="p-4 rounded-2xl border border-white/5 bg-white/[0.02]">
                    <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Active</div>
                    <div class="mt-1 text-xl font-display" :style="{ color: primaryColor }">{{ counts.active }}</div>
                  </div>
                  <div class="p-4 rounded-2xl border border-white/5 bg-white/[0.02]">
                    <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Expiring Soon</div>
                    <div class="mt-1 text-xl font-display text-white">{{ counts.expiring_soon }}</div>
                  </div>
                  <div class="p-4 rounded-2xl border border-white/5 bg-white/[0.02]">
                    <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Expired</div>
                    <div class="mt-1 text-xl font-display text-white">{{ counts.expired }}</div>
                  </div>
                  <div class="p-4 rounded-2xl border border-white/5 bg-white/[0.02]">
                    <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Pending</div>
                    <div class="mt-1 text-xl font-display text-white">{{ counts.pending }}</div>
                  </div>
                </div>
              </div>

              <div class="p-6 rounded-2xl bg-white/[0.03] border border-white/5">
                <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Quick Actions</div>
                <div class="mt-4 space-y-2">
                  <button
                    type="button"
                    class="w-full px-4 py-3 rounded-2xl text-xs font-black tracking-widest uppercase border transition-colors text-left"
                    :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
                    @click="goToCredentials"
                  >
                    Open Credentials
                  </button>
                  <button
                    type="button"
                    class="w-full px-4 py-3 rounded-2xl text-xs font-black tracking-widest uppercase border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10 text-left"
                    @click="goBack"
                  >
                    Back to Candidates
                  </button>
                </div>
              </div>
            </div>

            <div class="p-6 rounded-2xl bg-white/[0.03] border border-white/5">
              <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Credentials</div>

              <div v-if="credentials.length === 0" class="mt-4 text-sm text-[color:var(--p-text-muted-color)]">No credentials found for this candidate.</div>

              <div v-else class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                  <thead>
                    <tr class="text-left text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">
                      <th class="py-3 pr-4">Type</th>
                      <th class="py-3 pr-4">Issue</th>
                      <th class="py-3 pr-4">Expiry</th>
                      <th class="py-3 pr-4">Status</th>
                      <th class="py-3"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="cred in credentials" :key="cred.id" class="border-t border-white/5">
                      <td class="py-4 pr-4 text-white font-semibold">{{ cred.credential_type }}</td>
                      <td class="py-4 pr-4 text-slate-300">{{ cred.issue_date || '—' }}</td>
                      <td class="py-4 pr-4 text-slate-300">{{ cred.expiry_date || '—' }}</td>
                      <td class="py-4 pr-4 text-slate-200">{{ cred.status || '—' }}</td>
                      <td class="py-4 text-right">
                        <a
                          v-if="cred.document_url"
                          class="text-xs font-bold underline"
                          :style="{ color: primaryColor }"
                          :href="cred.document_url"
                          target="_blank"
                          rel="noreferrer"
                        >
                          View Document
                        </a>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </template>
        </template>

        <div v-if="error" class="text-sm text-red-400">{{ error }}</div>
      </div>
    </div>
  </div>

  <Dialog v-model:visible="submitModalOpen" modal header="Submit Candidate to Job" :style="{ width: 'min(720px, 96vw)' }">
    <div class="space-y-4">
      <div class="text-sm text-[color:var(--p-text-muted-color)]">
        Select a job order to generate a secure submission link.
      </div>

      <div>
        <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Job Order</div>
        <select v-model="selectedJobOrderId" class="mt-2 w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white">
          <option value="">Select a job...</option>
          <option v-for="j in jobOrders" :key="j.id" :value="String(j.id)">
            {{ j.facility_name }} — {{ j.title }}
          </option>
        </select>
      </div>

      <div class="flex items-center justify-end gap-2">
        <button
          type="button"
          class="px-4 py-3 rounded-2xl text-xs font-black tracking-widest uppercase border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10"
          @click="submitModalOpen = false"
        >
          Cancel
        </button>
        <button
          type="button"
          class="px-4 py-3 rounded-2xl text-xs font-black tracking-widest uppercase border transition-colors"
          :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
          :disabled="submissionCreating || !selectedJobOrderId"
          @click="generateSubmissionLink"
        >
          {{ submissionCreating ? 'Generating…' : 'Generate Link & Copy' }}
        </button>
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiGet, apiPost, normalizeApiList } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';
import Dialog from 'primevue/dialog';

const route = useRoute();
const router = useRouter();
const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const loading = ref(false);
const error = ref('');
const candidate = ref(null);
const credentials = ref([]);
const counts = ref({ active: 0, expiring_soon: 0, expired: 0, pending: 0 });
const readiness = ref({ status: '', reason_type: null, reason: null });

const readinessReason = computed(() => {
    const r = readiness.value;
    if (!r || !r.reason) return '';
    const name = r.reason?.name || r.reason?.category || 'Credential';
    if (r.reason_type === 'missing') return `Missing: ${name}`;
    if (r.reason_type === 'expired') return `Expired: ${name}`;
    if (r.reason_type === 'pending') return `Pending verification: ${name}`;
    if (r.reason_type === 'rejected') return `Rejected: ${name}`;
    return String(name);
});

const activeTab = ref('profile');

const jobOrders = ref([]);
const submitModalOpen = ref(false);
const selectedJobOrderId = ref('');
const submissionCreating = ref(false);
const submissionMessage = ref('');

const submissions = ref([]);
const submissionLoading = ref(false);

async function refresh() {
    loading.value = true;
    error.value = '';
    try {
        const res = await apiGet(`/v1/candidates/${route.params.id}`);
        // API wraps response in { data: { candidate, credentials, compliance } }
        const payload = res?.data || res;
        candidate.value = payload?.candidate || null;
        credentials.value = Array.isArray(payload?.credentials) ? payload.credentials : [];
        counts.value = payload?.compliance?.status_counts || counts.value;
        readiness.value = payload?.compliance?.readiness || readiness.value;
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Failed to load.';
    } finally {
        loading.value = false;
    }
}

function formatDateTime(v) {
    if (!v) return '—';
    const d = new Date(v);
    if (Number.isNaN(d.getTime())) return String(v);
    return d.toLocaleString();
}

async function copyLink(url) {
    if (!url) return;
    try {
        await navigator.clipboard.writeText(String(url));
        submissionMessage.value = 'Submission link copied to clipboard.';
        setTimeout(() => {
            if (submissionMessage.value === 'Submission link copied to clipboard.') submissionMessage.value = '';
        }, 2500);
    } catch {
        submissionMessage.value = 'Failed to copy link.';
    }
}

async function loadJobOrders() {
    try {
        const res = await apiGet('/v1/job-orders');
        jobOrders.value = normalizeApiList(res);
    } catch {
        jobOrders.value = [];
    }
}

function openSubmitModal() {
    submissionMessage.value = '';
    selectedJobOrderId.value = '';
    submitModalOpen.value = true;
    if (jobOrders.value.length === 0) loadJobOrders();
}

async function generateSubmissionLink() {
    if (!candidate.value?.id || !selectedJobOrderId.value) return;

    submissionCreating.value = true;
    submissionMessage.value = '';
    try {
        const res = await apiPost('/v1/submissions', {
            candidate_id: candidate.value.id,
            job_order_id: Number(selectedJobOrderId.value),
            expires_in_days: 14,
        });

        const url = res?.data?.url || '';
        if (url) {
            await copyLink(url);
        }

        submitModalOpen.value = false;
        await loadSubmissionHistory();
    } catch (e) {
        submissionMessage.value = e?.message || 'Failed to generate link.';
    } finally {
        submissionCreating.value = false;
    }
}

async function loadSubmissionHistory() {
    if (!candidate.value?.id) return;
    submissionLoading.value = true;
    try {
        const res = await apiGet(`/v1/submissions/candidate/${candidate.value.id}`);
        submissions.value = normalizeApiList(res);
    } finally {
        submissionLoading.value = false;
    }
}

async function openSubmissionsTab() {
    activeTab.value = 'submissions';
    await loadSubmissionHistory();
}

function goBack() {
    router.push({ name: 'dashboard.candidates' });
}

function goToCredentials() {
    router.push({ name: 'dashboard.candidate_credentials', params: { id: route.params.id } });
}

refresh();
</script>
