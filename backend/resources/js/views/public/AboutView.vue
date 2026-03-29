<template>
  <div class="min-h-screen bg-[#f8fafc] text-slate-900">
    <PublicSiteHeader mode="apex" brand-name="AgencHQ" :primary-color="primarySolid" @apex-login="goLogin" />
    <div class="mx-auto max-w-7xl px-6 pb-16 pt-28">
      <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm md:p-12">
        <h1 class="text-4xl font-bold tracking-tight md:text-5xl">Built for modern staffing teams</h1>
        <p class="mt-4 max-w-3xl text-slate-600">
          AgencHQ helps recruiting and workforce operations teams run faster with fewer systems, stronger compliance, and cleaner collaboration.
        </p>
      </section>

      <section class="mt-10 grid gap-5 md:grid-cols-2">
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1400&q=80" alt="Recruiting team in focused planning session" class="h-48 w-full rounded-xl object-cover" loading="lazy" />
          <h2 class="mt-4 text-xl font-semibold text-slate-900">Our mission</h2>
          <p class="mt-2 text-sm text-slate-600">Build the most reliable operating system for organizations that depend on fast and high-quality staffing execution.</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <img src="https://images.unsplash.com/photo-1573164713988-8665fc963095?auto=format&fit=crop&w=1400&q=80" alt="Staffing leaders collaborating on growth goals" class="h-48 w-full rounded-xl object-cover" loading="lazy" />
          <h2 class="mt-4 text-xl font-semibold text-slate-900">Our approach</h2>
          <p class="mt-2 text-sm text-slate-600">We combine elegant product design with operational depth so teams can scale without losing control.</p>
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
function goLogin() {
  router.push({ name: 'login' });
}
</script>

