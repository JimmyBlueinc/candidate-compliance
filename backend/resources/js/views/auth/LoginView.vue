<template>
  <div class="min-h-screen bg-white text-slate-900 selection:bg-emerald-500/20 relative overflow-hidden">
    <div class="grid grid-cols-1 lg:grid-cols-2 min-h-screen">
      <section class="relative hidden lg:block overflow-hidden">
        <img
          src="https://images.unsplash.com/photo-1584515933487-779824d29309?auto=format&fit=crop&w=1800&q=80"
          alt="Healthcare staffing operations"
          class="absolute inset-0 w-full h-full object-cover"
        />
        <div class="absolute inset-0 bg-gradient-to-r from-slate-950/78 via-slate-900/52 to-slate-800/30" />
        <div class="relative z-10 h-full p-12 flex flex-col justify-between">
          <div>
            <p class="text-xs uppercase tracking-[0.18em] font-bold text-white/80">AgencyHQ Healthcare Staffing</p>
            <h2 class="mt-4 text-white font-display text-4xl leading-tight max-w-xl">
              Silicon-grade operations,
              <span class="block">purpose-built for clinical staffing.</span>
            </h2>
            <p class="mt-5 text-white/80 text-sm leading-relaxed max-w-xl">
              Coordinate recruiters, facilities, compliance and finance from a single trusted platform built for healthcare velocity.
            </p>
          </div>

          <div class="grid grid-cols-3 gap-3 max-w-lg">
            <div class="rounded-2xl border border-white/20 bg-white/10 backdrop-blur-md p-4">
              <div class="text-white text-xl font-bold">98%</div>
              <div class="text-[11px] text-white/80 mt-1">Credential visibility</div>
            </div>
            <div class="rounded-2xl border border-white/20 bg-white/10 backdrop-blur-md p-4">
              <div class="text-white text-xl font-bold">24/7</div>
              <div class="text-[11px] text-white/80 mt-1">Live coordination</div>
            </div>
            <div class="rounded-2xl border border-white/20 bg-white/10 backdrop-blur-md p-4">
              <div class="text-white text-xl font-bold">1 Hub</div>
              <div class="text-[11px] text-white/80 mt-1">Recruiter + Facility</div>
            </div>
          </div>
        </div>
      </section>

      <section class="relative flex items-center justify-center px-6 py-12">
        <div class="absolute inset-0 pointer-events-none bg-[radial-gradient(circle_at_12%_20%,rgba(16,185,129,0.12),transparent_45%),radial-gradient(circle_at_85%_80%,rgba(79,70,229,0.12),transparent_48%)]" />
        <div class="w-full max-w-[440px] space-y-8 relative z-10">
          <div class="space-y-3">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl shadow-lg ring-1 ring-black/5 overflow-hidden" :style="logoWrapStyle">
              <img v-if="brand.logoUrl" :src="brand.logoUrl" alt="Logo" class="h-9 w-9 object-contain" />
              <i v-else class="pi pi-shield text-white text-[22px]" aria-hidden="true" />
            </div>
            <h1 class="text-3xl font-display font-semibold tracking-tight text-slate-900 leading-tight">Welcome back</h1>
            <p class="text-slate-600 text-sm font-medium leading-relaxed">Sign in to continue to your staffing operations dashboard.</p>
          </div>

          <form class="bg-white p-8 rounded-[30px] space-y-5 shadow-xl border border-slate-200/80" @submit.prevent="submit">
            <div v-if="errorMessage" class="p-3 bg-red-500/10 border border-red-500/20 text-red-700 text-xs font-bold rounded-2xl flex items-center gap-2">
              <i class="pi pi-exclamation-circle text-sm" aria-hidden="true" />
              {{ errorMessage }}
            </div>

            <div class="space-y-5">
              <div class="space-y-2">
                <label class="text-xs font-semibold text-slate-700 ml-1">Email address</label>
                <div class="relative group">
                  <div class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 transition-colors">
                    <i class="pi pi-envelope text-[14px]" aria-hidden="true" />
                  </div>
                  <input
                    v-model="email"
                    type="email"
                    autocomplete="email"
                    required
                    class="w-full rounded-2xl pl-12 pr-5 py-3 text-sm placeholder-slate-400 focus:outline-none transition-all duration-200 border border-slate-200 focus:border-[color:var(--p-primary-color)] focus:ring-4 focus:ring-[color:var(--p-primary-color)]/10"
                    placeholder="name@organization.com"
                  />
                </div>
              </div>

              <div class="space-y-2">
                <label class="text-xs font-semibold text-slate-700 ml-1">Password</label>
                <div class="relative group">
                  <div class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 transition-colors">
                    <i class="pi pi-lock text-[14px]" aria-hidden="true" />
                  </div>
                  <input
                    v-model="password"
                    :type="showPassword ? 'text' : 'password'"
                    autocomplete="current-password"
                    required
                    class="w-full rounded-2xl pl-12 pr-12 py-3 text-sm placeholder-slate-400 focus:outline-none transition-all duration-200 border border-slate-200 focus:border-[color:var(--p-primary-color)] focus:ring-4 focus:ring-[color:var(--p-primary-color)]/10"
                    placeholder="••••••••"
                  />
                  <button
                    type="button"
                    class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 transition-colors"
                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                    @click="showPassword = !showPassword"
                  >
                    <i :class="showPassword ? 'pi pi-eye-slash text-[14px]' : 'pi pi-eye text-[14px]'" aria-hidden="true" />
                  </button>
                </div>
              </div>

              <details class="group">
                <summary class="cursor-pointer select-none text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors">
                  Advanced
                </summary>
                <div class="mt-4 space-y-2">
                  <label class="text-xs font-semibold text-slate-700 ml-1">Organization ID (optional)</label>
                  <input
                    v-model="tenantId"
                    type="text"
                    class="w-full rounded-2xl px-5 py-3 text-sm placeholder-slate-400 focus:outline-none transition-all duration-200 border border-slate-200 focus:border-[color:var(--p-primary-color)] focus:ring-4 focus:ring-[color:var(--p-primary-color)]/10"
                    placeholder="organization_id"
                  />
                  <p class="text-xs text-slate-500 leading-relaxed ml-1">
                    If you use white-label domains, this can be auto-detected.
                  </p>
                </div>
              </details>
            </div>

            <div class="flex items-center justify-between">
              <label class="flex items-center gap-2 text-xs text-slate-600 font-semibold">
                <input v-model="rememberMe" type="checkbox" class="rounded border-slate-300 bg-white" />
                Remember me
              </label>
              <button
                type="button"
                class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors"
                @click="router.push('/forgot-password')"
              >
                Forgot password
              </button>
            </div>

            <button
              type="submit"
              :disabled="isSubmitting"
              class="w-full font-semibold py-3 rounded-2xl transition-all duration-200 shadow-sm disabled:opacity-50 flex items-center justify-center gap-2 text-sm"
              :style="primaryButtonStyle"
            >
              <template v-if="isSubmitting">
                <span class="w-5 h-5 border-2 border-white/35 border-t-white rounded-full animate-spin" />
              </template>
              <template v-else>
                <span>Sign In</span>
                <i class="pi pi-arrow-right text-[14px]" aria-hidden="true" />
              </template>
            </button>

            <div class="pt-4 text-center">
              <span class="text-slate-600 text-sm">Need an account? </span>
              <button type="button" class="text-sm font-semibold transition-colors" :style="linkStyle" @click="router.push('/signup')">
                Create one
              </button>
            </div>
          </form>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useBrandStore } from '../../stores/brand';

