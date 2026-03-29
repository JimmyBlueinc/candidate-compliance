<template>
  <div class="min-h-screen bg-[#f8fafc] text-slate-900 antialiased">
    <PublicSiteHeader mode="apex" brand-name="AgencHQ" :primary-color="primarySolid" @apex-login="handleLoginClick" />

    <main class="pt-28 pb-24">
      <section class="mx-auto max-w-7xl px-6">
        <div class="relative overflow-hidden rounded-[2rem] border border-slate-200 shadow-[0_30px_90px_rgba(2,6,23,0.16)] min-h-[620px]">
          <img
            src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=2400&q=80"
            alt="Staffing team collaborating in a modern office"
            class="hero-pan absolute inset-0 h-full w-full object-cover"
            loading="lazy"
          />
          <div class="absolute inset-0 bg-gradient-to-r from-slate-950/88 via-slate-900/74 to-slate-900/46" />
          <div class="relative z-10 grid h-full items-end p-8 md:p-12">
            <div class="max-w-3xl text-white">
              <p class="inline-flex rounded-full border border-white/25 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-white">
                Workforce Operating System
              </p>
              <h1 class="mt-6 text-4xl font-bold leading-tight tracking-tight md:text-6xl" :style="heroHeadlineStyle">
                Staffing execution, redesigned for high-growth teams.
              </h1>
              <p class="mt-5 text-lg leading-relaxed text-white/90">
                AgencHQ helps talent teams source, place, and retain workers faster with premium workflows, intelligent automation, and clear business visibility.
              </p>
              <div class="mt-8 flex flex-wrap gap-3">
                <RouterLink
                  to="/signup"
                  class="rounded-xl px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:-translate-y-0.5 hover:shadow-xl"
                  :style="{ backgroundColor: primarySolid }"
                >
                  Book Demo
                </RouterLink>
                <RouterLink to="/features" class="rounded-xl border border-white/35 bg-white/10 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/20">
                  Explore Platform
                </RouterLink>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="mx-auto mt-12 max-w-7xl px-6">
        <div class="grid gap-4 md:grid-cols-4">
          <article v-for="metric in metrics" :key="metric.label" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">{{ metric.label }}</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ metric.value }}</p>
            <p class="mt-1 text-xs text-slate-600">{{ metric.note }}</p>
          </article>
        </div>
      </section>

      <section class="mx-auto mt-16 max-w-7xl px-6">
        <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 p-6 shadow-[0_24px_70px_rgba(2,6,23,0.35)] md:p-8">
          <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(99,102,241,0.28),transparent_45%)]" />
          <div class="relative z-10">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/75">Dashboard Experience</p>
            <h2 class="mt-3 text-3xl font-bold tracking-tight" :style="dashboardHeadlineStyle">A command-center view your team actually enjoys using.</h2>
            <p class="mt-3 max-w-3xl text-sm text-white/80">
              Inspired by real operations dashboards: clean signal cards, activity context, and an executive snapshot built for staffing teams.
            </p>

            <div class="mt-6 rounded-2xl border border-white/10 bg-white/5 p-4">
              <div class="grid gap-3 md:grid-cols-4">
                <div class="rounded-xl border border-white/10 bg-slate-900/70 p-3">
                  <p class="text-[10px] uppercase tracking-[0.14em] text-white/60">Login time</p>
                  <p class="mt-1 text-sm font-semibold text-white">08:14 AM</p>
                </div>
                <div class="rounded-xl border border-white/10 bg-slate-900/70 p-3">
                  <p class="text-[10px] uppercase tracking-[0.14em] text-white/60">Location</p>
                  <p class="mt-1 text-sm font-semibold text-white">Lagos, NG</p>
                </div>
                <div class="rounded-xl border border-white/10 bg-slate-900/70 p-3">
                  <p class="text-[10px] uppercase tracking-[0.14em] text-white/60">Weather</p>
                  <p class="mt-1 text-sm font-semibold text-white">32 C | H35 / L26</p>
                </div>
                <div class="rounded-xl border border-white/10 bg-slate-900/70 p-3">
                  <p class="text-[10px] uppercase tracking-[0.14em] text-white/60">Recent activity</p>
                  <p class="mt-1 text-sm font-semibold text-white">Finance review updated</p>
                </div>
              </div>

              <div class="mt-4 grid gap-3 md:grid-cols-4">
                <article v-for="kpi in dashboardPreviewKpis" :key="kpi.label" class="rounded-xl border border-white/10 bg-white/5 p-4">
                  <p class="text-[10px] uppercase tracking-[0.14em] text-white/60">{{ kpi.label }}</p>
                  <p class="mt-2 text-2xl font-bold text-white">{{ kpi.value }}</p>
                </article>
              </div>

              <div class="mt-4 grid gap-3 md:grid-cols-2">
                <div class="rounded-xl border border-white/10 bg-slate-900/65 p-4">
                  <p class="text-[10px] uppercase tracking-[0.14em] text-white/60">Facility Profitability</p>
                  <p class="mt-3 text-sm text-white/75">Monitor margin performance by location and spot underperforming accounts quickly.</p>
                </div>
                <div class="rounded-xl border border-white/10 bg-slate-900/65 p-4">
                  <p class="text-[10px] uppercase tracking-[0.14em] text-white/60">Compliance Trend</p>
                  <p class="mt-3 text-sm text-white/75">Track expiring credentials and completion velocity before they impact active placements.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="mx-auto mt-16 max-w-7xl px-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
          <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900">Purpose-built for staffing leaders</h2>
            <RouterLink to="/solutions" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-100">See Solutions</RouterLink>
          </div>
          <div class="mt-8 grid gap-5 md:grid-cols-3">
            <article v-for="card in pillars" :key="card.title" class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
              <img :src="card.image" :alt="card.alt" class="h-40 w-full object-cover" loading="lazy" />
              <div class="p-5">
                <h3 class="text-lg font-semibold text-slate-900">{{ card.title }}</h3>
                <p class="mt-2 text-sm text-slate-600">{{ card.description }}</p>
              </div>
            </article>
          </div>
        </div>
      </section>

      <section class="mx-auto mt-16 max-w-7xl px-6">
        <div class="grid gap-6 lg:grid-cols-2">
          <article v-for="section in featureNarratives" :key="section.title" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <img :src="section.image" :alt="section.alt" class="h-56 w-full rounded-2xl object-cover" loading="lazy" />
            <p class="mt-5 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ section.eyebrow }}</p>
            <h3 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ section.title }}</h3>
            <p class="mt-2 text-sm text-slate-600">{{ section.description }}</p>
            <ul class="mt-4 space-y-2 text-sm text-slate-700">
              <li v-for="bullet in section.bullets" :key="bullet" class="flex items-start gap-2">
                <span class="mt-1 inline-block h-2 w-2 rounded-full bg-slate-400" />
                <span>{{ bullet }}</span>
              </li>
            </ul>
          </article>
        </div>
      </section>

      <section class="mx-auto mt-20 max-w-7xl px-6">
        <div class="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-slate-950 px-8 py-14 text-white md:px-12">
          <img
            src="https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=2000&q=80"
            alt="Downtown office towers representing workforce scale and growth"
            class="absolute inset-0 h-full w-full object-cover opacity-25"
            loading="lazy"
          />
          <div class="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-900/88 to-slate-900/78" />
          <div class="relative z-10 max-w-2xl">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/80">Scale with confidence</p>
            <h2 class="mt-4 text-4xl font-bold leading-tight" :style="ctaHeadlineStyle">One platform for every hiring and staffing outcome.</h2>
            <p class="mt-4 text-lg text-white/85">From requisition intake to successful placement, AgencHQ gives your teams clean workflows and a modern command center experience.</p>
            <div class="mt-7 flex flex-wrap gap-3">
              <RouterLink to="/signup" class="rounded-xl bg-white px-6 py-3 text-sm font-semibold text-slate-950 hover:bg-slate-100">Get Started</RouterLink>
              <RouterLink to="/pricing" class="rounded-xl border border-white/35 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10">View Pricing</RouterLink>
            </div>
          </div>
        </div>
      </section>
    </main>
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
const heroHeadlineStyle = computed(() => ({
  color: `color-mix(in srgb, ${primarySolid.value} 38%, #e0f2fe)`,
  textShadow: `0 8px 28px color-mix(in srgb, ${primarySolid.value} 35%, transparent)`,
}));
const dashboardHeadlineStyle = computed(() => ({
  color: `color-mix(in srgb, ${primarySolid.value} 42%, #fce7f3)`,
  textShadow: `0 8px 24px color-mix(in srgb, ${primarySolid.value} 30%, transparent)`,
}));
const ctaHeadlineStyle = computed(() => ({
  color: `color-mix(in srgb, ${primarySolid.value} 40%, #dcfce7)`,
  textShadow: `0 8px 24px color-mix(in srgb, ${primarySolid.value} 32%, transparent)`,
}));

