<template>
  <div class="space-y-6">
    <div class="glass-dark rounded-[32px] p-8 border border-white/5">
      <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
          <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Job Detail</div>
          <h2 class="mt-2 font-display text-2xl text-white truncate">{{ job?.title || 'Loading…' }}</h2>
          <div v-if="job" class="mt-1 text-sm text-[color:var(--p-text-muted-color)]">
            {{ job.facility_name }}
            <span class="opacity-40">•</span>
            {{ job.specialty || '—' }}
          </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <RouterLink
            :to="{ name: 'portal.jobs' }"
            class="px-3 py-1.5 rounded-full text-xs font-bold border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10"
          >
            Back
          </RouterLink>

          <button
            type="button"
            class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
            :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
            @click="refresh"
          >
            Refresh
          </button>
        </div>
      </div>

      <div v-if="loading" class="mt-6 text-sm text-[color:var(--p-text-muted-color)]">Loading...</div>
      <div v-else-if="error" class="mt-6 text-sm text-red-400">{{ error }}</div>

      <div v-else-if="job" class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 p-6 rounded-2xl bg-white/[0.03] border border-white/5">
          <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Compensation</div>

          <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            <div class="p-4 rounded-2xl border border-white/5 bg-white/[0.02]">
              <div class="text-xs text-[color:var(--p-text-muted-color)]">Pay Rate</div>
              <div class="mt-1 font-semibold text-white">{{ money(job.pay_rate) }}/hr</div>
            </div>

            <div class="p-4 rounded-2xl border border-white/5 bg-white/[0.02]">
              <div class="text-xs text-[color:var(--p-text-muted-color)]">Bill Rate</div>
              <div class="mt-1 font-semibold text-white">{{ money(job.bill_rate) }}/hr</div>
            </div>

            <div class="p-4 rounded-2xl border border-white/5 bg-white/[0.02]">
              <div class="text-xs text-[color:var(--p-text-muted-color)]">Stipend (Weekly)</div>
              <div class="mt-1 font-semibold text-white">{{ money(job.stipend_weekly) }}</div>
            </div>

            <div class="p-4 rounded-2xl border border-white/5 bg-white/[0.02]">
              <div class="text-xs text-[color:var(--p-text-muted-color)]">Est. Weekly Take-Home</div>
              <div class="mt-1 font-semibold text-white">{{ money(estimatedWeeklyTakeHome) }}</div>
              <div class="mt-1 text-xs text-[color:var(--p-text-muted-color)]">Assumes 36 hours/week.</div>
            </div>
          </div>
        </div>

        <div class="p-6 rounded-2xl bg-white/[0.03] border border-white/5">
          <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Details</div>

          <div class="mt-4 space-y-3 text-sm">
            <div class="flex items-center justify-between gap-2">
              <div class="text-[color:var(--p-text-muted-color)]">Start Date</div>
              <div class="text-white font-semibold">{{ formatDate(job.start_date) }}</div>
            </div>

            <div class="flex items-center justify-between gap-2">
              <div class="text-[color:var(--p-text-muted-color)]">Work Mode</div>
              <div class="text-white font-semibold">{{ formatWorkMode(job.work_mode) }}</div>
            </div>

            <div class="flex items-center justify-between gap-2">
              <div class="text-[color:var(--p-text-muted-color)]">Status</div>
              <div class="text-white font-semibold">{{ job.status || '—' }}</div>
            </div>
          </div>

          <div class="mt-6">
            <button
              type="button"
              class="w-full px-4 py-3 rounded-2xl text-xs font-black tracking-widest uppercase border transition-all"
              :style="{
                backgroundColor: primaryColor,
                borderColor: primaryColor,
                color: '#fff',
                boxShadow: `0 18px 45px ${String(primaryColor).includes('#') ? String(primaryColor) + '40' : 'rgba(139,92,246,0.25)'}`,
              }"
              :disabled="acting"
              @click="expressInterest"
            >
              {{ acting ? 'Saving…' : 'Express Interest' }}
            </button>
          <button
            type="button"
            class="mt-2 w-full px-4 py-3 rounded-2xl text-xs font-black tracking-widest uppercase border transition-all"
            :style="{ borderColor: primarySoftBorder, color: primaryColor, backgroundColor: job?.is_bookmarked ? primarySoftBg : 'transparent' }"
            :disabled="bookmarking"
            @click="toggleBookmark"
          >
            {{ bookmarking ? 'Updating…' : (job?.is_bookmarked ? 'Saved Job' : 'Save Job') }}
          </button>

            <div v-if="message" class="mt-3 text-sm text-[color:var(--p-text-muted-color)]">{{ message }}</div>
          </div>
        </div>
      </div>
    </div>

    <Dialog v-model:visible="credentialsDialogOpen" modal header="Complete Your Application Steps" :style="{ width: 'min(520px, 95vw)' }">
      <div class="space-y-4">
        <div class="text-sm text-[color:var(--p-text-color)]">
          <template v-if="requiresPhase1 && requiresPhase2">
            Complete <span class="font-semibold">phase 1 profile details</span> and <span class="font-semibold">phase 2 credentials/documents</span> before final application.
          </template>
          <template v-else-if="requiresPhase1">
            Complete your <span class="font-semibold">phase 1 profile details</span> before final application.
          </template>
          <template v-else>
            Complete your <span class="font-semibold">phase 2 credentials/documents</span> before final application.
          </template>
        </div>
        <div class="text-xs text-[color:var(--p-text-muted-color)]" v-if="requiresPhase1">
          Phase 1 missing fields: complete your personal profile details.
        </div>
        <div class="text-xs text-[color:var(--p-text-muted-color)]" v-else>
          Go to Credentials to upload required files and complete document checks.
        </div>

        <div class="flex gap-2 justify-end pt-2">
          <Button type="button" label="Later" severity="secondary" outlined size="small" @click="credentialsDialogOpen = false" />
          <Button
            type="button"
            :label="requiresPhase1 ? 'Go to Profile' : 'Go to Credentials'"
            size="small"
            @click="requiresPhase1 ? goToProfile() : goToCredentials()"
          />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiDelete, apiGet, apiPost, apiPut } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';

