<template>
  <div class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <PublicSiteHeader
      mode="tenant"
      :brand-name="brand.name || 'Organization'"
      :primary-color="primarySolid"
      :show-dashboard-button="isAdmin"
      :show-sign-in-button="!auth.isAuthenticated"
      @tenant-jobs="goToJobs"
      @tenant-dashboard="goToDashboard"
      @tenant-signin="goToLogin"
    />

    <main class="pt-24 pb-24">
      <section class="mx-auto max-w-7xl px-6">
        <div class="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_24px_74px_rgba(15,23,42,0.12)]">
          <div class="pointer-events-none absolute -top-24 -right-20 h-72 w-72 rounded-full bg-indigo-200/45 blur-3xl" />
          <div class="pointer-events-none absolute top-24 -left-12 h-64 w-64 rounded-full bg-cyan-200/35 blur-3xl" />

          <div class="relative z-10 grid items-stretch gap-8 p-8 md:p-10 lg:grid-cols-12 lg:p-12">
            <div class="lg:col-span-6">
              <span class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-indigo-700">
                <span class="h-1.5 w-1.5 rounded-full bg-indigo-500" />
                Careers at {{ brand.name || 'Blueinc' }}
              </span>

              <h1 class="mt-5 text-4xl font-bold leading-tight tracking-tight text-slate-950 md:text-5xl">
                {{ publicHomeContent.hero_heading }}
              </h1>
              <p class="mt-5 max-w-xl text-lg leading-relaxed text-slate-600">
                {{ publicHomeContent.hero_subheading }}
              </p>

              <div class="mt-8 flex flex-wrap gap-3">
                <button
                  type="button"
                  class="rounded-xl px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:-translate-y-0.5 hover:shadow-xl"
                  :style="{ backgroundColor: primarySolid }"
                  @click="scrollToSection('job-search')"
                >
                  {{ publicHomeContent.hero_primary_cta_label }}
                </button>
                <button
                  type="button"
                  class="rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-100"
                  @click="showRegisterModal = true"
                >
                  {{ publicHomeContent.hero_secondary_cta_label }}
                </button>
              </div>

              <div class="mt-8 grid gap-3 sm:grid-cols-3">
                <article v-for="stat in heroStats" :key="stat.label" class="rounded-2xl border border-slate-200 bg-slate-50/80 p-3">
                  <p class="text-[11px] uppercase tracking-[0.13em] text-slate-500">{{ stat.label }}</p>
                  <p class="mt-1 text-xl font-semibold text-slate-900">{{ stat.value }}</p>
                </article>
              </div>
            </div>

            <div class="lg:col-span-6">
              <div class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-900 shadow-xl">
                <img
                  src="https://images.unsplash.com/photo-1587351021759-3e566b6af7cc?auto=format&fit=crop&w=1600&q=80"
                  alt="Nurses and clinicians collaborating at a modern healthcare facility"
                  class="h-[330px] w-full object-cover opacity-85"
                  loading="lazy"
                  @error="(e) => onImageError(e, '/images/public/tenant-careers-hero.svg')"
                />
                <div class="grid grid-cols-3 gap-2 border-t border-slate-700/70 bg-slate-950/80 p-3 text-xs">
                  <div class="rounded-lg bg-slate-800/90 p-2 text-slate-100">
                    <p class="uppercase tracking-[0.12em] text-slate-400">Open Roles</p>
                    <p class="mt-1 text-lg font-semibold text-cyan-300">{{ jobs.length || 24 }}</p>
                  </div>
                  <div class="rounded-lg bg-slate-800/90 p-2 text-slate-100">
                    <p class="uppercase tracking-[0.12em] text-slate-400">Avg Response</p>
                    <p class="mt-1 text-lg font-semibold text-emerald-300">48h</p>
                  </div>
                  <div class="rounded-lg bg-slate-800/90 p-2 text-slate-100">
                    <p class="uppercase tracking-[0.12em] text-slate-400">Partner Facilities</p>
                    <p class="mt-1 text-lg font-semibold text-violet-300">30+</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="job-search" class="mx-auto mt-10 max-w-7xl px-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-7">
          <div class="flex flex-col gap-4 md:flex-row md:items-end">
            <div class="flex-1">
              <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Search Jobs</label>
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search by role, specialty, keyword"
                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
              />
            </div>
            <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-3">
              <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Role</label>
                <select v-model="selectedRole" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm text-slate-700 outline-none focus:border-indigo-400">
                  <option value="">All roles</option>
                  <option v-for="role in roleOptions" :key="role" :value="role">{{ role }}</option>
                </select>
              </div>
              <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Location</label>
                <select v-model="selectedLocation" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm text-slate-700 outline-none focus:border-indigo-400">
                  <option value="">All locations</option>
                  <option v-for="location in locationOptions" :key="location" :value="location">{{ location }}</option>
                </select>
              </div>
              <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Type</label>
                <select v-model="selectedType" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm text-slate-700 outline-none focus:border-indigo-400">
                  <option value="">All types</option>
                  <option v-for="type in typeOptions" :key="type" :value="type">{{ type }}</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="mx-auto mt-8 max-w-7xl px-6">
        <div class="flex items-end justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-600">Featured Opportunities</p>
            <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Find roles that fit your schedule and specialty.</h2>
          </div>
          <button type="button" class="hidden text-sm font-semibold text-indigo-600 hover:text-indigo-700 md:inline" @click="goToJobs">
            View full job board
          </button>
        </div>

        <div v-if="jobsLoading" class="mt-6 rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-500">
          Loading featured jobs...
        </div>
        <div v-else-if="filteredJobs.length === 0" class="mt-6 rounded-2xl border border-slate-200 bg-white p-8 text-center">
          <p class="text-lg font-semibold text-slate-800">No jobs match your current filters.</p>
          <p class="mt-2 text-sm text-slate-500">Try clearing filters or join the talent pool so recruiters can notify you.</p>
        </div>
        <div v-else class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          <article
            v-for="job in featuredJobs"
            :key="job.id"
            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-indigo-200 hover:shadow-md"
          >
            <div class="flex items-start justify-between gap-3">
              <h3 class="text-lg font-semibold text-slate-900">{{ job.title }}</h3>
              <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-indigo-700">
                {{ job.type }}
              </span>
            </div>
            <p class="mt-2 text-sm text-slate-500">{{ job.location }}</p>
            <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-slate-600">{{ job.description || 'No description provided yet.' }}</p>
            <div class="mt-5 flex items-center gap-2">
              <button
                type="button"
                class="rounded-lg px-3 py-2 text-xs font-semibold text-white"
                :style="{ backgroundColor: primarySolid }"
                @click="applyToJob(job)"
              >
                Apply Now
              </button>
              <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50" @click="viewJob(job)">
                View Role
              </button>
            </div>
          </article>
        </div>
      </section>

      <section class="mx-auto mt-14 max-w-7xl px-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
          <div class="grid gap-6 lg:grid-cols-12">
            <div class="lg:col-span-5">
              <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-600">Why Join {{ brand.name || 'Blueinc' }}</p>
              <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ publicHomeContent.why_join_heading }}</h2>
              <p class="mt-3 text-sm leading-relaxed text-slate-600">
                We partner with leading facilities, invest in your professional development, and keep your job experience transparent from application to placement.
              </p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:col-span-7">
              <article v-for="item in whyJoinItems" :key="item.title" class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                <img
                  :src="item.image"
                  :alt="item.alt"
                  class="h-32 w-full object-cover"
                  loading="lazy"
                  @error="(e) => onImageError(e, '/images/public/tenant-careers-hero.svg')"
                />
                <div class="p-4">
                  <h3 class="text-sm font-semibold text-slate-900">{{ item.title }}</h3>
                  <p class="mt-1 text-xs leading-relaxed text-slate-600">{{ item.description }}</p>
                </div>
              </article>
            </div>
          </div>
        </div>
      </section>

      <section class="mx-auto mt-10 max-w-7xl px-6">
        <div class="grid gap-4 lg:grid-cols-12">
          <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-900 p-6 text-white shadow-sm lg:col-span-8 md:p-8">
            <img
              src="https://images.unsplash.com/photo-1579154204601-01588f351e67?auto=format&fit=crop&w=1600&q=80"
              alt="Nurse preparing care plan at workstation"
              class="absolute inset-0 h-full w-full object-cover opacity-35"
              loading="lazy"
              @error="(e) => onImageError(e, '/images/public/tenant-careers-hero.svg')"
            />
            <div class="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-950/85 to-indigo-900/65" />
            <div class="relative z-10 max-w-xl">
              <p class="text-xs font-semibold uppercase tracking-[0.16em] text-cyan-200">Join Talent Pool</p>
              <h3 class="mt-3 text-3xl font-bold">{{ publicHomeContent.talent_pool_heading }}</h3>
              <p class="mt-3 text-sm leading-relaxed text-slate-200">
                {{ publicHomeContent.talent_pool_subheading }}
              </p>
              <button type="button" class="mt-5 rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 hover:bg-slate-100" @click="showRegisterModal = true">
                {{ publicHomeContent.hero_secondary_cta_label }}
              </button>
            </div>
          </div>

          <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-4">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Trusted Facilities</p>
            <h3 class="mt-2 text-xl font-semibold text-slate-900">Where our candidates work</h3>
            <div class="mt-4 space-y-2">
              <div v-for="partner in partnerFacilities" :key="partner" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700">
                {{ partner }}
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="mx-auto mt-10 max-w-7xl px-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
          <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-600">How It Works</p>
          <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Three steps from interest to placement</h2>
          <div class="mt-6 grid gap-4 md:grid-cols-3">
            <article v-for="(step, index) in howItWorks" :key="step.title" class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
              <p class="text-xs font-semibold uppercase tracking-[0.14em] text-indigo-600">Step {{ index + 1 }}</p>
              <h3 class="mt-2 text-lg font-semibold text-slate-900">{{ step.title }}</h3>
              <p class="mt-2 text-sm text-slate-600">{{ step.description }}</p>
            </article>
          </div>
        </div>
      </section>

      <section class="mx-auto mt-10 max-w-7xl px-6">
        <div class="rounded-[2rem] border border-slate-200 bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-600 px-8 py-12 text-white shadow-[0_20px_50px_rgba(59,130,246,0.35)]">
          <div class="max-w-2xl">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-100">Take the next step</p>
            <h2 class="mt-3 text-4xl font-bold leading-tight">{{ publicHomeContent.final_cta_heading }}</h2>
            <p class="mt-3 text-base text-cyan-100">{{ publicHomeContent.final_cta_subheading }}</p>
            <div class="mt-6 flex flex-wrap gap-3">
              <button type="button" class="rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 hover:bg-slate-100" @click="goToJobs">
                {{ publicHomeContent.hero_primary_cta_label }}
              </button>
              <button type="button" class="rounded-xl border border-white/40 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/15" @click="showRegisterModal = true">
                {{ publicHomeContent.hero_secondary_cta_label }}
              </button>
            </div>
          </div>
        </div>
      </section>
    </main>

    <footer class="border-t border-slate-200 bg-white px-6 py-10">
      <div class="mx-auto grid max-w-7xl gap-6 md:grid-cols-4">
        <div class="md:col-span-2">
          <p class="text-lg font-semibold text-slate-900">{{ brand.name || 'Organization' }}</p>
          <p class="mt-2 max-w-md text-sm text-slate-600">
            A modern healthcare staffing team focused on matching exceptional professionals to high-impact roles.
          </p>
        </div>
        <div class="space-y-2 text-sm text-slate-600">
          <p class="font-semibold text-slate-900">Explore</p>
          <button type="button" class="block hover:text-slate-900" @click="goToJobs">Open Jobs</button>
          <button type="button" class="block hover:text-slate-900" @click="showRegisterModal = true">{{ publicHomeContent.hero_secondary_cta_label }}</button>
        </div>
        <div class="space-y-2 text-sm text-slate-600">
          <p class="font-semibold text-slate-900">Platform</p>
          <button type="button" class="block hover:text-slate-900" @click="goToLogin">Sign in</button>
          <p>Powered by AgencHQ</p>
        </div>
      </div>
    </footer>

    <Dialog v-model:visible="showRegisterModal" modal :style="{ width: 'min(480px, 95vw)' }">
      <template #header>
        <div>
          <h3 class="text-lg font-semibold text-slate-900">Join Talent Pool</h3>
          <p class="mt-1 text-sm text-slate-500">Share your details and get matched quickly.</p>
        </div>
      </template>

      <div class="space-y-3">
        <div>
          <label class="mb-1.5 block text-xs font-medium text-slate-700">Full name *</label>
          <input v-model="registerName" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-indigo-400" placeholder="Jane Smith" />
        </div>
        <div>
          <label class="mb-1.5 block text-xs font-medium text-slate-700">Email *</label>
          <input v-model="registerEmail" type="email" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-indigo-400" placeholder="you@example.com" />
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-700">Phone</label>
            <input v-model="registerPhone" type="tel" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-indigo-400" placeholder="(555) 000-0000" />
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-700">Role</label>
            <input v-model="registerRole" type="text" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm outline-none focus:border-indigo-400" placeholder="RN, LPN, CNA" />
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex gap-2">
          <button type="button" class="flex-1 rounded-lg bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-200" @click="showRegisterModal = false">
            Cancel
          </button>
          <button
            type="button"
            class="flex-1 rounded-lg px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
            :style="{ backgroundColor: primarySolid }"
            :disabled="registering"
            @click="handleRegister"
          >
            {{ registering ? 'Submitting...' : 'Join Pool' }}
          </button>
        </div>
      </template>
    </Dialog>

    <Toast position="bottom-right" />
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useBrandStore } from '../../stores/brand';
import { useAuthStore } from '../../stores/auth';
import { useToast } from 'primevue/usetoast';
import Dialog from 'primevue/dialog';
import Toast from 'primevue/toast';
import { apiGet, apiPost } from '../../lib/api';
import PublicSiteHeader from '../../components/public/PublicSiteHeader.vue';

