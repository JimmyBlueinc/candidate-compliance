<template>
  <div class="min-h-screen bg-white text-slate-900">
    <PublicSiteHeader mode="apex" brand-name="AgencHQ" :primary-color="primarySolid" @apex-login="goLogin" />
    <div class="mx-auto max-w-6xl px-6 pb-14 pt-28">
      <section class="rounded-3xl border border-slate-200 bg-gradient-to-br from-sky-50/60 to-white p-8 md:p-12">
        <h1 class="text-4xl font-bold tracking-tight md:text-5xl">Built for modern staffing teams</h1>
        <p class="mt-4 max-w-3xl text-slate-600">
          AgencHQ helps recruiting and workforce operations teams run faster with fewer systems, stronger compliance, and cleaner collaboration.
        </p>
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

