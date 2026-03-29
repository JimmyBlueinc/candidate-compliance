<template>
  <div class="min-h-screen bg-[#f8fafc] text-slate-900">
    <PublicSiteHeader mode="apex" brand-name="AgencHQ" :primary-color="primaryColor" @apex-login="goLogin" />
    <div class="max-w-7xl mx-auto px-6 pt-28 pb-16">
      <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-900 p-7 md:p-10">
        <img
          src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1800&q=80"
          alt="Candidate application process"
          class="absolute inset-0 h-full w-full object-cover opacity-25"
          loading="lazy"
        />
        <div class="absolute inset-0 bg-gradient-to-r from-slate-950/92 via-slate-900/85 to-slate-900/70" />
        <div class="relative z-10">
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/75">Application</p>
          <h1 class="mt-3 text-4xl font-bold tracking-tight text-white md:text-5xl">{{ job?.title || 'Loading...' }}</h1>
          <p v-if="job" class="mt-3 text-sm text-white/85">{{ job.organization_name }} - {{ job.facility_name }}</p>
          <RouterLink
            :to="{ name: 'public.jobs.detail', params: { id: route.params.id } }"
            class="mt-5 inline-flex rounded-xl border border-white/25 bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20"
          >
            Back to role details
          </RouterLink>
        </div>
      </section>

      <div v-if="loadingJob" class="mt-8 text-sm text-slate-500">Loading...</div>
      <div v-else-if="jobError" class="mt-8 text-sm text-red-600">{{ jobError }}</div>

      <div v-else class="mt-8 grid grid-cols-1 lg:grid-cols-12 gap-6">
        <section class="lg:col-span-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Step 1 of 1</p>
            <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Create or verify your account</h2>
            <p class="mt-2 text-sm text-slate-600">Submit your details to apply for this role. If you already have an account, use your existing email and password.</p>
          </div>

          <form class="mt-7 space-y-5" @submit.prevent="submit">
            <div v-if="success" class="rounded-2xl border border-emerald-300 bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">
              {{ success }}
            </div>
            <div v-if="error" class="rounded-2xl border border-red-300 bg-red-50 p-4 text-sm font-semibold text-red-700">
              {{ error }}
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
              <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Basic information</h3>
              <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="ml-1 text-xs font-semibold uppercase tracking-[0.1em] text-slate-500">First name</label>
                  <input v-model="form.first_name" type="text" required class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none focus:border-slate-500" placeholder="First name" :disabled="submitting" />
                </div>
                <div>
                  <label class="ml-1 text-xs font-semibold uppercase tracking-[0.1em] text-slate-500">Last name</label>
                  <input v-model="form.last_name" type="text" required class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none focus:border-slate-500" placeholder="Last name" :disabled="submitting" />
                </div>
                <div class="sm:col-span-2">
                  <label class="ml-1 text-xs font-semibold uppercase tracking-[0.1em] text-slate-500">Email</label>
                  <input v-model="form.email" type="email" required class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none focus:border-slate-500" placeholder="you@example.com" :disabled="submitting" />
                </div>
              </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
              <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Account security</h3>
              <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="ml-1 text-xs font-semibold uppercase tracking-[0.1em] text-slate-500">Password</label>
                  <input v-model="form.password" type="password" required class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none focus:border-slate-500" placeholder="********" :disabled="submitting" />
                </div>
                <div>
                  <label class="ml-1 text-xs font-semibold uppercase tracking-[0.1em] text-slate-500">Confirm password</label>
                  <input v-model="form.password_confirmation" type="password" required class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none focus:border-slate-500" placeholder="********" :disabled="submitting" />
                </div>
              </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
              <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Optional details</h3>
              <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="ml-1 text-xs font-semibold uppercase tracking-[0.1em] text-slate-500">Phone</label>
                  <input v-model="form.phone" type="text" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none focus:border-slate-500" placeholder="Phone number" :disabled="submitting" />
                </div>
                <div>
                  <label class="ml-1 text-xs font-semibold uppercase tracking-[0.1em] text-slate-500">Specialty</label>
                  <input v-model="form.specialty" type="text" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none focus:border-slate-500" placeholder="Specialty" :disabled="submitting" />
                </div>
              </div>
            </div>

            <button type="submit" class="w-full rounded-xl px-4 py-3 text-sm font-semibold text-white disabled:opacity-60" :style="{ backgroundColor: primaryColor }" :disabled="submitting">
              {{ submitting ? 'Submitting...' : 'Apply & Continue' }}
            </button>
          </form>
        </section>

        <aside class="lg:col-span-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
          <h3 class="text-lg font-semibold text-slate-900">Application summary</h3>
          <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs uppercase tracking-[0.12em] text-slate-500">Role</p>
            <p class="mt-1 text-sm font-semibold text-slate-900">{{ job?.title || '-' }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ job?.facility_name || '-' }}</p>
          </div>
          <ul class="mt-4 space-y-2 text-sm text-slate-700">
            <li>- One submission instantly creates your candidate session</li>
            <li>- You are redirected to your candidate portal after apply</li>
            <li>- Continue profile updates and messaging in one place</li>
          </ul>
          <RouterLink class="mt-6 inline-flex w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" to="/jobs">
            Browse more jobs
          </RouterLink>
        </aside>
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
import PublicSiteHeader from '../../components/public/PublicSiteHeader.vue';

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

function goLogin() {
  router.push({ name: 'login' });
}

onMounted(async () => {
  await brand.load();
  await loadJob();
});
</script>