const router = useRouter();
const route = useRoute();
const brand = useBrandStore();
const auth = useAuthStore();
const toast = useToast();

const jobs = ref([]);
const jobsLoading = ref(false);

const searchQuery = ref('');
const selectedRole = ref('');
const selectedLocation = ref('');
const selectedType = ref('');

const showRegisterModal = ref(false);
const registering = ref(false);
const registerEmail = ref('');
const registerName = ref('');
const registerPhone = ref('');
const registerRole = ref('');

const primarySolid = computed(() => brand.primaryColor || '#4f46e5');
const isAdmin = computed(() => {
  const role = auth.user?.role;
  return Boolean(role && ['admin', 'super_admin', 'org_admin', 'platform_admin', 'org_super_admin', 'org_owner'].includes(role));
});

const defaultPublicHomeContent = {
  hero_heading: 'Build your next chapter with our team.',
  hero_subheading: 'Discover meaningful healthcare staffing opportunities and apply in minutes.',
  hero_primary_cta_label: 'Browse Open Jobs',
  hero_secondary_cta_label: 'Join Talent Pool',
  why_join_heading: 'A team built for growth, support, and meaningful impact.',
  talent_pool_heading: 'Get matched with the right opportunities faster.',
  talent_pool_subheading: 'Share your profile once and get notified when the right role opens.',
  final_cta_heading: 'Ready to apply or join our talent network?',
  final_cta_subheading: 'Start with open roles now or submit your profile for future opportunities.',
};

