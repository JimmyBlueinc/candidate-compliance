<template>
  <div class="min-h-screen bg-[#f8fafc] text-slate-900">
    <PublicSiteHeader mode="apex" brand-name="AgencHQ" :primary-color="primarySolid" @apex-login="goLogin" />
    <div class="max-w-7xl mx-auto px-6 pt-28 pb-16">
      <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm md:p-12">
        <h1 class="text-4xl md:text-5xl font-bold tracking-tight">Trusted by modern staffing operators</h1>
        <p class="mt-4 max-w-3xl text-slate-600">
          Teams use AgencHQ to improve placement speed, recruiter productivity, and operational execution quality.
        </p>
      </section>

      <section class="mt-10 grid gap-5 md:grid-cols-2">
        <article v-for="story in customerStories" :key="story.title" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          <img :src="story.image" :alt="story.alt" class="h-48 w-full object-cover" loading="lazy" />
          <div class="p-6">
            <div class="text-xs uppercase tracking-wider font-semibold text-slate-500">{{ story.tag }}</div>
            <h2 class="mt-2 text-lg font-semibold">{{ story.title }}</h2>
            <p class="mt-2 text-sm text-slate-600">{{ story.description }}</p>
            <div class="mt-4 grid grid-cols-3 gap-2">
              <div v-for="kpi in story.kpis" :key="kpi.label" class="rounded-xl border border-slate-200 bg-slate-50 p-2">
                <p class="text-[10px] uppercase tracking-wider text-slate-500">{{ kpi.label }}</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">{{ kpi.value }}</p>
              </div>
            </div>
          </div>
        </article>
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
const customerStories = [
  {
    tag: 'Case Highlight',
    title: 'Multi-city recruiting group',
    description: 'Unified fragmented recruiting tools into one operating system and accelerated placement decisions.',
    image: 'https://images.unsplash.com/photo-1521791136064-7986c2920216?auto=format&fit=crop&w=1400&q=80',
    alt: 'Business team celebrating staffing performance',
    kpis: [
      { label: 'Time to fill', value: '-34%' },
      { label: 'Pipeline clarity', value: '+51%' },
      { label: 'Coordination', value: '+40%' },
    ],
  },
  {
    tag: 'Case Highlight',
    title: 'Enterprise staffing operator',
    description: 'Scaled operations with standardized workflows and role-based accountability across recruiting teams.',
    image: 'https://images.unsplash.com/photo-1573164713988-8665fc963095?auto=format&fit=crop&w=1400&q=80',
    alt: 'Enterprise staffing team in meeting room',
    kpis: [
      { label: 'Recruiter output', value: '+29%' },
      { label: 'Response speed', value: '2.1x' },
      { label: 'Reporting lag', value: '-68%' },
    ],
  },
];

function goLogin() {
  router.push({ name: 'login' });
}
</script>
