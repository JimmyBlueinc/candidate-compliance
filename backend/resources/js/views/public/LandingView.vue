<template>
  <div class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <PublicSiteHeader
      mode="apex"
      :brand-name="brand.name || 'AgencHQ'"
      :primary-color="primarySolid"
      @apex-login="handleLoginClick"
    />

    <main class="relative overflow-hidden pt-28 pb-24">
      <div class="pointer-events-none absolute -top-24 -left-20 h-72 w-72 rounded-full bg-indigo-200/45 blur-3xl" />
      <div class="pointer-events-none absolute top-36 right-0 h-80 w-80 rounded-full bg-cyan-200/35 blur-3xl" />

      <section class="mx-auto max-w-7xl px-6">
        <div class="rounded-[2rem] border border-slate-200 bg-white/95 shadow-[0_26px_80px_rgba(15,23,42,0.14)] overflow-hidden">
          <div class="grid grid-cols-1 lg:grid-cols-12">
            <div class="lg:col-span-6 p-8 md:p-12 lg:p-14">
              <span class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-indigo-700">
                Workforce Operations Cloud
              </span>
              <h1 class="mt-6 text-4xl font-bold leading-tight tracking-tight text-slate-950 md:text-6xl">
                Run staffing operations with the precision of a modern command center.
              </h1>
              <p class="mt-6 max-w-2xl text-lg leading-relaxed text-slate-600">
                AgencHQ unifies recruiting, compliance, facility coordination, messaging, and billing into one premium operating system for fast-moving teams.
              </p>

              <div class="mt-8 flex flex-wrap gap-3">
                <RouterLink
                  to="/signup"
                  class="rounded-xl px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:-translate-y-0.5 hover:shadow-xl"
                  :style="{ backgroundColor: primarySolid }"
                >
                  Book a Live Demo
                </RouterLink>
                <RouterLink
                  to="/solutions"
                  class="rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-100"
                >
                  Explore Platform
                </RouterLink>
              </div>

              <div class="mt-8 grid gap-3 sm:grid-cols-3">
                <article
                  v-for="metric in heroMetrics"
                  :key="metric.label"
                  class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3"
                >
                  <p class="text-xs uppercase tracking-[0.12em] text-slate-500">{{ metric.label }}</p>
                  <p class="mt-1 text-lg font-semibold text-slate-900">{{ metric.value }}</p>
                </article>
              </div>
            </div>

            <div class="relative lg:col-span-6 border-t border-slate-200 bg-gradient-to-br from-indigo-50/80 via-cyan-50/70 to-violet-50/70 p-6 md:p-8 lg:border-l lg:border-t-0">
              <div class="relative h-full min-h-[420px] rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <img
                  :src="heroImageSrc"
                  alt="Operations team coordinating staffing workflows"
                  class="hero-image h-56 w-full rounded-2xl object-cover"
                  loading="lazy"
                />

                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-950 p-4 text-slate-100 shadow-xl">
                  <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Live Operations Pulse</p>
                  <div class="mt-3 grid grid-cols-3 gap-3 text-sm">
                    <div class="rounded-xl bg-slate-800/80 p-3">
                      <p class="text-[11px] uppercase tracking-[0.14em] text-slate-400">Open Shifts</p>
                      <p class="mt-1 text-xl font-semibold text-cyan-300">48</p>
                    </div>
                    <div class="rounded-xl bg-slate-800/80 p-3">
                      <p class="text-[11px] uppercase tracking-[0.14em] text-slate-400">Compliance</p>
                      <p class="mt-1 text-xl font-semibold text-emerald-300">93.8%</p>
                    </div>
                    <div class="rounded-xl bg-slate-800/80 p-3">
                      <p class="text-[11px] uppercase tracking-[0.14em] text-slate-400">ARR Trend</p>
                      <p class="mt-1 text-xl font-semibold text-violet-300">+12%</p>
                    </div>
                  </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                  <article class="rounded-2xl border border-slate-200 bg-gradient-to-br from-indigo-100 via-sky-100 to-cyan-100 p-3 shadow-sm">
                    <div class="rounded-xl border border-indigo-200/70 bg-white/85 p-3">
                      <p class="text-[11px] uppercase tracking-[0.14em] text-indigo-700">Care Demand Pulse</p>
                      <svg viewBox="0 0 300 92" class="mt-2 h-16 w-full">
                        <defs>
                          <linearGradient id="pulseLine" x1="0" x2="1">
                            <stop offset="0%" stop-color="#4f46e5" />
                            <stop offset="100%" stop-color="#06b6d4" />
                          </linearGradient>
                        </defs>
                        <path d="M14 68 C54 42, 84 74, 124 54 C160 36, 194 60, 230 46 C254 38, 274 42, 286 30" fill="none" stroke="url(#pulseLine)" stroke-width="6" stroke-linecap="round" />
                        <circle cx="124" cy="54" r="5.5" fill="#4f46e5" />
                        <circle cx="230" cy="46" r="5.5" fill="#06b6d4" />
                        <circle cx="286" cy="30" r="6" fill="#10b981" />
                      </svg>
                      <div class="mt-2 flex items-end justify-between">
                        <p class="text-xs font-semibold uppercase tracking-[0.13em] text-slate-500">Weekly Fill Momentum</p>
                        <p class="text-sm font-semibold text-emerald-600">+18.4%</p>
                      </div>
                    </div>
                  </article>
                  <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <img
                      :src="heroCareImageSrc"
                      alt="Nurses and healthcare staff collaborating on shift coordination"
                      class="hero-image h-32 w-full object-cover"
                      loading="lazy"
                      @error="onHeroCareImageError"
                    />
                  </div>
                </div>

                <div class="floating-chip right-6 top-5">
                  <span class="text-emerald-600">96.1%</span>
                  Credential Completion
                </div>
                <div class="floating-chip left-8 bottom-6">
                  <span class="text-indigo-700">132</span>
                  Active Facilities
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="mx-auto mt-12 max-w-7xl px-6">
        <div class="rounded-3xl border border-slate-200 bg-white px-6 py-7 shadow-sm md:px-8">
          <div class="grid gap-5 md:grid-cols-5 md:items-center">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Trusted by operations leaders</p>
            <div class="md:col-span-4 grid grid-cols-2 gap-3 text-sm text-slate-600 sm:grid-cols-4">
              <span v-for="item in trustBadges" :key="item" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-center font-medium">
                {{ item }}
              </span>
            </div>
          </div>
        </div>
      </section>

      <section class="mx-auto mt-20 max-w-7xl space-y-10 px-6">
        <article
          v-for="(section, index) in featureNarratives"
          :key="section.title"
          class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8"
        >
          <div class="grid items-center gap-8 lg:grid-cols-12">
            <div
              class="lg:col-span-6"
              :class="index % 2 === 1 ? 'lg:order-2' : ''"
            >
              <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-600">{{ section.eyebrow }}</p>
              <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ section.title }}</h2>
              <p class="mt-4 text-base leading-relaxed text-slate-600">{{ section.description }}</p>
              <ul class="mt-5 space-y-3 text-sm text-slate-700">
                <li v-for="bullet in section.bullets" :key="bullet" class="flex items-start gap-2">
                  <span class="mt-1 inline-block h-2 w-2 rounded-full bg-indigo-500" />
                  <span>{{ bullet }}</span>
                </li>
              </ul>
            </div>

            <div
              class="lg:col-span-6"
              :class="index % 2 === 1 ? 'lg:order-1' : ''"
            >
              <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 p-3">
                <img :src="section.image" :alt="section.alt" class="feature-image h-72 w-full rounded-xl object-cover" loading="lazy" />
                <div class="absolute left-6 top-6 rounded-full border border-white/50 bg-white/85 px-3 py-1 text-xs font-semibold text-slate-700 backdrop-blur">
                  {{ section.chip }}
                </div>
              </div>
            </div>
          </div>
        </article>
      </section>

      <section class="mx-auto mt-20 max-w-7xl px-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
          <h2 class="text-3xl font-bold tracking-tight text-slate-900">How AgencHQ works in practice</h2>
          <p class="mt-2 max-w-2xl text-slate-600">A single flow from demand intake to placement execution, compliance governance, and revenue closure.</p>
          <div class="mt-8 grid gap-4 md:grid-cols-3">
            <article
              v-for="(step, idx) in howItWorks"
              :key="step.title"
              class="rounded-2xl border border-slate-200 bg-slate-50 p-5"
            >
              <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-600">Step {{ idx + 1 }}</p>
              <h3 class="mt-2 text-lg font-semibold text-slate-900">{{ step.title }}</h3>
              <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ step.description }}</p>
            </article>
          </div>
        </div>
      </section>

      <section class="mx-auto mt-16 max-w-7xl px-6">
        <h2 class="text-center text-3xl font-bold tracking-tight text-slate-900">One platform, every mission-critical module</h2>
        <div class="mt-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <article
            v-for="module in modules"
            :key="module.title"
            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-md"
          >
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-indigo-600">{{ module.badge }}</p>
            <h3 class="mt-2 text-lg font-semibold text-slate-900">{{ module.title }}</h3>
            <p class="mt-2 text-sm text-slate-600">{{ module.description }}</p>
          </article>
        </div>
      </section>

      <section class="mx-auto mt-20 max-w-7xl px-6">
        <div class="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-slate-950 px-8 py-14 text-white md:px-12">
          <img
            src="https://images.unsplash.com/photo-1530026186672-2cd00ffc50fe?auto=format&fit=crop&w=1600&q=80"
            alt="Clinical operations team planning with modern dashboards"
            class="absolute inset-0 h-full w-full object-cover opacity-25"
            loading="lazy"
          />
          <div class="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-950/85 to-indigo-900/65" />
          <div class="relative z-10 max-w-2xl">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-200">Scale with confidence</p>
            <h2 class="mt-4 text-4xl font-bold leading-tight">See your operations in one intelligent workspace.</h2>
            <p class="mt-4 text-lg text-slate-200">From recruiter workflow to credential governance to financial outcomes, AgencHQ gives every team one source of truth.</p>
            <div class="mt-7 flex flex-wrap gap-3">
              <RouterLink
                to="/signup"
                class="rounded-xl bg-white px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-slate-100"
              >
                Book Demo
              </RouterLink>
              <RouterLink
                to="/pricing"
                class="rounded-xl border border-white/35 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10"
              >
                View Pricing
              </RouterLink>
            </div>
          </div>
        </div>
      </section>
    </main>

    <footer class="border-t border-slate-200 bg-white px-6 py-12">
      <div class="mx-auto grid max-w-7xl gap-8 md:grid-cols-5">
        <div class="md:col-span-2">
          <p class="text-lg font-semibold text-slate-900">AgencHQ</p>
          <p class="mt-3 max-w-sm text-sm text-slate-600">
            Premium multi-tenant staffing operations software for organizations that run compliance-sensitive, high-volume workflows.
          </p>
        </div>
        <div class="space-y-2 text-sm text-slate-600">
          <p class="font-semibold text-slate-900">Product</p>
          <RouterLink to="/solutions" class="block hover:text-slate-900">Solutions</RouterLink>
          <RouterLink to="/pricing" class="block hover:text-slate-900">Pricing</RouterLink>
          <RouterLink to="/jobs" class="block hover:text-slate-900">Jobs</RouterLink>
        </div>
        <div class="space-y-2 text-sm text-slate-600">
          <p class="font-semibold text-slate-900">Company</p>
          <RouterLink to="/customers" class="block hover:text-slate-900">Customers</RouterLink>
          <RouterLink to="/login" class="block hover:text-slate-900">Sign in</RouterLink>
          <RouterLink to="/signup" class="block hover:text-slate-900">Get started</RouterLink>
        </div>
        <div class="space-y-2 text-sm text-slate-600">
          <p class="font-semibold text-slate-900">Trust</p>
          <p class="text-slate-500">SOC-ready controls</p>
          <p class="text-slate-500">Role-based access</p>
          <p class="text-slate-500">Audit-ready activity logs</p>
        </div>
      </div>
      <div class="mx-auto mt-8 max-w-7xl border-t border-slate-200 pt-5 text-xs text-slate-500">
        &copy; {{ new Date().getFullYear() }} AgencHQ. Built for modern staffing operations.
      </div>
    </footer>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useBrandStore } from '../../stores/brand';