const publicHomeContent = computed(() => ({
  ...defaultPublicHomeContent,
  ...(brand.publicHomeContent || {}),
}));

const heroStats = computed(() => [
  { label: 'Open Roles', value: String(jobs.value.length || 24) },
  { label: 'Hiring Regions', value: '12+' },
  { label: 'Candidate Rating', value: '4.8/5' },
]);

const normalizedJobs = computed(() =>
  jobs.value.map((job) => ({
    id: job.id,
    title: job.title || 'Untitled Role',
    description: job.description || '',
    location: job.location || 'Multiple locations',
    type: job.employment_type || job.type || 'Full-time',
  })),
);

const roleOptions = computed(() => {
  const unique = new Set(normalizedJobs.value.map((j) => j.title).filter(Boolean));
  return Array.from(unique).slice(0, 12);
});

const locationOptions = computed(() => {
  const unique = new Set(normalizedJobs.value.map((j) => j.location).filter(Boolean));
  return Array.from(unique).slice(0, 12);
});

const typeOptions = computed(() => {
  const unique = new Set(normalizedJobs.value.map((j) => j.type).filter(Boolean));
  return Array.from(unique).slice(0, 12);
});

const filteredJobs = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  return normalizedJobs.value.filter((job) => {
    const qMatch =
      q === '' ||
      job.title.toLowerCase().includes(q) ||
      job.description.toLowerCase().includes(q) ||
      job.location.toLowerCase().includes(q);
    const roleMatch = selectedRole.value === '' || job.title === selectedRole.value;
    const locationMatch = selectedLocation.value === '' || job.location === selectedLocation.value;
    const typeMatch = selectedType.value === '' || job.type === selectedType.value;
    return qMatch && roleMatch && locationMatch && typeMatch;
  });
});

