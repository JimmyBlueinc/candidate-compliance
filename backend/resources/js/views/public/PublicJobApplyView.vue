<template>
  <div class="min-h-screen bg-[var(--app-bg)] text-[var(--app-fg)] selection:bg-purple-500/30">
    <div class="max-w-7xl mx-auto px-6 sm:px-10 py-10">
      <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
          <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-white/10 bg-white/5 text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">
            Apply
          </div>
          <h1 class="mt-5 font-display text-4xl sm:text-5xl text-white tracking-tight truncate">{{ job?.title || 'Loading…' }}</h1>
          <div v-if="job" class="mt-3 text-sm text-[color:var(--p-text-muted-color)] truncate">
            {{ job.organization_name }}
            <span class="opacity-40">•</span>
            {{ job.facility_name }}
          </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <RouterLink
            :to="{ name: 'public.jobs.detail', params: { id: route.params.id } }"
            class="px-4 py-2 rounded-full text-xs font-black tracking-widest uppercase border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10"
          >
            Back
          </RouterLink>
        </div>
      </div>

      <div v-if="loadingJob" class="mt-8 text-sm text-[color:var(--p-text-muted-color)]">Loading…</div>
      <div v-else-if="jobError" class="mt-8 text-sm text-red-400">{{ jobError }}</div>

      <div v-else class="mt-8 grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-8 glass-dark rounded-[32px] p-8 border border-white/5">
          <div class="flex items-start justify-between gap-4">
            <div>
              <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Step 1</div>
              <div class="mt-2 font-display text-2xl text-white">Create or verify your account</div>
              <div class="mt-2 text-sm text-[color:var(--p-text-muted-color)]">
                We’ll submit your application for <span class="text-white font-semibold">{{ job?.organization_name || 'this organization' }}</span>.
              </div>
            </div>
            <div class="shrink-0 w-11 h-11 rounded-2xl border border-white/10 bg-white/5 flex items-center justify-center">
              <span class="material-symbols-outlined text-white">badge</span>
            </div>
          </div>

          <form class="mt-7 space-y-5" @submit.prevent="submit">
            <div v-if="success" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-sm font-semibold">
              {{ success }}
            </div>

            <div v-if="error" class="p-4 bg-red-500/10 border border-red-500/20 text-red-300 text-sm font-semibold rounded-2xl flex items-center gap-3">
              <span class="material-symbols-outlined text-base">error</span>
              <span class="min-w-0">{{ error }}</span>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
              <div class="text-[11px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Basic info</div>
              <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <div class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">First name</div>
                  <input
                    v-model="form.first_name"
                    type="text"
                    required
                    class="mt-2 w-full rounded-2xl px-4 py-3 text-sm bg-white/5 border border-white/10 text-white placeholder:text-slate-500 outline-none focus:border-white/20"
                    placeholder="First name"
                    :disabled="submitting"
                  />
                </div>

                <div>
                  <div class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Last name</div>
                  <input
                    v-model="form.last_name"
                    type="text"
                    required
                    class="mt-2 w-full rounded-2xl px-4 py-3 text-sm bg-white/5 border border-white/10 text-white placeholder:text-slate-500 outline-none focus:border-white/20"
                    placeholder="Last name"
                    :disabled="submitting"
                  />
                </div>

                <div class="sm:col-span-2">
                  <div class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Email</div>
                  <input
                    v-model="form.email"
                    type="email"
                    required
                    class="mt-2 w-full rounded-2xl px-4 py-3 text-sm bg-white/5 border border-white/10 text-white placeholder:text-slate-500 outline-none focus:border-white/20"
                    placeholder="you@example.com"
                    :disabled="submitting"
                  />
                </div>
              </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
              <div class="text-[11px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Security</div>
              <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <div class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Password</div>
                  <input
                    v-model="form.password"
                    type="password"
                    required
                    class="mt-2 w-full rounded-2xl px-4 py-3 text-sm bg-white/5 border border-white/10 text-white placeholder:text-slate-500 outline-none focus:border-white/20"
                    placeholder="********"
                    :disabled="submitting"
                  />
                </div>

                <div>
                  <div class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Confirm password</div>
                  <input
                    v-model="form.password_confirmation"
                    type="password"
                    required
                    class="mt-2 w-full rounded-2xl px-4 py-3 text-sm bg-white/5 border border-white/10 text-white placeholder:text-slate-500 outline-none focus:border-white/20"
                    placeholder="********"
                    :disabled="submitting"
                  />
                </div>
              </div>

              <div class="mt-3 text-xs text-[color:var(--p-text-muted-color)]">
                Already have an account for this organization? Enter your email + password and we’ll sign you in and submit the application.
              </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
              <div class="text-[11px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Optional</div>
              <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <div class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Phone</div>
                  <input
                    v-model="form.phone"
                    type="text"
                    class="mt-2 w-full rounded-2xl px-4 py-3 text-sm bg-white/5 border border-white/10 text-white placeholder:text-slate-500 outline-none focus:border-white/20"
                    placeholder="Phone"
                    :disabled="submitting"
                  />
                </div>

                <div>
                  <div class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Specialty</div>
                  <input
                    v-model="form.specialty"
                    type="text"
                    class="mt-2 w-full rounded-2xl px-4 py-3 text-sm bg-white/5 border border-white/10 text-white placeholder:text-slate-500 outline-none focus:border-white/20"
                    placeholder="Specialty"
                    :disabled="submitting"
                  />
                </div>
              </div>
            </div>

            <button
              type="submit"
              class="w-full py-3 rounded-2xl text-xs font-black tracking-widest uppercase border transition-colors disabled:opacity-60"
              :style="{ backgroundColor: primaryColor, borderColor: primaryColor, color: '#fff' }"
              :disabled="submitting"
            >
              {{ submitting ? 'Submitting…' : 'Apply & Continue' }}
            </button>
          </form>
        </div>

        <div class="lg:col-span-4 glass-dark rounded-[32px] p-8 border border-white/5">
          <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Summary</div>

          <div class="mt-5 p-5 rounded-2xl border border-white/10 bg-white/5">
            <div class="text-[11px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Job</div>
            <div class="mt-2 text-white font-semibold">{{ job?.title || '—' }}</div>
            <div class="mt-1 text-xs text-[color:var(--p-text-muted-color)]">
              {{ job?.facility_name || '—' }}
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
              <span
                class="px-3 py-1 rounded-full text-[11px] font-black tracking-widest uppercase border"
                :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
              >
                Apply-first
              </span>
              <span class="px-3 py-1 rounded-full text-[11px] font-black tracking-widest uppercase border border-white/10 bg-white/5 text-slate-200">
                Redirects to portal
              </span>
            </div>
          </div>

          <div class="mt-5 text-sm text-[color:var(--p-text-muted-color)]">
            After you apply, you’ll be redirected into the candidate portal.
          </div>

          <RouterLink
            class="mt-6 inline-flex items-center justify-center w-full px-4 py-3 rounded-2xl text-xs font-black tracking-widest uppercase border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10"
            to="/jobs"
          >
            Browse more jobs
          </RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiGet, apiPost } from '../../lib/api';
