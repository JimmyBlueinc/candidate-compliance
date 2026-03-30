<template>
  <div class="min-h-screen bg-[#f8fafc] text-slate-900">
    <PublicSiteHeader mode="apex" brand-name="AgencHQ" :primary-color="primarySolid" @apex-login="goLogin" />
    <div class="max-w-7xl mx-auto px-6 pt-28 pb-20">
      <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 p-8 shadow-lg md:p-12">
        <img src="https://images.unsplash.com/photo-1462899006636-339e08d1844e?auto=format&fit=crop&w=1800&q=80" alt="Finance and operations review session" class="absolute inset-0 h-full w-full object-cover opacity-30" loading="lazy" />
        <div class="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-900/85 to-slate-900/65" />
        <div class="relative z-10">
          <h1 class="text-4xl md:text-5xl font-bold tracking-tight text-white" style="text-shadow: 0 10px 24px rgba(2, 6, 23, 0.55);">Pricing designed for staffing scale</h1>
          <p class="mt-4 max-w-3xl text-white/85">Start lean, then expand features as your team, candidate volume, and client demand grow.</p>
        </div>
      </section>

      <section class="mt-10 grid grid-cols-1 gap-5 md:grid-cols-3">
        <article v-for="plan in plans" :key="plan.name" class="rounded-2xl border p-6 shadow-sm" :class="plan.featured ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white'">
          <p class="text-xs uppercase tracking-wider font-semibold" :class="plan.featured ? 'text-white/70' : 'text-slate-500'">{{ plan.name }}</p>
          <p class="mt-3 text-3xl font-bold">{{ plan.price }}<span class="text-base font-medium" :class="plan.featured ? 'text-white/70' : 'text-slate-500'">{{ plan.period }}</span></p>
          <p class="mt-3 text-sm" :class="plan.featured ? 'text-white/85' : 'text-slate-600'">{{ plan.description }}</p>
          <ul class="mt-5 space-y-2 text-sm" :class="plan.featured ? 'text-white/85' : 'text-slate-600'">
            <li v-for="feature in plan.features" :key="feature">- {{ feature }}</li>
          </ul>
          <button type="button" class="mt-6 w-full rounded-xl px-4 py-2 text-sm font-semibold" :class="plan.featured ? 'bg-white text-slate-900' : 'bg-slate-900 text-white'">
            {{ plan.cta }}
          </button>
        </article>
      </section>

      <section class="mt-10 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Included in every plan</h2>
        <div class="mt-6 grid gap-4 md:grid-cols-3">
          <article v-for="item in baselineIncludes" :key="item.title" class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
            <h3 class="text-lg font-semibold text-slate-900">{{ item.title }}</h3>
            <p class="mt-2 text-sm text-slate-600">{{ item.description }}</p>
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
const plans = [
  {
    name: 'Starter',
    price: '$299',
    period: '/mo',
    description: 'For focused recruiting teams launching a clean staffing workflow.',
    features: ['Candidate pipeline and search', 'Core messaging and alerts', 'Basic reporting and dashboards'],
    cta: 'Start Starter',
    featured: false,
  },
  {
    name: 'Growth',
    price: '$699',
    period: '/mo',
    description: 'For scaling operations that need deeper automation and team visibility.',
    features: ['Everything in Starter', 'Advanced analytics and workforce intelligence', 'Integrations and automation controls'],
    cta: 'Book Growth Demo',
    featured: true,
  },
  {
    name: 'Enterprise',
    price: 'Custom',
    period: '',
    description: 'For high-volume operators with custom governance and rollout needs.',
    features: ['Dedicated onboarding support', 'Custom security and controls', 'Enterprise service and success plan'],
    cta: 'Talk to Sales',
    featured: false,
  },
];
const baselineIncludes = [
  {
    title: 'Implementation support',
    description: 'Guided onboarding for your team, from setup to first production workflows.',
  },
  {
    title: 'Secure access controls',
    description: 'Role-based permissions and structured governance built for staffing organizations.',
  },
  {
    title: 'Continuous product upgrades',
    description: 'Ongoing enhancements across recruiting, compliance, operations, and reporting.',
  },
];

function goLogin() {
  router.push({ name: 'login' });
}
</script>
