<template>
  <div class="auth-shell min-h-screen text-slate-900">
    <div class="grid min-h-screen grid-cols-1 lg:grid-cols-2">
      <section class="auth-image-panel hidden lg:block">
        <img
          :src="heroImage"
          alt="Healthcare clinicians working as a team"
          class="absolute inset-0 h-full w-full object-cover"
          loading="lazy"
          @error="onHeroImageError"
        />
        <div class="absolute inset-0 bg-gradient-to-br from-slate-950/70 via-slate-900/45 to-emerald-900/30" />
        <div class="relative z-10 flex h-full flex-col justify-end p-12 text-white">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-100/90">Candidate Onboarding</p>
          <h2 class="mt-4 max-w-xl text-4xl font-semibold leading-tight tracking-tight">
            Start your healthcare career journey with confidence.
          </h2>
          <p class="mt-4 max-w-lg text-sm leading-relaxed text-slate-200/90">
            Create one profile, access trusted clinical opportunities, and stay connected to your next placement.
          </p>
        </div>
      </section>

      <section class="relative flex items-center justify-center px-6 py-10">
        <div class="auth-glow auth-glow-a" />
        <div class="auth-glow auth-glow-b" />

        <div class="relative z-10 w-full max-w-[480px] space-y-6">
          <div class="space-y-3 text-center lg:text-left">
            <div class="inline-flex h-12 w-12 items-center justify-center overflow-hidden rounded-2xl shadow-lg ring-1 ring-slate-200" :style="logoWrapStyle">
              <img v-if="brand.logoUrl" :src="brand.logoUrl" alt="Logo" class="h-8 w-8 object-contain" />
              <span v-else class="material-symbols-outlined text-[22px] text-white">person_add</span>
            </div>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Create your account</h1>
            <p class="text-sm leading-relaxed text-slate-600">Join <span class="font-semibold text-slate-800">{{ brand.name || 'AgencHQ' }}</span> and unlock curated healthcare opportunities.</p>
          </div>

          <form class="auth-card space-y-5" @submit.prevent="handleSubmit">
            <div v-if="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700">
              {{ errorMessage }}
            </div>

            <div class="space-y-2">
              <label class="text-xs font-semibold text-slate-700">Full name</label>
              <input v-model="name" type="text" required class="auth-input" placeholder="Jane Smith" />
            </div>

            <div class="space-y-2">
              <label class="text-xs font-semibold text-slate-700">Email address</label>
              <input v-model="email" type="email" required autocomplete="email" class="auth-input" placeholder="name@organization.com" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div class="space-y-2">
                <label class="text-xs font-semibold text-slate-700">Password</label>
                <div class="relative">
                  <input
                    v-model="password"
                    :type="showPassword ? 'text' : 'password'"
                    required
                    autocomplete="new-password"
                    class="auth-input pr-11"
                    placeholder="••••••••"
                  />
                  <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700" @click="showPassword = !showPassword">
                    <i :class="showPassword ? 'pi pi-eye-slash' : 'pi pi-eye'" />
                  </button>
                </div>
              </div>

              <div class="space-y-2">
                <label class="text-xs font-semibold text-slate-700">Confirm password</label>
                <div class="relative">
                  <input
                    v-model="passwordConfirmation"
                    :type="showPasswordConfirm ? 'text' : 'password'"
                    required
                    autocomplete="new-password"
                    class="auth-input pr-11"
                    placeholder="••••••••"
                  />
                  <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700" @click="showPasswordConfirm = !showPasswordConfirm">
                    <i :class="showPasswordConfirm ? 'pi pi-eye-slash' : 'pi pi-eye'" />
                  </button>
                </div>
              </div>
            </div>

            <button type="submit" :disabled="isSubmitting" class="auth-primary-btn" :style="primaryButtonStyle">
              <span v-if="isSubmitting" class="h-4 w-4 animate-spin rounded-full border-2 border-white/35 border-t-white" />
              <span v-else>Create account</span>
            </button>

            <div class="pt-1">
              <div class="flex items-center gap-3 text-xs text-slate-400">
                <div class="h-px flex-1 bg-slate-200" />
                <span>or sign up with</span>
                <div class="h-px flex-1 bg-slate-200" />
              </div>
              <div ref="googleButtonEl" class="mt-3 flex justify-center lg:justify-start" />
              <p v-if="googleMessage" class="mt-2 text-[11px] text-slate-500">{{ googleMessage }}</p>
            </div>

            <div class="pt-2 text-center lg:text-left">
              <span class="text-sm text-slate-600">Already registered? </span>
              <button type="button" class="text-sm font-semibold" :style="linkStyle" @click="router.push('/login')">Sign in</button>
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

