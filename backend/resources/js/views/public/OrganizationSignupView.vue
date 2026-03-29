<template>
  <div class="min-h-screen bg-[var(--app-bg)] text-[var(--app-fg)] selection:bg-purple-500/30 relative overflow-hidden">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.06),transparent_55%)] pointer-events-none" />

    <header class="relative z-10 max-w-6xl mx-auto px-6 py-8 flex items-center justify-between">
      <RouterLink to="/" class="flex items-center gap-3">
        <div class="w-10 h-10 bg-gradient-to-br from-primary to-accent-blue rounded-xl flex items-center justify-center shadow-lg shadow-primary/20">
          <span class="material-symbols-outlined text-white text-xl">health_metrics</span>
        </div>
        <span class="font-display text-xl tracking-tight">AgencHQ</span>
      </RouterLink>

      <RouterLink
        to="/login"
        class="rounded-full border px-5 py-2 text-sm font-semibold transition"
        :class="loginLinkClass"
      >
        Log in
      </RouterLink>
    </header>

    <main class="relative z-10 max-w-6xl mx-auto px-6 pb-16">
      <div class="max-w-xl">
        <div class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-xs font-bold uppercase tracking-widest" :class="kickerClass">
          Self-serve signup
        </div>

        <h1 class="mt-6 text-4xl sm:text-5xl font-display font-bold tracking-tight leading-[1.08]">
          Create your organization
        </h1>

        <p class="mt-4 text-sm sm:text-base" :class="bodyClass">
          Create your AgencHQ tenant and receive login details for your first admin account.
        </p>

        <form class="mt-10 glass-dark p-8 rounded-[28px] border border-white/5 space-y-4" @submit.prevent="submit">
          <div v-if="success" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-sm font-semibold">
            {{ success }}
            <div v-if="emailSent === true" class="mt-3 text-xs text-emerald-200/90">
              Email sent.
            </div>
            <div v-else-if="emailSent === false" class="mt-3 text-xs text-emerald-200/90">
              Email not sent. Use test login details below.
            </div>
            <div v-if="tempPassword" class="mt-4">
              <button
                type="button"
                class="inline-flex items-center justify-center rounded-2xl bg-white/10 text-white px-4 py-2 text-[11px] font-black hover:bg-white/15 active:bg-white/20 transition border border-white/10"
                @click="showTestModal = true"
              >
                View test login details
              </button>
            </div>
            <div class="mt-4">
              <RouterLink
                to="/login"
                class="inline-flex items-center justify-center rounded-2xl bg-white text-black px-5 py-3 text-xs font-black hover:scale-[1.01] active:scale-[0.99] transition shadow-xl shadow-white/10"
              >
                Continue to login
              </RouterLink>
            </div>
          </div>

          <div v-if="errorMessage" class="p-3 bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-bold rounded-2xl flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">error</span>
            {{ errorMessage }}
          </div>

          <div class="space-y-2">
            <label class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Organization name</label>
            <input
              v-model="organizationName"
              type="text"
              required
              class="w-full rounded-2xl px-5 py-3 text-sm placeholder-slate-600 focus:outline-none transition-all duration-300"
              :class="inputClass"
              :disabled="submitting || Boolean(success)"
              placeholder="Acme Staffing"
            />
          </div>

          <div class="space-y-2">
            <label class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Your name</label>
            <input
              v-model="adminName"
              type="text"
              required
              class="w-full rounded-2xl px-5 py-3 text-sm placeholder-slate-600 focus:outline-none transition-all duration-300"
              :class="inputClass"
              :disabled="submitting || Boolean(success)"
              placeholder="Jane Owner"
            />
          </div>

          <div class="space-y-2">
            <label class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Admin email</label>
            <input
              v-model="adminEmail"
              type="email"
              required
              class="w-full rounded-2xl px-5 py-3 text-sm placeholder-slate-600 focus:outline-none transition-all duration-300"
              :class="inputClass"
              :disabled="submitting || Boolean(success)"
              placeholder="name@organization.com"
            />
          </div>

          <button
            type="submit"
            class="w-full font-black py-3 rounded-xl hover:scale-[1.01] active:scale-[0.99] transition-all duration-300 shadow-xl disabled:opacity-50 disabled:hover:scale-100 flex items-center justify-center gap-2 text-xs border"
            :disabled="submitting || Boolean(success)"
            :style="primaryButtonStyle"
          >
            <template v-if="submitting">
              <span class="w-5 h-5 border-2 border-black/20 border-t-black rounded-full animate-spin" />
            </template>
            <template v-else>
              <span>Create organization</span>
              <span class="material-symbols-outlined text-[18px]">arrow_right_alt</span>
            </template>
          </button>

          <div class="pt-1">
            <div class="flex items-center gap-3 text-[11px] text-slate-500">
              <div class="h-px flex-1 bg-[color:var(--p-surface-border)]" />
              <span>or prefill with</span>
              <div class="h-px flex-1 bg-[color:var(--p-surface-border)]" />
            </div>
            <div ref="googleButtonEl" class="mt-3 flex justify-center" />
            <p v-if="googleMessage" class="mt-2 text-[11px] text-[color:var(--p-text-muted-color)] text-center">{{ googleMessage }}</p>
          </div>

          <div class="pt-2 text-center">
            <span class="text-slate-500 text-xs font-medium">Already have an account? </span>
            <RouterLink to="/login" class="text-white text-xs font-black transition-colors" :style="linkStyle">Sign in</RouterLink>
          </div>
        </form>
      </div>
    </main>

    <div
      v-if="showTestModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-6"
      @click.self="showTestModal = false"
    >
      <div class="w-full max-w-md rounded-[28px] border border-white/10 bg-[color:var(--p-surface-0)] text-[color:var(--p-text-color)] p-6 shadow-2xl">
        <div class="flex items-start justify-between gap-4">
          <div>
            <div class="text-sm font-black">Test login details</div>
            <div class="mt-1 text-xs text-[color:var(--p-text-muted-color)]">
              Use these to sign in if you are still configuring email delivery.
            </div>
          </div>
          <button type="button" class="rounded-xl px-2 py-1 text-xs font-black border border-[color:var(--p-surface-border)]" @click="showTestModal = false">
            Close
          </button>
        </div>

        <div class="mt-5 space-y-3">
          <div class="rounded-2xl border border-[color:var(--p-surface-border)] p-4">
            <div class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest">Email</div>
            <div class="mt-1 text-sm font-black break-all">{{ adminEmail }}</div>
          </div>
          <div class="rounded-2xl border border-[color:var(--p-surface-border)] p-4">
            <div class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest">Temporary password</div>
            <div class="mt-1 text-sm font-black break-all">{{ tempPassword }}</div>
          </div>
        </div>

        <div class="mt-5">
          <RouterLink
            to="/login"
            class="inline-flex w-full items-center justify-center rounded-2xl bg-[color:var(--p-primary-color)] text-white px-5 py-3 text-xs font-black hover:scale-[1.01] active:scale-[0.99] transition"
          >
            Go to login
          </RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';