import { useAuthStore } from '../../stores/auth';
import PublicSiteHeader from '../../components/public/PublicSiteHeader.vue';

const router = useRouter();
const brand = useBrandStore();
const auth = useAuthStore();

const primaryColor = computed(() => brand.primaryColor || '#4f46e5');
const primarySolid = computed(() => {
  const c = primaryColor.value;
  return typeof c === 'string' && c.trim().length ? c : '#4f46e5';
});
const heroImageSrc = '/images/public/hero-operations.svg';
const heroCareImageSrc = ref('https://images.unsplash.com/photo-1584515933487-779824d29309?auto=format&fit=crop&w=1200&q=80');

const heroMetrics = [
  { label: 'Placement velocity', value: '2.4x faster' },
  { label: 'Compliance coverage', value: '96% avg' },
  { label: 'Billing visibility', value: 'Real-time' },
];

const trustBadges = ['Blueinc Health', 'Northline Care', 'Summit Workforce', 'Harbor Clinical'];

const featureNarratives = [
  {
    eyebrow: 'Staffing Intelligence',
    title: 'Coordinate talent pipelines with operational confidence.',
    description: 'Track requisitions, source quality candidates, and move placements through clear stage gates while your team sees one live operating picture.',
    bullets: [
      'Pipeline lanes tied to facility demand and role urgency',
      'Structured recruiter handoffs and visibility across teams',
      'Single timeline for candidate movement and blockers',
    ],
    image: 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&w=1400&q=80',
    alt: 'Healthcare staffing leaders reviewing candidate pipeline',
    chip: 'Pipeline command',
  },
  {
    eyebrow: 'Compliance Governance',
    title: 'Stay audit-ready with proactive credential oversight.',
    description: 'Surface expiring items early, route approvals clearly, and keep every facility assignment aligned with policy before shifts go live.',
    bullets: [
      'Credential expiry heatmaps and proactive queueing',
      'Review workflows with visible approver accountability',
      'Audit-ready activity trails across user actions',
    ],
    image: 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=1400&q=80',
    alt: 'Clinical professional reviewing compliance and credential details',
    chip: 'Compliance clarity',
  },
  {
    eyebrow: 'Finance + Facilities',
    title: 'Align facility execution and billing outcomes in one loop.',
    description: 'Keep contracts, placement activity, and invoice signals in sync so revenue forecasting reflects operational reality in real time.',
    bullets: [
      'Facility status snapshots tied to staffing progress',
      'Contract and billing context visible to operations teams',
      'Revenue trend visibility with exception alerts',
    ],
    image: 'https://images.unsplash.com/photo-1666214280391-8ff5bd3c0bf1?auto=format&fit=crop&w=1400&q=80',
    alt: 'Healthcare operations and finance teams coordinating facility staffing outcomes',
    chip: 'Revenue intelligence',
  },
];