const featuredJobs = computed(() => filteredJobs.value.slice(0, 6));

const whyJoinItems = [
  {
    title: 'Premium Facility Network',
    description: 'Work with respected hospitals and care organizations that value excellent clinical talent.',
    image: 'https://images.unsplash.com/photo-1519494080410-f9aa8f52f12e?auto=format&fit=crop&w=1200&q=80',
    alt: 'Healthcare staff collaborating in a clinical environment',
  },
  {
    title: 'Transparent Hiring Experience',
    description: 'Clear role expectations, faster communication, and support at every stage of your application.',
    image: 'https://images.unsplash.com/photo-1516549655169-df83a0774514?auto=format&fit=crop&w=1200&q=80',
    alt: 'Recruiting team discussing candidate opportunities',
  },
  {
    title: 'Flexible Career Paths',
    description: 'Explore contract, long-term, and specialty placements tailored to your career goals.',
    image: 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=1200&q=80',
    alt: 'Nurse reviewing patient and shift plans',
  },
  {
    title: 'Growth and Community',
    description: 'Join a network where clinicians learn, grow, and make measurable impact together.',
    image: 'https://images.unsplash.com/photo-1576765607924-b4f1d1f4f5d7?auto=format&fit=crop&w=1200&q=80',
    alt: 'Healthcare professionals celebrating team success',
  },
];