import { useAuthStore } from '../../stores/auth';
import { useBrandStore } from '../../stores/brand';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const loadingJob = ref(false);
const jobError = ref('');
const job = ref(null);

const submitting = ref(false);
const error = ref('');
const success = ref('');

const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  password_confirmation: '',
  phone: '',
  specialty: '',
});

async function loadJob() {
  const id = route.params.id;
  if (!id) return;

  loadingJob.value = true;
  jobError.value = '';
  try {
    const res = await apiGet(`/public/job-board/${id}`);
    job.value = res?.data || null;

    if (job.value?.tenant_id) {
      auth.setTenantId(job.value.tenant_id);
    }
  } catch (e) {
    jobError.value = e?.response?.data?.message || 'Failed to load job.';
  } finally {
    loadingJob.value = false;
  }
}

async function submit() {
  if (submitting.value) return;

  error.value = '';
  success.value = '';
  submitting.value = true;

  try {
    const id = route.params.id;
    const res = await apiPost(`/public/job-board/${id}/apply`, form.value);

    auth.setSession({ token: res?.token, user: res?.user });
    auth.setTenantId(res?.user?.organization_id || null);

    success.value = res?.message || 'Application submitted.';

    await router.push({ name: 'portal.jobs' });
  } catch (e) {
    const msg = e?.response?.data?.message;
    error.value = msg || 'Application failed.';
  } finally {
    submitting.value = false;
  }
}

onMounted(async () => {
  await brand.load();
  await loadJob();
});
</script>
