<template>
  <div class="min-h-screen bg-[#f8fafc] text-slate-900">
    <PublicSiteHeader mode="apex" brand-name="AgencHQ" :primary-color="primarySolid" @apex-login="goLogin" />
    <div class="mx-auto max-w-7xl px-6 pb-20 pt-28">
      <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 p-8 shadow-lg md:p-12">
        <img src="https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1800&q=80" alt="Staffing leadership team in strategy session" class="absolute inset-0 h-full w-full object-cover opacity-30" loading="lazy" />
        <div class="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-900/85 to-slate-900/65" />
        <div class="relative z-10">
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/75">About AgencHQ</p>
          <h1 class="mt-4 text-4xl font-bold tracking-tight text-white md:text-5xl" style="text-shadow: 0 10px 24px rgba(2, 6, 23, 0.55);">Built to run the full staffing business, not just pieces of it.</h1>
          <p class="mt-4 max-w-3xl text-white/85">
            AgencHQ was created for staffing teams that need speed, control, and quality at the same time. We unify recruiting, operations, compliance,
            messaging, and finance into one premium operating experience.
          </p>
        </div>
      </section>

      <section class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <article v-for="stat in stats" :key="stat.label" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ stat.label }}</p>
          <p class="mt-2 text-2xl font-bold text-slate-900">{{ stat.value }}</p>
          <p class="mt-1 text-xs text-slate-600">{{ stat.note }}</p>
        </article>
      </section>

      <section class="mt-10 grid gap-5 md:grid-cols-2">
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1400&q=80" alt="Recruiting team in focused planning session" class="h-48 w-full rounded-xl object-cover" loading="lazy" />
          <h2 class="mt-4 text-xl font-semibold text-slate-900">Our mission</h2>
          <p class="mt-2 text-sm text-slate-600">Build the most reliable execution system for organizations that depend on high-quality staffing outcomes.</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <img src="https://images.unsplash.com/photo-1573164713988-8665fc963095?auto=format&fit=crop&w=1400&q=80" alt="Staffing leaders collaborating on growth goals" class="h-48 w-full rounded-xl object-cover" loading="lazy" />
          <h2 class="mt-4 text-xl font-semibold text-slate-900">Our approach</h2>
          <p class="mt-2 text-sm text-slate-600">Elegant UX, deep operational logic, and practical automation that helps real teams move faster every day.</p>
        </article>
      </section>

      <section class="mt-10 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">What makes AgencHQ different</h2>
        <div class="mt-6 grid gap-4 md:grid-cols-3">
          <article v-for="point in differentiators" :key="point.title" class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
            <h3 class="text-lg font-semibold text-slate-900">{{ point.title }}</h3>
            <p class="mt-2 text-sm text-slate-600">{{ point.description }}</p>
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
const stats = [
  { label: 'Unified workflows', value: '1 platform', note: 'No fragmented tools' },
  { label: 'Placement velocity', value: '2.7x', note: 'Faster decision cycles' },
  { label: 'Team alignment', value: '100%', note: 'Shared source of truth' },
  { label: 'Operational signal', value: 'Live', note: 'Always-on visibility' },
];
const differentiators = [
  {
    title: 'Built for staffing complexity',
    description: 'Role-specific workflows for recruiters, compliance, scheduling, finance, and operations in one system.',
  },
  {
    title: 'Designed to be admired',
    description: 'Premium UI and thoughtful interactions make the platform easy to adopt and enjoyable to use daily.',
  },
  {
    title: 'Ready for growth',
    description: 'Scales from emerging teams to enterprise staffing operators with clear controls and governance.',
  },
];
function goLogin() {
  router.push({ name: 'login' });
}
</script>