const router = useRouter();
const auth = useAuthStore();
const brand = useBrandStore();

const name = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const showPassword = ref(false);
const showPasswordConfirm = ref(false);
const googleButtonEl = ref(null);
const googleMessage = ref('');
const heroImage = ref('https://images.unsplash.com/photo-1576671081837-49000212a370?auto=format&fit=crop&w=1800&q=80');

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

function onHeroImageError() {
  heroImage.value = '/images/public/tenant-careers-hero.svg';
}

const errorMessage = computed(() => {
    const err = auth.error;
    if (!err) return null;

    const message = err?.response?.data?.message;
    if (message) return message;

    const fieldErrors = err?.response?.data?.errors;
    if (fieldErrors) {
        const firstKey = Object.keys(fieldErrors)[0];
        if (firstKey && Array.isArray(fieldErrors[firstKey]) && fieldErrors[firstKey].length) {
            return fieldErrors[firstKey][0];
        }
    }

    return 'Registration failed. Please try again.';
});

async function handleSubmit() {
    if (password.value !== passwordConfirmation.value) {
        auth.error = { message: 'Passwords do not match' };
        return;
    }

    await auth.register({
        name: name.value,
        email: email.value,
        password: password.value,
        passwordConfirmation: passwordConfirmation.value,
    });

    await router.push({ name: 'portal.dashboard' });
}

async function handleGoogleCredential(idToken) {
    if (!idToken) return;
    googleMessage.value = '';
    try {
        const response = await apiPost('/google/authenticate', {
            id_token: idToken,
            intent: 'signup',
            tenant_id: auth.tenantId ? Number(auth.tenantId) : null,
            role: 'candidate',
        });
        const payload = response?.data ? response.data : response;
        if (!payload?.token || !payload?.user) {
            throw new Error('Google signup response is missing session data.');
        }
        auth.setSession({ token: payload.token, user: payload.user });
        if (payload.user?.organization_id) {
            auth.setTenantId(String(payload.user.organization_id));
        }
        await router.push({ name: 'portal.dashboard' });
    } catch (e) {
        googleMessage.value = e?.response?.data?.message || e?.message || 'Google sign-up failed.';
    }
}

async function initGoogleButton() {
    const clientId = String(import.meta.env.VITE_GOOGLE_CLIENT_ID || '').trim();
    if (!clientId || !googleButtonEl.value) return;
    try {
        await renderGoogleButton(googleButtonEl.value, clientId, handleGoogleCredential, {
            text: 'signup_with',
            width: 340,
            theme: 'filled_blue',
        });
    } catch (e) {
        googleMessage.value = e?.message || 'Google sign-up is currently unavailable.';
    }
}

onMounted(() => {
    initGoogleButton();
});
</script>

<style scoped>
.auth-shell {
  background:
    radial-gradient(620px 420px at 8% 8%, rgba(79, 70, 229, 0.14), transparent 60%),
    radial-gradient(580px 380px at 90% 82%, rgba(16, 185, 129, 0.12), transparent 60%),
    #f8fafc;
}

.auth-image-panel {
  position: relative;
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
  background: rgba(16, 185, 129, 0.18);
}

@keyframes authEnter {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
