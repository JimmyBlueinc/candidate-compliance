<template>
  <div class="min-h-screen bg-[#f8fafc] text-slate-900">
    <PublicSiteHeader mode="apex" brand-name="AgencHQ" :primary-color="primarySolid" @apex-login="goLogin" />
    <div class="mx-auto max-w-7xl px-6 pb-20 pt-28">
      <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 p-8 shadow-lg md:p-12">
        <img src="https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1800&q=80" alt="Customer success team collaborating on staffing goals" class="absolute inset-0 h-full w-full object-cover opacity-30" loading="lazy" />
        <div class="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-900/85 to-slate-900/65" />
        <div class="relative z-10">
          <h1 class="text-4xl font-bold tracking-tight text-white md:text-5xl">Contact AgencHQ</h1>
          <p class="mt-3 max-w-3xl text-white/85">Talk with our team about recruiting operations, compliance workflows, and rollout support.</p>
        </div>
      </section>
      <section class="mt-8 grid gap-5 md:grid-cols-2">
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <img src="https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1400&q=80" alt="Staffing consultants discussing hiring strategy" class="h-44 w-full rounded-xl object-cover" loading="lazy" />
          <h2 class="mt-4 text-lg font-semibold text-slate-900">Book a strategy session</h2>
          <p class="mt-2 text-sm text-slate-600">Get a tailored walkthrough based on your staffing workflow, team size, and growth targets.</p>
          <ul class="mt-4 space-y-1 text-sm text-slate-700">
            <li>- Platform walkthrough personalized to your workflow</li>
            <li>- Suggested rollout plan for your team</li>
            <li>- Role-based feature and pricing guidance</li>
          </ul>
        </article>
        <form class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm grid grid-cols-1 gap-4 md:grid-cols-2">
          <input type="text" placeholder="Full name" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" />
          <input type="email" placeholder="Work email" class="rounded-xl border border-slate-300 px-3 py-2 text-sm" />
          <input type="text" placeholder="Organization" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2" />
          <textarea rows="4" placeholder="Tell us what you need" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2"></textarea>
          <div class="md:col-span-2">
            <button type="button" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Send Message</button>
          </div>
        </form>
      </section>

      <section class="mt-10 grid gap-4 md:grid-cols-3">
        <article v-for="channel in channels" :key="channel.title" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h3 class="text-lg font-semibold text-slate-900">{{ channel.title }}</h3>
          <p class="mt-2 text-sm text-slate-600">{{ channel.description }}</p>
          <p class="mt-3 text-sm font-semibold text-slate-800">{{ channel.value }}</p>
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
const channels = [
  {
    title: 'Sales inquiries',
    description: 'For product demos, pricing details, and fit assessment.',
    value: 'sales@agenchq.com',
  },
  {
    title: 'Partnerships',
    description: 'Explore integration, channel, and strategic partner opportunities.',
    value: 'partners@agenchq.com',
  },
  {
    title: 'Client success',
    description: 'Support and optimization for active customers.',
    value: 'success@agenchq.com',
  },
];
function goLogin() {
  router.push({ name: 'login' });
}
</script>

