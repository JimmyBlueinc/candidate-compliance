<template>
  <div class="min-h-screen bg-[#f8fafc] text-slate-900">
    <PublicSiteHeader
      :mode="headerMode"
      :brand-name="headerBrandName"
      :primary-color="primaryColor"
      :show-dashboard-button="false"
      :show-sign-in-button="!auth.isAuthenticated"
      :current-role="auth.user?.role || ''"
      @apex-login="goLogin"
      @tenant-jobs="goToJobs"
      @tenant-dashboard="goToDashboard"
      @tenant-signin="goLogin"
    />
    <div class="max-w-7xl mx-auto px-6 pt-28 pb-16">
      <section class="aq-on-dark relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-900 p-7 md:p-10">
        <img
          src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1800&q=80"
          alt="Candidate and recruiter discussing a role"
          class="absolute inset-0 h-full w-full object-cover opacity-25"
          loading="lazy"
        />
        <div class="absolute inset-0 bg-gradient-to-r from-slate-950/92 via-slate-900/85 to-slate-900/70" />
        <div class="relative z-10">
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/75">Job Detail</p>
          <h1 class="mt-3 text-4xl font-bold tracking-tight text-white md:text-5xl" style="text-shadow: 0 10px 24px rgba(2, 6, 23, 0.55);">{{ job?.title || 'Loading...' }}</h1>
          <p v-if="job" class="mt-3 text-sm text-white/85">
            {{ job.organization_name }} - {{ job.facility_name || 'Facility' }}
            <template v-if="job.facility_city || job.facility_state">
              - {{ [job.facility_city, job.facility_state].filter(Boolean).join(', ') }}
            </template>
          </p>
          <div class="mt-5 flex flex-wrap gap-2">
            <span class="px-3 py-1 rounded-full text-xs font-semibold border border-white/20 bg-white/10 text-white">{{ job?.specialty || 'Specialty' }}</span>
            <span class="px-3 py-1 rounded-full text-xs font-semibold border" :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: '#fff' }">{{ formatWorkMode(job?.work_mode) }}</span>
            <span class="px-3 py-1 rounded-full text-xs font-semibold border border-white/20 bg-white/10 text-white">Starts: {{ formatDate(job?.start_date) }}</span>
          </div>
          <div class="mt-6 flex flex-wrap gap-2">
            <RouterLink :to="jobsBackRoute" class="px-4 py-2 rounded-xl text-sm font-semibold border border-white/25 bg-white/10 text-white hover:bg-white/20">Back to jobs</RouterLink>
            <RouterLink v-if="job?.id" :to="applyRoute" class="px-4 py-2 rounded-xl text-sm font-semibold text-white" :style="{ backgroundColor: primaryColor }">Apply now</RouterLink>
          </div>
        </div>
      </section>

      <div v-if="loading" class="mt-8 text-sm text-slate-500">Loading...</div>
      <div v-else-if="error" class="mt-8 text-sm text-red-600">{{ error }}</div>

      <section v-else-if="job" class="mt-8 grid grid-cols-1 lg:grid-cols-12 gap-6">
        <article class="lg:col-span-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
          <h2 class="text-2xl font-bold tracking-tight text-slate-900">About this role</h2>
          <p class="mt-4 whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ job.description || 'No description provided.' }}</p>

          <div class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 p-5">
            <h3 class="text-lg font-semibold text-slate-900">Application highlights</h3>
            <ul class="mt-3 space-y-1 text-sm text-slate-700">
              <li>- Fast account creation and one-step application flow</li>
              <li>- Your progress continues directly in the candidate portal</li>
              <li>- Messaging and updates happen in one place after submission</li>
            </ul>
            <RouterLink
              :to="applyRoute"
              class="mt-5 inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold text-white"
              :style="{ backgroundColor: primaryColor }"
            >
              Continue to apply
            </RouterLink>
          </div>
        </article>

        <article class="lg:col-span-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
          <h2 class="text-lg font-semibold text-slate-900">Key details</h2>
          <div class="mt-4 space-y-3 text-sm">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3"><span class="text-slate-500">Organization:</span> <span class="font-semibold text-slate-900">{{ job.organization_name || '-' }}</span></div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3"><span class="text-slate-500">Facility:</span> <span class="font-semibold text-slate-900">{{ job.facility_name || '-' }}</span></div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3"><span class="text-slate-500">Work mode:</span> <span class="font-semibold text-slate-900">{{ formatWorkMode(job.work_mode) }}</span></div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3"><span class="text-slate-500">Start date:</span> <span class="font-semibold text-slate-900">{{ formatDate(job.start_date) }}</span></div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3"><span class="text-slate-500">Pay rate:</span> <span class="font-semibold text-slate-900">{{ money(job.pay_rate) }}/hr</span></div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3"><span class="text-slate-500">Weekly stipend:</span> <span class="font-semibold text-slate-900">{{ money(job.stipend_weekly) }}</span></div>
          </div>
        </article>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiGet } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';
import { useAuthStore } from '../../stores/auth';
import PublicSiteHeader from '../../components/public/PublicSiteHeader.vue';

const route = useRoute();
const router = useRouter();
const brand = useBrandStore();
const auth = useAuthStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const loading = ref(false);
const error = ref('');
const job = ref(null);
const isTenantRoute = computed(() => String(route.name || '').startsWith('tenant.'));
const isOrgSlugRoute = computed(() => Boolean(route.params?.orgSlug));
const headerMode = computed(() => ((isTenantRoute.value || isOrgSlugRoute.value) ? 'tenant' : 'apex'));
const headerBrandName = computed(() => String(job.value?.organization_name || brand.name || 'Organization'));
const jobsBackRoute = computed(() => {
  if (isTenantRoute.value) return { name: 'tenant.jobs' };
  if (isOrgSlugRoute.value) return { name: 'public.org-home', params: { orgSlug: String(route.params.orgSlug) } };
  return { name: 'landing' };
});
const applyRoute = computed(() => {
  const id = job.value?.id || route.params.id;
  if (isTenantRoute.value) return { name: 'tenant.job-apply', params: { id } };
  if (isOrgSlugRoute.value) return { name: 'public.org.jobs.apply', params: { orgSlug: String(route.params.orgSlug), id } };
  return { name: 'landing' };
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

function goLogin() {
  router.push({ name: 'login' });
}

function goToJobs() {
  router.push(jobsBackRoute.value);
}

function goToDashboard() {
  if (auth.isAuthenticated) {
    router.push({ name: 'dashboard.index' });
    return;
  }
  router.push({ name: 'login' });
}

async function refresh() {
  const id = route.params.id;
  if (!id) return;

  loading.value = true;
  error.value = '';
  job.value = null;
  try {
    const res = await apiGet(`/public/job-board/${id}`);
    job.value = res?.data || null;
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to load job.';
  } finally {
    loading.value = false;
  }
}

watch(
  () => route.params.id,
  async () => {
    await refresh();
  }
);

onMounted(async () => {
  await brand.load();
  await refresh();
});
</script>
