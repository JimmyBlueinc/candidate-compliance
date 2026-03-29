<template>
  <div class="min-h-screen bg-[#f8fafc] text-slate-900">
    <PublicSiteHeader mode="apex" brand-name="AgencHQ" :primary-color="primarySolid" @apex-login="goLogin" />
    <div class="mx-auto max-w-7xl px-6 pb-16 pt-28">
      <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm md:p-12">
        <h1 class="text-4xl font-bold tracking-tight md:text-5xl">Platform features that scale with your operations</h1>
      </section>
      <section class="mt-8 grid gap-5 md:grid-cols-2">
        <article v-for="feature in features" :key="feature.title" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <img :src="feature.image" :alt="feature.alt" class="h-44 w-full rounded-xl object-cover" loading="lazy" />
          <h3 class="mt-4 text-lg font-semibold">{{ feature.title }}</h3>
          <p class="mt-2 text-sm text-slate-600">{{ feature.description }}</p>
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
const features = [
  {
    title: 'Pipeline intelligence',
    description: 'Track candidate movement, recruiter ownership, and conversion speed in one visual workflow.',
    image: 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1400&q=80',
    alt: 'Recruiting team planning staffing pipeline',
  },
  {
    title: 'Team collaboration',
    description: 'Centralize comments, direct messages, and task follow-ups so no candidate falls through.',
    image: 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1400&q=80',
    alt: 'Staffing team collaborating around laptop',
  },
  {
    title: 'Automation controls',
    description: 'Trigger reminders, streamline approvals, and run repeatable processes with confidence.',
    image: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=1400&q=80',
    alt: 'Business analytics and workflow automation on laptop',
  },
  {
    title: 'Executive reporting',
    description: 'See performance, productivity, and revenue signals in dashboards built for decision-making.',
    image: 'https://images.unsplash.com/photo-1553877522-43269d4ea984?auto=format&fit=crop&w=1400&q=80',
    alt: 'Leadership reviewing staffing reports',
  },
];
function goLogin() {
  router.push({ name: 'login' });
}
</script>

