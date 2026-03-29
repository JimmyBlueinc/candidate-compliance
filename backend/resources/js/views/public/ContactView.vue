<template>
  <div class="min-h-screen bg-white text-slate-900">
    <PublicSiteHeader mode="apex" brand-name="AgencHQ" :primary-color="primarySolid" @apex-login="goLogin" />
    <div class="mx-auto max-w-6xl px-6 pb-14 pt-28">
      <section class="rounded-3xl border border-slate-200 bg-gradient-to-br from-emerald-50/60 to-white p-8 md:p-12">
        <h1 class="text-4xl font-bold tracking-tight md:text-5xl">Contact AgencHQ</h1>
        <p class="mt-3 text-slate-600">Talk with our team about recruiting operations, compliance workflows, and rollout support.</p>
      </section>
      <section class="mt-8 rounded-2xl border border-slate-200 p-6">
        <form class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <input type="text" placeholder="Full name" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" />
          <input type="email" placeholder="Work email" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" />
          <input type="text" placeholder="Organization" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2" />
          <textarea rows="4" placeholder="Tell us what you need" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2"></textarea>
          <div class="md:col-span-2">
            <button type="button" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Send Message</button>
          </div>
        </form>
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