const metrics = [
  { label: 'Fill speed', value: '2.7x', note: 'faster average placement cycles' },
  { label: 'Pipeline visibility', value: '100%', note: 'real-time stage transparency' },
  { label: 'Team coordination', value: '8 hrs', note: 'saved weekly per recruiter' },
  { label: 'Revenue clarity', value: 'Live', note: 'always-on operational reporting' },
];
const dashboardPreviewKpis = [
  { label: 'Gross revenue', value: '$0.00' },
  { label: 'Labor cost', value: '$0.00' },
  { label: 'Net margin', value: '$0.00' },
  { label: 'Projected profit', value: '$0.00' },
];

const pillars = [
  {
    title: 'Faster talent placement',
    description: 'Give recruiters one workflow from sourcing to offer with no fragmented systems.',
    image: 'https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1400&q=80',
    alt: 'Staffing team meeting around a desk',
  },
  {
    title: 'Operational control',
    description: 'Track assignment readiness, approvals, and team handoffs with confidence.',
    image: 'https://images.unsplash.com/photo-1462899006636-339e08d1844e?auto=format&fit=crop&w=1400&q=80',
    alt: 'Modern team reviewing workforce operations dashboards',
  },
  {
    title: 'Clear business outcomes',
    description: 'Connect activity to placements, productivity, and financial performance.',
    image: 'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=1400&q=80',
    alt: 'Business leaders discussing staffing growth strategy',
  },
];

