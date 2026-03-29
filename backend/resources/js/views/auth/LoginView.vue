<template>
  <div class="auth-shell min-h-screen text-slate-900">
    <div class="grid min-h-screen grid-cols-1 lg:grid-cols-[minmax(0,1.02fr)_minmax(0,0.98fr)]">
      <AuthRotatingShowcase
        kicker="Workforce Platform"
        heading="Where great professionals meet trusted opportunities."
        subtitle="Discover roles, move through onboarding faster, and stay connected to recruiters from one premium workspace."
      />

      <section class="relative flex items-center justify-center px-6 py-10 lg:justify-start lg:px-10 xl:px-12">
        <div class="auth-glow auth-glow-a" />
        <div class="auth-glow auth-glow-b" />

        <div class="relative z-10 w-full max-w-[460px] space-y-6 lg:max-w-[500px]">
          <div class="space-y-3 text-center lg:text-left">
            <div class="inline-flex h-12 w-12 items-center justify-center overflow-hidden rounded-2xl shadow-lg ring-1 ring-slate-200" :style="logoWrapStyle">
              <img v-if="brand.logoUrl" :src="brand.logoUrl" alt="Logo" class="h-8 w-8 object-contain" />
              <i v-else class="pi pi-heart-fill text-white text-[18px]" aria-hidden="true" />
            </div>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Welcome back</h1>
            <p class="text-sm leading-relaxed text-slate-600">Sign in to continue your healthcare staffing journey.</p>
          </div>

          <form class="auth-card space-y-5" @submit.prevent="submit">
            <div v-if="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700">
              {{ errorMessage }}
            </div>

            <div class="space-y-2">
              <label class="text-xs font-semibold text-slate-700">Email address</label>
              <input
                v-model="email"
                type="email"
                autocomplete="email"
                required
                class="auth-input"
                placeholder="name@organization.com"
              />
            </div>

            <div class="space-y-2">
              <label class="text-xs font-semibold text-slate-700">Password</label>
              <div class="relative">
                <input
                  v-model="password"
                  :type="showPassword ? 'text' : 'password'"
                  autocomplete="current-password"
                  required
                  class="auth-input pr-11"
                  placeholder="••••••••"
                />
                <button
                  type="button"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700"
                  :aria-label="showPassword ? 'Hide password' : 'Show password'"
                  @click="showPassword = !showPassword"
                >
                  <i :class="showPassword ? 'pi pi-eye-slash' : 'pi pi-eye'" aria-hidden="true" />
                </button>
              </div>
            </div>

            <details>
              <summary class="cursor-pointer text-xs font-semibold text-slate-500 hover:text-slate-800">Advanced options</summary>
              <div class="mt-3 space-y-2">
                <label class="text-xs font-semibold text-slate-700">Organization ID (optional)</label>
                <input v-model="tenantId" type="text" class="auth-input" placeholder="organization_id" />
              </div>
            </details>

            <div class="flex items-center justify-between text-xs">
              <label class="flex items-center gap-2 text-slate-600">
                <input v-model="rememberMe" type="checkbox" class="rounded border-slate-300" />
                Remember me
              </label>
              <button type="button" class="font-semibold text-slate-600 hover:text-slate-900" @click="router.push('/forgot-password')">
                Forgot password
              </button>
            </div>

            <button type="submit" :disabled="isSubmitting" class="auth-primary-btn" :style="primaryButtonStyle">
              <span v-if="isSubmitting" class="h-4 w-4 animate-spin rounded-full border-2 border-white/35 border-t-white" />
              <span v-else>Sign In</span>
            </button>

            <div class="pt-1">
              <div class="flex items-center gap-3 text-xs text-slate-400">
                <div class="h-px flex-1 bg-slate-200" />
                <span>or continue with</span>
                <div class="h-px flex-1 bg-slate-200" />
              </div>
              <div ref="googleButtonEl" class="mt-3 flex justify-center lg:justify-start" />
              <p v-if="googleMessage" class="mt-2 text-[11px] text-slate-500">{{ googleMessage }}</p>
            </div>

            <div class="pt-2 text-center lg:text-left">
              <span class="text-sm text-slate-600">Need an account? </span>
              <button type="button" class="text-sm font-semibold" :style="linkStyle" @click="router.push('/signup')">Create one</button>
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
import { apiPost } from '../../lib/api';
import { renderGoogleButton } from '../../lib/googleIdentity';
import AuthRotatingShowcase from '../../components/auth/AuthRotatingShowcase.vue';

const router = useRouter();
const auth = useAuthStore();
const brand = useBrandStore();