const howItWorks = [
  {
    title: 'Intake and prioritize demand',
    description: 'Capture open needs, critical shifts, and organization context with role-based workflow routing.',
  },
  {
    title: 'Match, verify, and coordinate',
    description: 'Move candidates through qualification, compliance checks, facility workflows, and assignment readiness.',
  },
  {
    title: 'Execute, bill, and improve',
    description: 'Close loops on timesheets, billing, and activity analytics to optimize future staffing performance.',
  },
];

const modules = [
  { badge: 'Hiring', title: 'Jobs + Pipeline', description: 'Requisitions, sourcing, candidate flow, and conversion analytics.' },
  { badge: 'Compliance', title: 'Credential Governance', description: 'Expiration tracking, verification queues, and policy adherence.' },
  { badge: 'Facilities', title: 'Facility Operations', description: 'Contract context, account coordination, and operational views.' },
  { badge: 'Financial', title: 'Billing + Revenue', description: 'Invoice lifecycle, payment visibility, and trend insights.' },
  { badge: 'Collaboration', title: 'Messaging + Alerts', description: 'Real-time updates, notifications, and team coordination lanes.' },
  { badge: 'Platform', title: 'Integrations + Settings', description: 'Organization-level controls with extensible system connectors.' },
];

function onHeroCareImageError() {
  heroCareImageSrc.value = '/images/public/tenant-careers-hero.svg';
}

function handleLoginClick() {
  if (auth.isAuthenticated) {
    if (!brand.loaded) {
      brand.initFromStorage();
    }
    const isOrgUser = !['candidate', 'facility', 'platform_admin'].includes(auth.user?.role);
    if (brand.subdomain && isOrgUser) {
      window.location.href = `https://${brand.subdomain}.agenchq.com/dashboard`;
      return;
    }
  }
  router.push({ name: 'login' });
}
</script>

<style scoped>
.hero-image,
.feature-image {
  transition: transform 240ms ease, filter 240ms ease;
}

.hero-image:hover,
.feature-image:hover {
  transform: scale(1.015);
  filter: saturate(1.08);
}

.floating-chip {
  position: absolute;
  border-radius: 9999px;
  border: 1px solid rgb(226 232 240 / 0.95);
  background: rgb(255 255 255 / 0.92);
  padding: 0.45rem 0.8rem;
  font-size: 0.7rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgb(71 85 105);
  box-shadow: 0 14px 32px -20px rgb(15 23 42 / 0.5);
}

.floating-chip span {
  margin-right: 0.35rem;
}
</style>