const featureNarratives = [
  {
    eyebrow: 'Workforce orchestration',
    title: 'Run the full staffing lifecycle in one place.',
    description: 'From demand planning and sourcing to onboarding and performance tracking, every team works in one connected flow.',
    bullets: ['Unified requisition and candidate pipelines', 'Shared activity timelines across teams', 'Role-based views for recruiting and operations'],
    image: 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1400&q=80',
    alt: 'Workforce team collaborating in open office',
  },
  {
    eyebrow: 'Automation + insights',
    title: 'Move faster with intelligent workflows.',
    description: 'Automate repetitive tasks, surface risks early, and keep teams focused on high-value work that closes roles faster.',
    bullets: ['Trigger-based alerts and reminders', 'Actionable dashboards by role', 'Performance trends tied to outcomes'],
    image: 'https://images.unsplash.com/photo-1556155092-490a1ba16284?auto=format&fit=crop&w=1400&q=80',
    alt: 'Laptop and team collaboration focused on productivity',
  },
];

function handleLoginClick() {
  if (auth.isAuthenticated) {
    if (!brand.loaded) brand.initFromStorage();
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
  animation: heroPan 18s ease-in-out infinite alternate;
}

@keyframes heroPan {
  from { transform: scale(1.02) translateX(0); }
  to { transform: scale(1.08) translateX(-2%); }
}
</style>
