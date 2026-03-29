<template>
  <div class="min-h-screen bg-[#f8fafc] text-slate-900">
    <PublicSiteHeader mode="apex" brand-name="AgencHQ" :primary-color="primarySolid" @apex-login="goLogin" />
    <div class="mx-auto max-w-7xl px-6 pb-20 pt-28">
      <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 p-8 shadow-lg md:p-12">
        <img src="https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1800&q=80" alt="Staffing operations center" class="absolute inset-0 h-full w-full object-cover opacity-30" loading="lazy" />
        <div class="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-900/85 to-slate-900/65" />
        <div class="relative z-10">
          <h1 class="text-4xl font-bold tracking-tight text-white md:text-5xl">Platform features that scale with your operations</h1>
          <p class="mt-4 max-w-3xl text-white/85">Everything your team needs to recruit, coordinate, and deliver great staffing outcomes in one modern workspace.</p>
        </div>
      </section>

      <section class="mt-8 grid gap-5 md:grid-cols-2">
        <article v-for="feature in features" :key="feature.title" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <img :src="feature.image" :alt="feature.alt" class="h-44 w-full rounded-xl object-cover" loading="lazy" />
          <h3 class="mt-4 text-lg font-semibold">{{ feature.title }}</h3>
          <p class="mt-2 text-sm text-slate-600">{{ feature.description }}</p>
          <ul class="mt-4 space-y-1 text-sm text-slate-700">
            <li v-for="item in feature.highlights" :key="item">- {{ item }}</li>
          </ul>
        </article>
      </section>

      <section class="mt-10 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Designed for every core team</h2>
        <div class="mt-6 grid gap-4 md:grid-cols-3">
          <article v-for="role in roleFit" :key="role.title" class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
            <h3 class="text-lg font-semibold text-slate-900">{{ role.title }}</h3>
            <p class="mt-2 text-sm text-slate-600">{{ role.description }}</p>
          </article>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useBrandStore } from '../../stores/brand';
import PublicSiteHeader from '../../components/public/PublicSiteHeader.vue';

const router = useRouter();
const brand = useBrandStore();
const primarySolid = computed(() => brand.primaryColor || '#2563eb');
const features = [
  {
    title: 'Pipeline intelligence',
    description: 'Track candidate movement, recruiter ownership, and conversion speed in one visual workflow.',
    image: 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1400&q=80',
    alt: 'Recruiting team planning staffing pipeline',
    highlights: ['Visual stage tracking', 'Ownership clarity', 'Conversion visibility'],
  },
  {
    title: 'Team collaboration',
    description: 'Centralize comments, direct messages, and task follow-ups so no candidate falls through.',
    image: 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1400&q=80',
    alt: 'Staffing team collaborating around laptop',
    highlights: ['Instant team chat', 'Unread priorities', 'Context-aware communication'],
  },
  {
    title: 'Automation controls',
    description: 'Trigger reminders, streamline approvals, and run repeatable processes with confidence.',
    image: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=1400&q=80',
    alt: 'Business analytics and workflow automation on laptop',
    highlights: ['Approval workflows', 'Auto reminders', 'Reduced manual touchpoints'],
  },
  {
    title: 'Executive reporting',
    description: 'See performance, productivity, and revenue signals in dashboards built for decision-making.',
    image: 'https://images.unsplash.com/photo-1553877522-43269d4ea984?auto=format&fit=crop&w=1400&q=80',
    alt: 'Leadership reviewing staffing reports',
    highlights: ['Real-time KPI views', 'Profitability tracking', 'Forecast-ready metrics'],
  },
];
const roleFit = [
  {
    title: 'Recruiting teams',
    description: 'Source, track, and place candidates faster with clearer handoffs and less admin burden.',
  },
  {
    title: 'Operations leaders',
    description: 'Run assignment execution with dependable visibility into status, blockers, and team throughput.',
  },
  {
    title: 'Compliance and finance',
    description: 'Keep credential readiness, documentation, and financial controls tightly coordinated.',
  },
];
function goLogin() {
  router.push({ name: 'login' });
}
</script>