const route = useRoute();
const router = useRouter();
const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const job = ref(null);
const loading = ref(false);
const error = ref('');
const acting = ref(false);
const bookmarking = ref(false);
const message = ref('');
const onboarding = ref(null);
const credentialsDialogOpen = ref(false);
const requiresPhase1 = ref(false);
const requiresPhase2 = ref(true);

const estimatedWeeklyTakeHome = computed(() => {
    const payRate = Number(job.value?.pay_rate || 0);
    const stipend = Number(job.value?.stipend_weekly || 0);
    const hours = 36;
    if (Number.isNaN(payRate) || Number.isNaN(stipend)) return 0;
    return payRate * hours + stipend;
});

function money(v) {
    if (v === null || v === undefined || v === '') return '—';
    const n = Number(v);
    if (Number.isNaN(n)) return '—';
    return `$${n.toFixed(2)}`;
}

function formatDate(v) {
    if (!v) return '—';
    const d = new Date(v);
    if (Number.isNaN(d.getTime())) return String(v);
    return d.toLocaleDateString();
}

function formatWorkMode(v) {
    if (!v) return '—';
    if (v === 'on_site') return 'On-site';
    if (v === 'remote') return 'Remote';
    return String(v);
}

async function loadOnboarding() {
    try {
        const res = await apiGet('/v1/portal/profile');
        const payload = res?.data || res;
        onboarding.value = payload?.onboarding || null;
    } catch {
        onboarding.value = null;
    }
}

async function refresh() {
    const id = route.params.id;
    if (!id) return;

    loading.value = true;
    error.value = '';
    message.value = '';
    try {
        const res = await apiGet(`/v1/portal/jobs/${id}`);
        job.value = res?.data || null;
    } catch (e) {
        error.value = e?.message || 'Failed to load job.';
    } finally {
        loading.value = false;
    }
}

async function expressInterest() {
    if (!job.value?.id) return;

    // Check onboarding completion before final application
    const phase1Complete = Boolean(onboarding.value?.phase1_complete);
    const phase2Complete = Boolean(onboarding.value?.phase2_complete);
    if (!phase1Complete || !phase2Complete) {
        requiresPhase1.value = !phase1Complete;
        requiresPhase2.value = !phase2Complete;
        credentialsDialogOpen.value = true;
        return;
    }

    acting.value = true;
    message.value = '';
    try {
        await apiPost(`/v1/placements/express-interest/${job.value.id}`);
        message.value = 'Interest submitted. A recruiter will review your application.';
    } catch (e) {
        const payload = e?.response?.data || {};
        if (payload?.requires_onboarding) {
            requiresPhase1.value = Boolean(payload?.requires_phase1);
            requiresPhase2.value = Boolean(payload?.requires_phase2);
            credentialsDialogOpen.value = true;
            message.value = payload?.message || 'Complete onboarding before final application.';
            return;
        }
        message.value = e?.message || 'Failed to submit interest.';
    } finally {
        acting.value = false;
    }
}

async function toggleBookmark() {
    if (!job.value?.id || bookmarking.value) return;
    bookmarking.value = true;
    try {
        if (job.value.is_bookmarked) {
            await apiDelete(`/v1/portal/bookmarks/${job.value.id}`);
            job.value.is_bookmarked = false;
            message.value = 'Removed from saved jobs.';
        } else {
            await apiPut(`/v1/portal/bookmarks/${job.value.id}`, {});
            job.value.is_bookmarked = true;
            message.value = 'Saved to your bookmarked jobs.';
        }
    } catch (e) {
        message.value = e?.response?.data?.message || e?.message || 'Failed to update bookmark.';
    } finally {
        bookmarking.value = false;
    }
}

function goToCredentials() {
    credentialsDialogOpen.value = false;
    router.push({ name: 'portal.credentials' });
}

function goToProfile() {
    credentialsDialogOpen.value = false;
    router.push({ name: 'portal.profile' });
}

watch(
    () => route.params.id,
    async () => {
        await refresh();
    }
);

onMounted(async () => {
    await brand.load();
    await loadOnboarding();
    await refresh();
});
</script>