import { useUiStore } from '../../stores/ui';
import { apiPost } from '../../lib/api';
import { renderGoogleButton } from '../../lib/googleIdentity';

const ui = useUiStore();

const organizationName = ref('');
const adminName = ref('');
const adminEmail = ref('');

const submitting = ref(false);
const success = ref(null);
const tempPassword = ref(null);
const emailSent = ref(null);
const showTestModal = ref(false);
const errorMessage = ref(null);
const googleButtonEl = ref(null);
const googleMessage = ref('');

const isDark = computed(() => ui.theme !== 'light');

const loginLinkClass = computed(() =>
  isDark.value
    ? 'border-white/10 bg-white/5 text-white hover:bg-white/10'
    : 'border-slate-200 bg-white text-slate-900 hover:bg-slate-50'
);

const kickerClass = computed(() =>
  isDark.value
    ? 'border-white/10 bg-white/5 text-slate-200'
    : 'border-slate-200 bg-white text-slate-700'
);

const bodyClass = computed(() => (isDark.value ? 'text-slate-300' : 'text-slate-600'));

const inputClass = computed(() =>
  'bg-[color:var(--p-surface-0)] text-[color:var(--p-text-color)] border-2 border-[color:var(--p-surface-border)] focus:border-[color:var(--p-primary-color)]'
);

const primaryButtonStyle = computed(() => ({
  backgroundColor: 'var(--brand-primary, var(--p-primary-color))',
  borderColor: 'color-mix(in srgb, var(--brand-primary, var(--p-primary-color)) 55%, rgba(15,23,42,0.25))',
  color: '#ffffff',
}));

const linkStyle = computed(() => ({
  color: 'var(--brand-primary, var(--p-primary-color))',
}));

async function submit() {
  if (submitting.value || success.value) return;

  submitting.value = true;
  errorMessage.value = null;

  try {
    const res = await axios.post('/api/public/organizations/signup', {
      organization_name: organizationName.value,
      admin_name: adminName.value,
      admin_email: adminEmail.value,
    });

    success.value = res?.data?.message || 'Organization created.';
    tempPassword.value = res?.data?.meta?.temp_password || null;
    emailSent.value = res?.data?.meta?.email_sent;
  } catch (e) {
    const message = e?.response?.data?.message;
    errorMessage.value = message || 'Signup failed.';
  } finally {
    submitting.value = false;
  }
}

async function handleGooglePrefill(idToken) {
  if (!idToken) return;
  googleMessage.value = '';
  try {
    const res = await apiPost('/google/profile', { id_token: idToken });
    const payload = res?.data ? res.data : res;
    const profile = payload?.profile || {};
    if (profile?.name) {
      adminName.value = profile.name;
    }
    if (profile?.email) {
      adminEmail.value = profile.email;
    }
    googleMessage.value = 'Google profile loaded. Complete organization name and continue.';
  } catch (e) {
    googleMessage.value = e?.response?.data?.message || e?.message || 'Google profile lookup failed.';
  }
}

async function initGoogleButton() {
  const clientId = String(import.meta.env.VITE_GOOGLE_CLIENT_ID || '').trim();
  if (!clientId || !googleButtonEl.value) return;
  try {
    await renderGoogleButton(googleButtonEl.value, clientId, handleGooglePrefill, {
      text: 'continue_with',
      width: 330,
      theme: 'outline',
    });
  } catch (e) {
    googleMessage.value = e?.message || 'Google sign-up is currently unavailable.';
  }
}

onMounted(() => {
  initGoogleButton();
});
</script>
