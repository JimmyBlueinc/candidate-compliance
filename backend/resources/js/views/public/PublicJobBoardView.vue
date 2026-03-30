<template>
  <div class="min-h-screen bg-[#f8fafc] text-slate-900">
    <PublicSiteHeader mode="apex" brand-name="AgencHQ" :primary-color="primaryColor" @apex-login="goLogin" />
    <div class="max-w-7xl mx-auto px-6 pt-28 pb-12">
      <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-900 p-7 md:p-10">
        <img
          src="https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=2000&q=80"
          alt="Staffing professionals discussing career opportunities"
          class="absolute inset-0 h-full w-full object-cover opacity-25"
          loading="lazy"
        />
        <div class="absolute inset-0 bg-gradient-to-r from-slate-950/92 via-slate-900/85 to-slate-900/70" />
        <div class="relative z-10 max-w-3xl">
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/75">Open Roles</p>
          <h1 class="mt-3 text-4xl font-bold tracking-tight text-white md:text-5xl" style="text-shadow: 0 10px 24px rgba(2, 6, 23, 0.55);">Find your next staffing opportunity</h1>
          <p class="mt-3 text-base text-white/85">Explore high-quality roles, filter quickly, and apply in minutes from a modern hiring experience.</p>
        </div>
      </section>

      <div class="mt-8 flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-2">
          <RouterLink to="/" class="px-4 py-2 rounded-xl text-sm font-semibold border border-slate-300 bg-white text-slate-700">Home</RouterLink>
          <RouterLink to="/portal/login" class="px-4 py-2 rounded-xl text-sm font-semibold border" :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }">
            Candidate Portal
          </RouterLink>
        </div>
      </div>

      <div class="mt-8 grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-4 rounded-3xl border border-slate-200 bg-white p-6">
          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Filters</div>
              <div class="mt-1 text-sm text-slate-700">Narrow down the list</div>
            </div>
            <button type="button" class="px-3 py-1.5 rounded-xl text-xs font-semibold border border-slate-300 bg-slate-50 text-slate-700" :disabled="loading" @click="reset">Reset</button>
          </div>

          <div class="mt-5 space-y-4">
            <div>
              <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 ml-1">Search</div>
              <input v-model="filters.q" type="text" class="mt-2 w-full rounded-xl px-3 py-2.5 text-sm border border-slate-300 text-slate-800 placeholder:text-slate-400 outline-none focus:border-slate-500" placeholder="Title, org, specialty..." @keydown.enter.prevent="refresh" />
            </div>
            <div>
              <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 ml-1">Organization</div>
              <input v-model="filters.org" type="text" class="mt-2 w-full rounded-xl px-3 py-2.5 text-sm border border-slate-300 text-slate-800 placeholder:text-slate-400 outline-none focus:border-slate-500" placeholder="org slug" @keydown.enter.prevent="refresh" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 ml-1">Specialty</div>
                <input v-model="filters.specialty" type="text" class="mt-2 w-full rounded-xl px-3 py-2.5 text-sm border border-slate-300 text-slate-800 placeholder:text-slate-400 outline-none focus:border-slate-500" placeholder="e.g. RN" @keydown.enter.prevent="refresh" />
              </div>
              <div>
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 ml-1">Work mode</div>
                <select v-model="filters.work_mode" class="mt-2 w-full rounded-xl px-3 py-2.5 text-sm border border-slate-300 text-slate-800 outline-none focus:border-slate-500" @change="refresh">
                  <option value="">Any</option>
                  <option value="on_site">On-site</option>
                  <option value="remote">Remote</option>
                </select>
              </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 ml-1">City</div>
                <input v-model="filters.city" type="text" class="mt-2 w-full rounded-xl px-3 py-2.5 text-sm border border-slate-300 text-slate-800 placeholder:text-slate-400 outline-none focus:border-slate-500" placeholder="City" @keydown.enter.prevent="refresh" />
              </div>
              <div>
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 ml-1">State</div>
                <input v-model="filters.state" type="text" class="mt-2 w-full rounded-xl px-3 py-2.5 text-sm border border-slate-300 text-slate-800 placeholder:text-slate-400 outline-none focus:border-slate-500" placeholder="State" @keydown.enter.prevent="refresh" />
              </div>
            </div>
            <button type="button" class="w-full px-4 py-3 rounded-xl text-sm font-semibold text-white" :style="{ backgroundColor: primaryColor }" :disabled="loading" @click="refresh">
              {{ loading ? 'Searching...' : 'Search jobs' }}
            </button>
          </div>
        </div>

        <div class="lg:col-span-8">
          <div v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</div>
          <div class="rounded-3xl border border-slate-200 bg-white overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
              <div class="text-sm text-slate-600">{{ loading ? 'Loading...' : `${items.length} job${items.length === 1 ? '' : 's'}` }}</div>
              <button type="button" class="px-3 py-1.5 rounded-xl text-xs font-semibold border border-slate-300 bg-slate-50 text-slate-700" :disabled="loading" @click="refresh">Refresh</button>
            </div>
            <div class="p-5">
              <div v-if="loading" class="text-sm text-slate-500">Loading...</div>
              <div v-else-if="items.length === 0" class="text-sm text-slate-500">No jobs found.</div>
              <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <article
                  v-for="j in items"
                  :key="j.id"
                  class="rounded-2xl border border-slate-200 p-4 hover:border-slate-400 transition cursor-pointer"
                  role="button"
                  tabindex="0"
                  @click="goToDetail(j)"
                >
                  <h3 class="font-semibold truncate">{{ j.title }}</h3>
                  <p class="mt-1 text-xs text-slate-500 truncate">{{ j.organization_name }} • {{ j.facility_name }}</p>
                  <div class="mt-3 flex flex-wrap gap-2">
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold border border-slate-200 bg-slate-50 text-slate-700">{{ j.specialty || 'Specialty -' }}</span>
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold border" :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }">{{ formatWorkMode(j.work_mode) }}</span>
                    <span v-if="j.facility_city || j.facility_state" class="px-2.5 py-1 rounded-full text-xs font-semibold border border-slate-200 bg-slate-50 text-slate-700">
                      <span v-if="j.facility_city">{{ j.facility_city }}</span><span v-if="j.facility_city && j.facility_state">, </span><span v-if="j.facility_state">{{ j.facility_state }}</span>
                    </span>
                  </div>
                  <div class="mt-4">
                    <RouterLink :to="{ name: 'public.jobs.apply', params: { id: j.id } }" class="inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold border" :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }" @click.stop>
                      Apply
                    </RouterLink>
                  </div>
                </article>
              </div>
            </div>
          </div>
        </div>
      </div>

      <section class="mt-10 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Why candidates choose AgencHQ</h2>
        <div class="mt-6 grid gap-4 md:grid-cols-3">
          <article v-for="point in candidatePoints" :key="point.title" class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
            <h3 class="text-base font-semibold text-slate-900">{{ point.title }}</h3>
            <p class="mt-2 text-sm text-slate-600">{{ point.description }}</p>
          </article>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { apiGet } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';
