<template>
  <div class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <PublicSiteHeader
      mode="apex"
      brand-name="AgencHQ"
      :primary-color="primarySolid"
      @apex-login="handleLoginClick"
    />

    <main class="relative overflow-hidden pt-28 pb-24">
      <div class="pointer-events-none absolute -top-24 -left-20 h-72 w-72 rounded-full bg-indigo-200/45 blur-3xl" />
      <div class="pointer-events-none absolute top-36 right-0 h-80 w-80 rounded-full bg-cyan-200/35 blur-3xl" />

      <section class="mx-auto max-w-7xl px-6">
        <div class="relative overflow-hidden rounded-[2rem] border border-slate-200 shadow-[0_26px_80px_rgba(15,23,42,0.16)] min-h-[560px]">
          <img
            src="https://images.unsplash.com/photo-1516549655169-df83a0774514?auto=format&fit=crop&w=2200&q=80"
            alt="Professional healthcare staffing team collaborating in a modern hospital workspace"
            class="hero-pan absolute inset-0 h-full w-full object-cover"
            loading="lazy"
          />
          <div class="absolute inset-0 bg-gradient-to-r from-slate-950/88 via-slate-900/72 to-slate-900/40" />
          <div class="relative z-10 flex h-full items-end p-8 md:p-12">
            <div class="max-w-3xl text-white">
              <span class="inline-flex items-center rounded-full border border-white/25 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]">
                AgencHQ Staffing Cloud
              </span>
              <h1 class="mt-5 text-4xl font-bold leading-tight tracking-tight md:text-6xl">
                Place the right talent faster, with confidence at every step.
              </h1>
              <p class="mt-5 text-lg leading-relaxed text-slate-100/90">
                AgencHQ gives recruiting, compliance, operations, and finance teams one premium command center to run modern workforce delivery.
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
                  to="/features"
                  class="rounded-xl border border-white/35 bg-white/10 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/20"
                >
                  See Features
                </RouterLink>
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
        <div class="aq-on-dark relative overflow-hidden rounded-[2rem] border border-slate-200 bg-slate-950 px-8 py-14 text-white md:px-12">
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
import { computed } from 'vue';
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
.hero-pan {
  animation: heroPan 16s ease-in-out infinite alternate;
}

.feature-image {
  transition: transform 240ms ease, filter 240ms ease;
}
.feature-image:hover {
  transform: scale(1.015);
  filter: saturate(1.08);
}

@keyframes heroPan {
  from {
    transform: scale(1.03) translateX(0);
  }
  to {
    transform: scale(1.08) translateX(-1.5%);
  }
}
</style>
