<template>
  <div class="min-h-screen bg-[#050507] text-white flex items-center justify-center px-6 py-12 selection:bg-purple-500/30 relative overflow-hidden">
    <div class="orb absolute w-[300px] h-[300px] bg-purple-900/10 top-[-150px] left-[-150px] rounded-full blur-[80px] pointer-events-none" />
    <div class="orb absolute w-[400px] h-[400px] bg-blue-900/10 bottom-[-200px] right-[-200px] rounded-full blur-[80px] pointer-events-none" />

    <div class="w-full max-w-md space-y-8 relative z-10 animate-[fadeInUp_0.7s_ease-out_both]">
      <div class="text-center space-y-4">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-white/5 rounded-2xl border border-white/10 mb-2">
          <span class="material-symbols-outlined text-primary text-[32px]">shield</span>
        </div>
        <h1 class="serif text-3xl font-bold tracking-tight">Set New Password</h1>
        <p class="text-gray-400 text-sm">Please enter your new secure password below.</p>
      </div>

      <form class="glass-dark p-8 rounded-[32px] space-y-6 border border-white/10" @submit.prevent="handleSubmit">
        <div v-if="error" class="p-4 bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-xl">
          {{ error }}
        </div>
        <div v-if="success" class="p-4 bg-green-500/10 border border-green-500/20 text-green-400 text-sm rounded-xl">
          {{ success }}
        </div>

        <div class="space-y-4">
          <div class="space-y-2">
            <label class="text-sm font-medium text-gray-300 ml-1">New Password</label>
            <div class="relative">
              <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-gray-600 text-[18px]">key</span>
              <input
                v-model="password"
                type="password"
                required
                autocomplete="new-password"
                class="w-full bg-white/5 border border-white/10 rounded-2xl pl-12 pr-5 py-4 text-white placeholder:text-gray-600 focus:outline-none focus:border-purple-500/50 focus:ring-1 focus:ring-purple-500/50 transition-all"
                placeholder="••••••••"
              />
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium text-gray-300 ml-1">Confirm Password</label>
            <div class="relative">
              <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-gray-600 text-[18px]">key</span>
              <input
                v-model="passwordConfirmation"
                type="password"
                required
                autocomplete="new-password"
                class="w-full bg-white/5 border border-white/10 rounded-2xl pl-12 pr-5 py-4 text-white placeholder:text-gray-600 focus:outline-none focus:border-purple-500/50 focus:ring-1 focus:ring-purple-500/50 transition-all"
                placeholder="••••••••"
              />
            </div>
          </div>
        </div>

        <button
          type="submit"
          :disabled="loading || (Boolean(error) && !success)"
          class="w-full bg-white text-black font-bold py-4 rounded-2xl hover:scale-[1.02] active:scale-[0.98] transition-all shadow-xl shadow-white/5 disabled:opacity-50 disabled:hover:scale-100 flex items-center justify-center gap-2"
        >
          <span v-if="loading" class="w-4 h-4 border-2 border-black/20 border-t-black rounded-full animate-spin" />
          Reset Password
        </button>

        <button
          type="button"
          class="w-full flex items-center justify-center gap-2 text-sm text-gray-500 hover:text-white transition-colors"
          @click="router.push('/login')"
        >
          <span class="material-symbols-outlined text-[16px]">arrow_back</span>
          Back to Login
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { apiPost } from '../../lib/api';

const router = useRouter();
const route = useRoute();

const token = ref(String(route.query.token || ''));
const email = ref(String(route.query.email || ''));
const password = ref('');
const passwordConfirmation = ref('');

const loading = ref(false);
const error = ref('');
const success = ref('');

onMounted(() => {
    if (!token.value || !email.value) {
        error.value = 'Invalid or expired reset link. Please request a new one.';
    }
});

async function handleSubmit() {
    if (password.value !== passwordConfirmation.value) {
        error.value = 'Passwords do not match';
        return;
    }

    loading.value = true;
    error.value = '';
    success.value = '';

    try {
        await apiPost('/reset-password', {
            token: token.value,
            email: email.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        });
        success.value = 'Password reset successful! You can now log in.';
        setTimeout(() => router.push('/login'), 3000);
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Failed to reset password';
    } finally {
        loading.value = false;
    }
}
</script>
