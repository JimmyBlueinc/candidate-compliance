<template>
  <div class="min-h-screen bg-[var(--app-bg)] text-[var(--app-fg)] selection:bg-purple-500/30 relative overflow-hidden">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.06),transparent_55%)] pointer-events-none" />

    <div class="grid grid-cols-1 lg:grid-cols-2 min-h-screen">
      <div class="relative hidden lg:block">
        <div class="absolute inset-0" :style="imagePanelStyle" />
        <div class="absolute inset-0" :style="imageOverlayStyle" />

        <div class="relative z-10 h-full p-12 flex flex-col justify-end">
          <div class="max-w-md">
            <div class="text-xs font-black tracking-widest uppercase" :class="heroKickerClass">Secure Onboarding</div>
            <div class="mt-3 text-4xl font-display leading-tight" :class="heroTitleClass">
              Create your account,
              <span :style="{ color: primaryColor }">then</span> continue into your workspace.
            </div>
            <div class="mt-4 text-sm" :class="heroBodyClass">
              Organization-aware signup flows for staffing teams and candidates.
            </div>
          </div>
        </div>
      </div>

      <div class="relative flex items-center justify-center px-6 py-12">
        <div
          class="absolute w-[520px] h-[520px] top-[-220px] right-[-180px] rounded-full blur-[140px] animate-pulse pointer-events-none"
          :style="{ backgroundColor: primaryGlowA }"
        />
        <div
          class="absolute w-[620px] h-[620px] bottom-[-260px] left-[-220px] rounded-full blur-[160px] animate-pulse pointer-events-none"
          :style="{ backgroundColor: primaryGlowB, animationDelay: '1s' }"
        />

        <div class="w-full max-w-[460px] space-y-8 relative z-10 animate-[fadeInUp_0.7s_ease-out_both]">
          <div class="text-center space-y-3">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-[18px] shadow-2xl ring-1 ring-white/20 overflow-hidden" :style="logoWrapStyle">
              <img v-if="brand.logoUrl" :src="brand.logoUrl" alt="Logo" class="h-9 w-9 object-contain" />
              <span v-else class="material-symbols-outlined text-white text-[28px]">person_add</span>
            </div>
            <h1 class="text-3xl font-display font-bold tracking-tight text-[color:var(--p-text-color)] leading-tight">Create Account</h1>
            <p class="text-[color:var(--p-text-muted-color)] text-xs font-medium max-w-[340px] mx-auto leading-relaxed">
              Join <span class="text-[color:var(--p-text-color)] font-semibold">{{ brand.name || 'AgencyHQ' }}</span>.
            </p>
          </div>

          <form class="glass-dark p-8 rounded-[32px] space-y-5 shadow-2xl relative" @submit.prevent="handleSubmit">
            <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 px-3 py-1 bg-[color:var(--p-surface-0)] border border-[color:var(--p-surface-border)] rounded-full">
              <span class="text-[10px] font-black uppercase tracking-[0.2em] text-[color:var(--p-text-muted-color)]">Secure Enrollment</span>
            </div>

            <div v-if="errorMessage" class="p-3 bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-bold rounded-2xl flex items-center gap-2">
              <span class="material-symbols-outlined text-sm">error</span>
              {{ errorMessage }}
            </div>

            <div class="space-y-5">
              <div class="space-y-2">
                <label class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Full Name</label>
                <div class="relative group">
                  <div class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 transition-colors" :style="iconActiveStyle">
                    <span class="material-symbols-outlined text-[18px]">person</span>
                  </div>
                  <input
                    v-model="name"
                    type="text"
                    required
                    class="w-full rounded-2xl pl-12 pr-5 py-3 text-sm placeholder-slate-600 focus:outline-none transition-all duration-300"
                    :class="inputClass"
                    :style="inputStyle"
                    placeholder="John Doe"
                  />
                </div>
              </div>

              <div class="space-y-2">
                <label class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Email Address</label>
                <div class="relative group">
                  <div class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 transition-colors" :style="iconActiveStyle">
                    <span class="material-symbols-outlined text-[18px]">mail</span>
                  </div>
                  <input
                    v-model="email"
                    type="email"
                    required
                    autocomplete="email"
                    class="w-full rounded-2xl pl-12 pr-5 py-3 text-sm placeholder-slate-600 focus:outline-none transition-all duration-300"
                    :class="inputClass"
                    :style="inputStyle"
                    placeholder="name@organization.com"
                  />
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <label class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Password</label>
                  <div class="relative group">
                    <div class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 transition-colors" :style="iconActiveStyle">
                      <span class="material-symbols-outlined text-[18px]">lock</span>
                    </div>
                    <input
                      v-model="password"
                      :type="showPassword ? 'text' : 'password'"
                      required
                      autocomplete="new-password"
                      class="w-full rounded-2xl pl-12 pr-12 py-3 text-sm placeholder-slate-600 focus:outline-none transition-all duration-300"
                      :class="inputClass"
                      :style="inputStyle"
                      placeholder="••••••••"
                    />
                    <button
                      type="button"
                      class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-[color:var(--p-text-color)] transition-colors"
                      :aria-label="showPassword ? 'Hide password' : 'Show password'"
                      @click="showPassword = !showPassword"
                    >
                      <span class="material-symbols-outlined text-[18px]">{{ showPassword ? 'visibility_off' : 'visibility' }}</span>
                    </button>
                  </div>
                </div>

                <div class="space-y-2">
                  <label class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Confirm</label>
                  <div class="relative group">
                    <div class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 transition-colors" :style="iconActiveStyle">
                      <span class="material-symbols-outlined text-[18px]">lock</span>
                    </div>
                    <input
                      v-model="passwordConfirmation"
                      :type="showPasswordConfirm ? 'text' : 'password'"
                      required
                      autocomplete="new-password"
                      class="w-full rounded-2xl pl-12 pr-12 py-3 text-sm placeholder-slate-600 focus:outline-none transition-all duration-300"
                      :class="inputClass"
                      :style="inputStyle"
                      placeholder="••••••••"
                    />
                    <button
                      type="button"
                      class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-[color:var(--p-text-color)] transition-colors"
                      :aria-label="showPasswordConfirm ? 'Hide password' : 'Show password'"
                      @click="showPasswordConfirm = !showPasswordConfirm"
                    >
                      <span class="material-symbols-outlined text-[18px]">{{ showPasswordConfirm ? 'visibility_off' : 'visibility' }}</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <button
              type="submit"
              :disabled="isSubmitting"
              class="w-full font-black py-2.5 rounded-xl hover:scale-[1.01] active:scale-[0.99] transition-all duration-300 shadow-xl disabled:opacity-50 disabled:hover:scale-100 flex items-center justify-center gap-2 text-xs border"
              :class="primaryButtonTextClass"
              :style="primaryButtonStyle"
            >
              <template v-if="isSubmitting">
                <span class="w-5 h-5 border-2 border-black/20 border-t-black rounded-full animate-spin" />
              </template>
              <template v-else>
                <span>Create Account</span>
                <span class="material-symbols-outlined text-[18px]">arrow_right_alt</span>
              </template>
            </button>

            <div class="pt-2">
              <div class="flex items-center gap-3 text-[11px] text-slate-500">
                <div class="h-px flex-1 bg-[color:var(--p-surface-border)]" />
                <span>or sign up with</span>
                <div class="h-px flex-1 bg-[color:var(--p-surface-border)]" />
              </div>
              <div ref="googleButtonEl" class="mt-3 flex justify-center" />
              <p v-if="googleMessage" class="mt-2 text-[11px] text-center text-[color:var(--p-text-muted-color)]">{{ googleMessage }}</p>
            </div>

            <div class="pt-4 text-center">
              <span class="text-slate-500 text-xs font-medium">Already registered? </span>
              <button type="button" class="text-white text-xs font-black transition-colors" :style="linkStyle" @click="router.push('/login')">
                Sign In
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useBrandStore } from '../../stores/brand';
import { useUiStore } from '../../stores/ui';
import { apiPost } from '../../lib/api';
import { renderGoogleButton } from '../../lib/googleIdentity';