import PublicSiteHeader from '../../components/public/PublicSiteHeader.vue';

const router = useRouter();
const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const items = ref([]);
const loading = ref(false);
const error = ref('');

const filters = ref({
  q: '',
  org: '',
  specialty: '',
  work_mode: '',
  city: '',
  state: '',
});
const candidatePoints = [
  {
    title: 'Simple, guided applications',
    description: 'Apply quickly with a guided flow and no confusing screens.',
  },
  {
    title: 'Transparent job details',
    description: 'Review work mode, location, and key role context before you apply.',
  },
  {
    title: 'Direct portal access',
    description: 'Move straight into your candidate portal to continue your hiring journey.',
  },
];

function formatWorkMode(v) {
  if (!v) return '—';
  if (v === 'on_site') return 'On-site';
  if (v === 'remote') return 'Remote';
  return String(v);
}

function buildQuery() {
  const q = new URLSearchParams();
  for (const [k, v] of Object.entries(filters.value)) {
    const raw = String(v || '').trim();
    if (raw) q.set(k, raw);
  }
  const s = q.toString();
  return s ? `?${s}` : '';
}

async function refresh() {
  loading.value = true;
  error.value = '';
  try {
    const res = await apiGet(`/public/job-board${buildQuery()}`);
    items.value = Array.isArray(res?.data) ? res.data : [];
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to load jobs.';
  } finally {
    loading.value = false;
  }
}

async function reset() {
  filters.value = { q: '', org: '', specialty: '', work_mode: '', city: '', state: '' };
  await refresh();
}

async function goToDetail(job) {
  if (!job?.id) return;
  await router.push({ name: 'public.jobs.detail', params: { id: job.id } });
}

function goLogin() {
  router.push({ name: 'login' });
}

brand.load();
refresh();
</script>
