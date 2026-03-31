<template>
  <div class="min-h-screen flex items-center justify-center bg-[var(--app-bg)] text-[var(--app-fg)] px-6">
    <div class="w-full max-w-md glass-dark rounded-[32px] p-8 border border-white/5">
      <div class="mb-6">
        <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Candidate Portal</div>
        <h1 class="font-display text-3xl text-white mt-2">Sign in</h1>
        <p class="text-sm text-[color:var(--p-text-muted-color)] mt-2">
          {{ loginMode === 'password' ? 'Enter your email and password to sign in.' : 'Enter your email to receive a 6-digit login code.' }}
        </p>
      </div>

      <form class="space-y-4" @submit.prevent="submit">
        <div>
          <label class="block text-xs font-bold text-slate-300 mb-2">Email</label>
          <input
            v-model="email"
            type="email"
            autocomplete="email"
            class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white placeholder:text-slate-500 outline-none focus:border-white/20"
            placeholder="you@example.com"
          />
        </div>

        <div v-if="loginMode === 'password'">
          <label class="block text-xs font-bold text-slate-300 mb-2">Password</label>
          <input
            v-model="password"
            type="password"
            autocomplete="current-password"
            class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white placeholder:text-slate-500 outline-none focus:border-white/20"
            placeholder="Enter your password"
          />
        </div>

        <div v-if="step === 'verify' && loginMode === 'code'">
          <label class="block text-xs font-bold text-slate-300 mb-2">6-digit code</label>
          <input
            v-model="code"
            inputmode="numeric"
            maxlength="6"
            class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white placeholder:text-slate-500 outline-none focus:border-white/20 tracking-[0.35em] font-black"
            placeholder="______"
          />
          <div class="mt-2 text-xs text-[color:var(--p-text-muted-color)]">
            Check your email for the login code.
          </div>
        </div>

        <div v-if="error" class="text-sm text-red-400">{{ error }}</div>

        <button
          type="submit"
          class="w-full py-3 rounded-2xl text-xs font-black tracking-widest uppercase border transition-colors"
          :style="buttonStyle"
          :disabled="loading"
        >
          {{ loading ? 'Please wait…' : submitButtonText }}
        </button>

        <button
          v-if="step === 'verify'"
          type="button"
          class="w-full py-3 rounded-2xl bg-white/5 border border-white/5 text-slate-400 text-xs font-bold hover:bg-white/10 hover:text-white transition-all"
          @click="reset"
        >
          Use a different email
        </button>
      </form>

      <div class="mt-6 text-center">
        <button
          type="button"
          class="text-xs text-[color:var(--p-text-muted-color)] hover:text-white transition-colors"
          @click="toggleLoginMode"
        >
          {{ loginMode === 'password' ? 'Sign in with code instead' : 'Sign in with password' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { apiPost } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const brand = useBrandStore();
const auth = useAuthStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const buttonStyle = computed(() => ({
    backgroundColor: primarySoftBg.value,
    borderColor: primarySoftBorder.value,
    color: primaryColor.value,
}));

const loginMode = ref('password'); // 'password' or 'code'
const step = ref('request');
const email = ref('');
const password = ref('');
const code = ref('');
const loading = ref(false);
const error = ref(null);

const submitButtonText = computed(() => {
    if (loading.value) return 'Please wait…';
    if (loginMode.value === 'password') return 'Sign In';
    return step.value === 'request' ? 'Send Code' : 'Verify & Enter';
});

function toggleLoginMode() {
    loginMode.value = loginMode.value === 'password' ? 'code' : 'password';
    step.value = 'request';
    error.value = null;
}

function reset() {
    step.value = 'request';
    code.value = '';
    error.value = null;
}

async function submit() {
    error.value = null;
    loading.value = true;
    try {
        await brand.load();

        if (loginMode.value === 'password') {
            // Password login
            const res = await apiPost('/login', { 
                email: email.value, 
                password: password.value 
            });
            auth.setSession({ token: res?.token, user: res?.user });
            auth.setTenantId(res?.user?.organization_id || null);
            
            // Store temp password if user must change it
            if (res?.user?.must_change_password) {
                auth._tempPassword = password.value;
            }
            
            await router.push({ name: 'portal.dashboard' });
        } else {
            // Code-based login
            if (step.value === 'request') {
                await apiPost('/v1/portal/request-code', { email: email.value });
                step.value = 'verify';
                return;
            }

            const res = await apiPost('/v1/portal/verify-code', { email: email.value, code: code.value });
            auth.setSession({ token: res?.token, user: res?.user });
            auth.setTenantId(res?.user?.organization_id || null);
            await router.push({ name: 'portal.dashboard' });
        }
    } catch (e) {
        error.value = e?.response?.data?.message || 'Login failed.';
    } finally {
        loading.value = false;
    }
}
</script>
