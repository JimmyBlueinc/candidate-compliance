<template>
  <div class="min-h-screen bg-white text-slate-900 antialiased">
    <nav class="fixed top-0 left-0 right-0 z-50 border-b border-slate-200/60 bg-white/90 backdrop-blur">
      <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between gap-5">
        <RouterLink to="/" class="flex items-center gap-2">
          <div class="h-9 w-9 rounded-xl flex items-center justify-center text-white font-bold" :style="{ backgroundColor: primarySolid }">A</div>
          <span class="font-bold tracking-tight">{{ brand.name || 'AgencyHQ' }}</span>
        </RouterLink>

        <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
          <RouterLink to="/solutions" class="hover:text-slate-900">Solutions</RouterLink>
          <RouterLink to="/customers" class="hover:text-slate-900">Customers</RouterLink>
          <RouterLink to="/pricing" class="hover:text-slate-900">Pricing</RouterLink>
          <RouterLink to="/jobs" class="hover:text-slate-900">Jobs</RouterLink>
        </div>

        <div class="hidden md:flex items-center gap-3">
          <button type="button" class="px-4 py-2 text-sm font-medium text-slate-700" @click="handleLoginClick">Log in</button>
          <RouterLink to="/signup" class="px-4 py-2 rounded-xl text-sm font-semibold text-white" :style="{ backgroundColor: primarySolid }">
            Get Started
          </RouterLink>
        </div>
      </div>
    </nav>

    <main class="pt-28 pb-20 px-6">
      <section class="max-w-7xl mx-auto">
        <div class="rounded-3xl border border-slate-200 overflow-hidden bg-gradient-to-br from-slate-50 via-white to-blue-50">
          <div class="grid grid-cols-1 lg:grid-cols-12">
            <div class="lg:col-span-7 p-8 md:p-12">
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Healthcare Staffing Platform</p>
              <h1 class="mt-4 text-4xl md:text-6xl font-bold tracking-tight leading-tight">
                The operating system for modern agency operations.
              </h1>
              <p class="mt-5 text-slate-600 max-w-2xl">
                Unify recruiting, compliance, facilities, and finance workflows in one platform built for clinical staffing teams.
              </p>
              <div class="mt-7 flex flex-wrap gap-3">
                <RouterLink to="/signup" class="px-6 py-3 rounded-xl text-sm font-semibold text-white" :style="{ backgroundColor: primarySolid }">Start your trial</RouterLink>
                <RouterLink to="/pricing" class="px-6 py-3 rounded-xl text-sm font-semibold border border-slate-300 text-slate-800 bg-white">View pricing</RouterLink>
              </div>
            </div>

            <div class="lg:col-span-5 p-6 md:p-8 border-t lg:border-t-0 lg:border-l border-slate-200 bg-white/80">
              <div class="rounded-2xl border border-slate-200 p-4 bg-white">
                <svg viewBox="0 0 360 220" class="w-full h-auto">
                  <defs>
                    <linearGradient id="g1" x1="0" x2="1">
                      <stop offset="0%" stop-color="#4f46e5" />
                      <stop offset="100%" stop-color="#0ea5e9" />
                    </linearGradient>
                  </defs>
                  <rect x="0" y="0" width="360" height="220" rx="18" fill="#f8fafc" />
                  <rect x="20" y="24" width="120" height="16" rx="8" fill="#cbd5e1" />
                  <rect x="20" y="54" width="320" height="40" rx="12" fill="url(#g1)" opacity="0.18" />
                  <rect x="20" y="108" width="96" height="84" rx="12" fill="#e2e8f0" />
                  <rect x="132" y="108" width="96" height="84" rx="12" fill="#e2e8f0" />
                  <rect x="244" y="108" width="96" height="84" rx="12" fill="#e2e8f0" />
                </svg>
              </div>
              <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl border border-slate-200 bg-white p-3">
                  <div class="text-slate-500 text-xs">Fill rate</div>
                  <div class="text-xl font-bold text-emerald-600">94.2%</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-3">
                  <div class="text-slate-500 text-xs">Live facilities</div>
                  <div class="text-xl font-bold">128</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="max-w-7xl mx-auto mt-12 grid grid-cols-1 md:grid-cols-3 gap-4">
        <article class="rounded-2xl border border-slate-200 p-6 bg-white">
          <h2 class="font-semibold text-lg">Command Center</h2>
          <p class="mt-2 text-sm text-slate-600">Realtime visibility into staffing, compliance, and financial posture.</p>
        </article>
        <article class="rounded-2xl border border-slate-200 p-6 bg-white">
          <h2 class="font-semibold text-lg">Facility Workflows</h2>
          <p class="mt-2 text-sm text-slate-600">Manage contracts, facility users, and billing context in one workspace.</p>
        </article>
        <article class="rounded-2xl border border-slate-200 p-6 bg-white">
          <h2 class="font-semibold text-lg">Integration-ready</h2>
          <p class="mt-2 text-sm text-slate-600">Enable connectors as your agency scales operations.</p>
        </article>
      </section>
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useBrandStore } from '../../stores/brand';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const brand = useBrandStore();
const auth = useAuthStore();

const primaryColor = computed(() => brand.primaryColor || '#2563eb');
const primarySolid = computed(() => {
  const c = primaryColor.value;
  return typeof c === 'string' && c.trim().length ? c : '#2563eb';
});

function handleLoginClick() {
  if (auth.isAuthenticated) {
    if (!brand.loaded) {
      brand.initFromStorage();
    }
    const isOrgUser = !['candidate', 'facility', 'platform_admin'].includes(auth.user?.role);
    if (brand.subdomain && isOrgUser) {
      window.location.href = `https://${brand.subdomain}.agenchq.com/dashboard`;
      return;
    }
  }
  router.push({ name: 'login' });
}
</script>
