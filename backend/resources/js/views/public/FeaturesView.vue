<template>
  <div class="min-h-screen bg-white text-slate-900">
    <PublicSiteHeader mode="apex" brand-name="AgencHQ" :primary-color="primarySolid" @apex-login="goLogin" />
    <div class="mx-auto max-w-6xl px-6 pb-14 pt-28">
      <section class="rounded-3xl border border-slate-200 bg-gradient-to-br from-indigo-50/60 to-white p-8 md:p-12">
        <h1 class="text-4xl font-bold tracking-tight md:text-5xl">Platform features that scale with your operations</h1>
      </section>
      <section class="mt-8 grid gap-4 md:grid-cols-2">
        <article class="rounded-2xl border border-slate-200 p-5">
          <h3 class="text-lg font-semibold">Candidate pipeline intelligence</h3>
          <p class="mt-2 text-sm text-slate-600">Track stage movement, ownership, and conversion signals in one view.</p>
        </article>
        <article class="rounded-2xl border border-slate-200 p-5">
          <h3 class="text-lg font-semibold">Compliance automation</h3>
          <p class="mt-2 text-sm text-slate-600">Monitor credential expirations and approval workflows proactively.</p>
        </article>
        <article class="rounded-2xl border border-slate-200 p-5">
          <h3 class="text-lg font-semibold">Messaging + notifications</h3>
          <p class="mt-2 text-sm text-slate-600">Coordinate recruiting work with direct conversations and alerts.</p>
        </article>
        <article class="rounded-2xl border border-slate-200 p-5">
          <h3 class="text-lg font-semibold">Integrations hub</h3>
          <p class="mt-2 text-sm text-slate-600">Connect external tools with secure credentials and health checks.</p>
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