const partnerFacilities = ['Blueinc Medical Center', 'Northline Care Partners', 'Harborview Health Network', 'Summit Clinical Group'];

const howItWorks = [
  { title: 'Discover roles', description: 'Search and filter open jobs by specialty, location, and work type.' },
  { title: 'Apply in minutes', description: 'Submit your application quickly with a streamlined candidate experience.' },
  { title: 'Get matched and onboarded', description: 'Recruiters guide you into the right opportunities and next steps.' },
];

function scrollToSection(id) {
  const el = document.getElementById(id);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}

function onImageError(event, fallback) {
  const img = event?.target;
  if (!img) return;
  if (img.dataset.fallbackApplied === '1') return;
  img.dataset.fallbackApplied = '1';
  img.src = fallback;
}

function goToJobs() {
  router.push({ name: 'tenant.jobs' });
}

function goToDashboard() {
  if (auth.isAuthenticated) {
    router.push({ name: 'dashboard.index' });
    return;
  }
  router.push({ name: 'login' });
}

function goToLogin() {
  router.push({ name: 'login' });
}

function viewJob(job) {
  router.push({ name: 'tenant.job-detail', params: { id: job.id } });
}

function applyToJob(job) {
  router.push({ name: 'tenant.job-apply', params: { id: job.id } });
}

async function fetchJobs() {
  jobsLoading.value = true;
  try {
    const response = await apiGet('/jobs', {
      params: { subdomain: brand.subdomain },
    });
    jobs.value = response?.jobs || response?.data || [];
  } catch (_error) {
    jobs.value = [];
  } finally {
    jobsLoading.value = false;
  }
}

async function handleRegister() {
  if (!registerEmail.value.trim() || !registerName.value.trim()) {
    toast.add({
      severity: 'warn',
      summary: 'Missing information',
      detail: 'Please enter full name and email.',
      life: 3200,
    });
    return;
  }

  if (!brand.loaded && !brand.loading) {
    await brand.load();
  }

  const orgSlug = route.params.slug || brand.slug;
  if (!orgSlug) {
    toast.add({
      severity: 'error',
      summary: 'Organization missing',
      detail: 'Refresh and try again.',
      life: 3500,
    });
    return;
  }

  const nameParts = registerName.value.trim().split(/\s+/);
  const firstName = nameParts[0] || '';
  const lastName = nameParts.slice(1).join(' ') || '';

  registering.value = true;
  try {
    await apiPost(`/public/${orgSlug}/register`, {
      first_name: firstName,
      last_name: lastName,
      email: registerEmail.value.trim(),
      phone: registerPhone.value.trim() || '000-000-0000',
      role: registerRole.value.trim() || 'Healthcare Professional',
    });

    toast.add({
      severity: 'success',
      summary: 'You are in',
      detail: 'Your profile was submitted to the talent pool.',
      life: 4200,
    });
    showRegisterModal.value = false;
    registerEmail.value = '';
    registerName.value = '';
    registerPhone.value = '';
    registerRole.value = '';
  } catch (error) {
    const message = error?.response?.data?.message || 'Registration failed. Please try again.';
    toast.add({
      severity: 'error',
      summary: 'Submission failed',
      detail: message,
      life: 4200,
    });
  } finally {
    registering.value = false;
  }
}

onMounted(async () => {
  if (!brand.loaded && !brand.loading) {
    await brand.load();
  }
  await fetchJobs();
});
</script>
