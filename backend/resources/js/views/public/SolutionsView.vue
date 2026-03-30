<template>
  <div class="min-h-screen bg-[#f8fafc] text-slate-900">
    <PublicSiteHeader mode="apex" brand-name="AgencHQ" :primary-color="primarySolid" @apex-login="goLogin" />
    <div class="max-w-7xl mx-auto px-6 pt-28 pb-20">
      <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 p-8 shadow-lg md:p-12">
        <img src="https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=1800&q=80" alt="Operational leadership reviewing staffing plans" class="absolute inset-0 h-full w-full object-cover opacity-30" loading="lazy" />
        <div class="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-900/85 to-slate-900/65" />
        <div class="relative z-10">
          <h1 class="text-4xl md:text-5xl font-bold tracking-tight text-white" style="text-shadow: 0 10px 24px rgba(2, 6, 23, 0.55);">Solutions for every staffing growth stage</h1>
          <p class="mt-4 max-w-3xl text-white/85">
          Whether you are scaling a recruiting desk or running multi-team workforce operations, AgencHQ gives you modern workflows and clear execution control.
          </p>
        </div>
      </section>

      <section class="mt-10 grid grid-cols-1 gap-5 md:grid-cols-3">
        <article v-for="item in solutionCards" :key="item.title" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          <img :src="item.image" :alt="item.alt" class="h-44 w-full object-cover" loading="lazy" />
          <div class="p-5">
            <h2 class="text-lg font-semibold">{{ item.title }}</h2>
            <p class="mt-2 text-sm text-slate-600">{{ item.description }}</p>
          </div>
        </article>
      </section>

      <section class="mt-12 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">Outcome-focused solution pillars</h2>
        <div class="mt-6 grid gap-4 md:grid-cols-2">
          <article v-for="pillar in pillars" :key="pillar.title" class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
            <h3 class="text-lg font-semibold text-slate-900">{{ pillar.title }}</h3>
            <p class="mt-2 text-sm text-slate-600">{{ pillar.description }}</p>
          </article>
        </div>
      </section>

      <section class="mt-10 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">How teams win with AgencHQ</h2>
        <div class="mt-6 grid gap-4 md:grid-cols-3">
          <article v-for="step in executionFlow" :key="step.title" class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ step.phase }}</p>
            <h3 class="mt-2 text-lg font-semibold text-slate-900">{{ step.title }}</h3>
            <p class="mt-2 text-sm text-slate-600">{{ step.description }}</p>
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
const solutionCards = [
  {
    title: 'High-volume recruiting',
    description: 'Keep hiring teams aligned with shared pipelines, scorecards, and recruiter workflows.',
    image: 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1400&q=80',
    alt: 'Recruiting team collaborating in office',
  },
  {
    title: 'Distributed workforce operations',
    description: 'Coordinate assignment readiness, handoffs, and team communication from one dashboard.',
    image: 'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=1400&q=80',
    alt: 'Workforce leaders discussing operations',
  },
  {
    title: 'Staffing performance visibility',
    description: 'Track productivity and business outcomes with role-specific analytics and clean reporting.',
    image: 'https://images.unsplash.com/photo-1462899006636-339e08d1844e?auto=format&fit=crop&w=1400&q=80',
    alt: 'Team reviewing staffing analytics',
  },
];
const pillars = [
  { title: 'Recruiter productivity', description: 'Reduce manual admin and increase time spent on candidate relationships and placements.' },
  { title: 'Manager oversight', description: 'Gain clarity on pipeline health, assignment risk, and execution bottlenecks instantly.' },
  { title: 'Cross-team collaboration', description: 'Align operations, finance, and talent teams through shared records and status updates.' },
  { title: 'Scalable process design', description: 'Standardize workflows so your team can scale headcount and volume without chaos.' },
];
const executionFlow = [
  {
    phase: 'Phase 01',
    title: 'Structure demand clearly',
    description: 'Capture requisitions and role details with enough context for faster sourcing and cleaner qualification.',
  },
  {
    phase: 'Phase 02',
    title: 'Coordinate teams in real time',
    description: 'Keep recruiting, compliance, and operations aligned with shared messaging and status visibility.',
  },
  {
    phase: 'Phase 03',
    title: 'Measure and improve outcomes',
    description: 'Use role-level analytics and trend reporting to continuously improve placement quality and speed.',
  },
];

function goLogin() {
  router.push({ name: 'login' });
}
</script>
