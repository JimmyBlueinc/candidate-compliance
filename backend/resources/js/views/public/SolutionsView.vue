<template>
  <div class="min-h-screen bg-white text-slate-900">
    <PublicSiteHeader mode="apex" brand-name="AgencHQ" :primary-color="primarySolid" @apex-login="goLogin" />
    <div class="max-w-6xl mx-auto px-6 pt-28 pb-14">

      <section class="rounded-3xl border border-slate-200 p-8 md:p-12 bg-gradient-to-br from-slate-50 to-white">
        <h1 class="text-4xl md:text-5xl font-bold tracking-tight">Solutions for healthcare staffing operations</h1>
        <p class="mt-4 text-slate-600 max-w-3xl">
          Built to unify compliance, recruiting, scheduling, finance, and facility workflows in one operating system.
        </p>
      </section>

      <section class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-5">
        <article class="rounded-2xl border border-slate-200 p-6">
          <div class="h-24 rounded-xl bg-gradient-to-br from-blue-100 to-indigo-100 mb-4" />
          <h2 class="font-semibold text-lg">Recruiting Velocity</h2>
          <p class="mt-2 text-sm text-slate-600">Source candidates, track pipeline stages, and reduce placement time.</p>
        </article>
        <article class="rounded-2xl border border-slate-200 p-6">
          <div class="h-24 rounded-xl bg-gradient-to-br from-emerald-100 to-cyan-100 mb-4" />
          <h2 class="font-semibold text-lg">Compliance Confidence</h2>
          <p class="mt-2 text-sm text-slate-600">Automate credential visibility, expirations, and review workflows.</p>
        </article>
        <article class="rounded-2xl border border-slate-200 p-6">
          <div class="h-24 rounded-xl bg-gradient-to-br from-violet-100 to-fuchsia-100 mb-4" />
          <h2 class="font-semibold text-lg">Finance Intelligence</h2>
          <p class="mt-2 text-sm text-slate-600">Track margin by facility, export reports, and streamline invoicing.</p>
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