const email = ref('');
const password = ref('');
const showPassword = ref(false);
const rememberMe = ref(false);
const tenantId = ref(auth.tenantId || '');
const googleButtonEl = ref(null);
const googleMessage = ref('');

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

    await redirectAfterAuth();
  } catch (err) {
    console.error(err);
  }
}

async function redirectAfterAuth() {
  if (!brand.loaded && !brand.loading) {
    await brand.load();
  }

  const currentHost = window.location.hostname;
  const isOnApex = currentHost === 'agenchq.com' || currentHost === 'www.agenchq.com';
  const tenantSubdomain = String(auth.user?.organization?.subdomain || brand.subdomain || '').trim().toLowerCase();
  const hasTenantSubdomain = tenantSubdomain !== '';

  if (isOnApex && hasTenantSubdomain) {
    window.location.href = `https://${tenantSubdomain}.agenchq.com/dashboard`;
    return;
  }

  if (auth.user?.needs_onboarding) {
    await router.push({ name: 'onboarding' });
    return;
  }

  if (auth.user?.role === 'candidate') {
    await router.push({ name: 'portal.dashboard' });
    return;
  }

  await router.push('/dashboard');
}

async function handleGoogleCredential(idToken) {
  if (!idToken) return;
  googleMessage.value = '';
  try {
    const response = await apiPost('/google/authenticate', {
      id_token: idToken,
      intent: 'login',
      tenant_id: tenantId.value ? Number(tenantId.value) : null,
    });

    const payload = response?.data ? response.data : response;
    if (!payload?.token || !payload?.user) {
      throw new Error('Google login response is missing session data.');
    }

    auth.setSession({ token: payload.token, user: payload.user });
    if (payload.user?.organization_id) {
      auth.setTenantId(String(payload.user.organization_id));
    }
    await redirectAfterAuth();
  } catch (e) {
    googleMessage.value = e?.response?.data?.message || e?.message || 'Google sign-in failed.';
  }
}

async function initGoogleButton() {
  const clientId = String(import.meta.env.VITE_GOOGLE_CLIENT_ID || '').trim();
  if (!clientId || !googleButtonEl.value) return;
  try {
    await renderGoogleButton(googleButtonEl.value, clientId, handleGoogleCredential, {
      text: 'continue_with',
      width: 340,
      theme: 'outline',
    });
  } catch (e) {
    googleMessage.value = e?.message || 'Google sign-in is currently unavailable.';
  }
}

onMounted(async () => {
  if (!brand.loaded && !brand.loading) {
    await brand.load();
  }
  await initGoogleButton();
});
</script>

<style scoped>
.auth-shell {
  background:
    radial-gradient(620px 420px at 8% 8%, rgba(79, 70, 229, 0.14), transparent 60%),
    radial-gradient(580px 380px at 90% 82%, rgba(14, 165, 233, 0.12), transparent 60%),
    #f8fafc;
}

.auth-card {
  border-radius: 1.5rem;
  border: 1px solid rgba(148, 163, 184, 0.28);
  background: rgba(255, 255, 255, 0.96);
  padding: 1.6rem;
  box-shadow: 0 32px 70px -38px rgba(15, 23, 42, 0.45);
  animation: authEnter 520ms ease;
}

.auth-input {
  width: 100%;
  border-radius: 0.9rem;
  border: 1px solid rgba(148, 163, 184, 0.45);
  background: #fff;
  padding: 0.68rem 0.85rem;
  font-size: 0.875rem;
  color: #0f172a;
  transition: border-color 180ms ease, box-shadow 180ms ease;
}

.auth-input:focus {
  outline: none;
  border-color: color-mix(in srgb, var(--p-primary-color) 45%, #94a3b8);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--p-primary-color) 14%, transparent);
}

.auth-primary-btn {
  width: 100%;
  display: inline-flex;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
  border-radius: 0.9rem;
  padding: 0.72rem;
  font-size: 0.875rem;
  font-weight: 700;
  color: #fff;
  transition: transform 160ms ease, box-shadow 160ms ease, opacity 160ms ease;
}

.auth-primary-btn:hover {
  transform: translateY(-1px);
}

.auth-primary-btn:disabled {
  opacity: 0.62;
}

.auth-glow {
  position: absolute;
  border-radius: 9999px;
  filter: blur(85px);
  pointer-events: none;
}

.auth-glow-a {
  height: 300px;
  width: 300px;
  top: 10%;
  right: -80px;
  background: rgba(79, 70, 229, 0.2);
}

.auth-glow-b {
  height: 280px;
  width: 280px;
  bottom: 5%;
  left: -90px;
  background: rgba(14, 165, 233, 0.18);
}

@keyframes authEnter {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