const router = useRouter();
const auth = useAuthStore();
const brand = useBrandStore();

const email = ref('');
const password = ref('');
const showPassword = ref(false);
const rememberMe = ref(false);
const tenantId = ref(auth.tenantId || '');

const isSubmitting = computed(() => auth.status === 'loading');

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');

const logoWrapStyle = computed(() => ({
    background: `linear-gradient(135deg, ${primaryColor.value}, rgba(59,130,246,0.85))`,
    boxShadow: `0 25px 60px -20px color-mix(in srgb, ${primaryColor.value} 45%, transparent)`,
}));

const primaryButtonStyle = computed(() => ({
    backgroundColor: primaryColor.value,
    borderColor: `color-mix(in srgb, ${primaryColor.value} 55%, rgba(15,23,42,0.25))`,
    color: '#ffffff',
    boxShadow: `0 24px 45px -20px color-mix(in srgb, ${primaryColor.value} 35%, transparent)`,
}));

const linkStyle = computed(() => ({
    color: primaryColor.value,
}));

const errorMessage = computed(() => {
    const err = auth.error;
    if (!err) return null;

    const message = err?.response?.data?.message;
    if (message) return message;

    const fieldErrors = err?.response?.data?.errors;
    if (fieldErrors?.email?.length) return fieldErrors.email[0];
    if (fieldErrors?.password?.length) return fieldErrors.password[0];
    return 'Unable to sign in. Please try again.';
});

async function submit() {
  try {
    await auth.login({
      email: email.value,
      password: password.value,
      rememberMe: rememberMe.value,
      tenantId: tenantId.value,
    });

    // Load brand to get organization subdomain
    if (!brand.loaded && !brand.loading) {
      await brand.load();
    }

    // Check if we need to redirect to tenant subdomain
    const currentHost = window.location.hostname;
    const isOnApex = currentHost === 'agenchq.com' || currentHost === 'www.agenchq.com';
    const hasTenantSubdomain = brand.subdomain && brand.subdomain !== '';

    if (isOnApex && hasTenantSubdomain) {
      // Redirect to tenant subdomain
      const tenantUrl = `https://${brand.subdomain}.agenchq.com/dashboard`;
      window.location.href = tenantUrl;
      return;
    }

    if (auth.user?.needs_onboarding) {
      try {
        await router.push({ name: 'onboarding' });
      } catch (navErr) {
        console.error('[SUBMIT] NAVIGATION ERROR:', navErr);
      }
      return;
    }

    if (auth.user?.role === 'candidate') {
      await router.push({ name: 'portal.dashboard' });
      return;
    }

    await router.push('/dashboard');
  } catch (err) {
    console.error(err);
  }
}

onMounted(async () => {
  if (!brand.loaded && !brand.loading) {
    await brand.load();
  }
});
</script>