const router = useRouter();
const auth = useAuthStore();
const brand = useBrandStore();
const ui = useUiStore();

const name = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const showPassword = ref(false);
const showPasswordConfirm = ref(false);
const googleButtonEl = ref(null);
const googleMessage = ref('');

const isSubmitting = computed(() => auth.status === 'loading');

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primaryGlowA = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primaryGlowB = computed(() => `color-mix(in srgb, ${primaryColor.value} 10%, transparent)`);

const inputClass = computed(() =>
    'bg-[color:var(--p-surface-0)] text-[color:var(--p-text-color)] border-2 border-[color:var(--p-surface-border)] focus:border-[color:var(--p-primary-color)]'
);

const inputStyle = computed(() => ({
    boxShadow: ui.theme === 'light'
        ? '0 1px 0 rgba(15, 23, 42, 0.02)'
        : '0 1px 0 rgba(255, 255, 255, 0.02)',
}));

const imageOverlayStyle = computed(() => {
    const overlay = ui.theme === 'light'
        ? 'linear-gradient(to right, rgba(2,6,23,0.78), rgba(2,6,23,0.45), rgba(2,6,23,0.10))'
        : 'linear-gradient(to right, rgba(0,0,0,0.72), rgba(0,0,0,0.42), rgba(0,0,0,0.12))';

    return {
        backgroundImage: overlay,
    };
});

const heroKickerClass = computed(() => 'text-white/85');
const heroTitleClass = computed(() => 'text-white');
const heroBodyClass = computed(() => 'text-white/80');

const imagePanelStyle = computed(() => ({
    backgroundImage: "url('/login-nursing-office.jpg')",
    backgroundSize: 'cover',
    backgroundPosition: 'center',
}));

const logoWrapStyle = computed(() => ({
    background: `linear-gradient(135deg, ${primaryColor.value}, rgba(59,130,246,0.85))`,
    boxShadow: `0 25px 60px -20px color-mix(in srgb, ${primaryColor.value} 45%, transparent)`,
}));

const iconActiveStyle = computed(() => ({
    '--focus-color': primaryColor.value,
}));

const primaryButtonStyle = computed(() => ({
    backgroundColor: primaryColor.value,
    borderColor: `color-mix(in srgb, ${primaryColor.value} 55%, rgba(15,23,42,0.25))`,
    color: '#ffffff',
    boxShadow: `0 24px 45px -20px color-mix(in srgb, ${primaryColor.value} 35%, transparent)`,
}));

const primaryButtonTextClass = computed(() => 'text-white');

const linkStyle = computed(() => ({
    color: primaryColor.value,
}));

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

    await router.push('/dashboard');
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
        await router.push('/dashboard');
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
