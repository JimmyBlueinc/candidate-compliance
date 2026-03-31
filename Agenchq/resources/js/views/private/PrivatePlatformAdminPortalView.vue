<template>
    <div class="min-h-screen bg-white text-slate-900 selection:bg-emerald-500/20 relative overflow-hidden">
        <div class="relative flex items-center justify-center px-6 py-12 min-h-screen">
            <div class="w-full max-w-[520px] space-y-8 relative z-10">
                <div class="space-y-3 text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl shadow-lg ring-1 ring-black/5 overflow-hidden" :style="logoWrapStyle">
                        <span class="text-white font-black text-[18px] tracking-tight">PA</span>
                    </div>
                    <h1 class="text-3xl font-display font-semibold tracking-tight text-slate-900 leading-tight">Private Platform Admin Portal</h1>
                    <p class="text-slate-600 text-sm font-medium leading-relaxed">Secret-key protected bootstrap/reset for platform admin credentials.</p>
                </div>

                <form class="bg-white p-8 rounded-[32px] space-y-5 shadow-sm border border-slate-200" @submit.prevent="submit">
                    <div v-if="errorMessage" class="p-3 bg-red-500/10 border border-red-500/20 text-red-700 text-xs font-bold rounded-2xl flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">error</span>
                        {{ errorMessage }}
                    </div>

                    <div v-if="successMessage" class="p-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 text-xs font-bold rounded-2xl flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">check_circle</span>
                        {{ successMessage }}
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-slate-700 ml-1">Mode</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button
                                type="button"
                                class="px-4 py-3 rounded-2xl border text-xs font-semibold transition-all"
                                :class="mode === 'upsert' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-200 hover:border-slate-300'"
                                @click="mode = 'upsert'"
                            >
                                Create / Update
                            </button>
                            <button
                                type="button"
                                class="px-4 py-3 rounded-2xl border text-xs font-semibold transition-all"
                                :class="mode === 'resetFirst' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-200 hover:border-slate-300'"
                                @click="mode = 'resetFirst'"
                            >
                                Reset First Admin
                            </button>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-slate-700 ml-1">Secret Key</label>
                        <input
                            v-model="secretKey"
                            type="password"
                            autocomplete="off"
                            required
                            class="w-full rounded-2xl px-5 py-3 text-sm placeholder-slate-400 focus:outline-none transition-all duration-200"
                            :class="inputClass"
                            placeholder="SUPER_ADMIN_SECRET_KEY"
                        />
                    </div>

                    <div v-if="mode === 'upsert'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-xs font-semibold text-slate-700 ml-1">Name</label>
                            <input
                                v-model="name"
                                type="text"
                                required
                                class="w-full rounded-2xl px-5 py-3 text-sm placeholder-slate-400 focus:outline-none transition-all duration-200"
                                :class="inputClass"
                                placeholder="Platform Admin"
                            />
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-semibold text-slate-700 ml-1">Email</label>
                            <input
                                v-model="email"
                                type="email"
                                required
                                class="w-full rounded-2xl px-5 py-3 text-sm placeholder-slate-400 focus:outline-none transition-all duration-200"
                                :class="inputClass"
                                placeholder="platform-admin@yourdomain.com"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-xs font-semibold text-slate-700 ml-1">Password</label>
                            <input
                                v-model="password"
                                type="password"
                                autocomplete="new-password"
                                required
                                class="w-full rounded-2xl px-5 py-3 text-sm placeholder-slate-400 focus:outline-none transition-all duration-200"
                                :class="inputClass"
                                placeholder="••••••••"
                            />
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-semibold text-slate-700 ml-1">Confirm Password</label>
                            <input
                                v-model="passwordConfirmation"
                                type="password"
                                autocomplete="new-password"
                                required
                                class="w-full rounded-2xl px-5 py-3 text-sm placeholder-slate-400 focus:outline-none transition-all duration-200"
                                :class="inputClass"
                                placeholder="••••••••"
                            />
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="isSubmitting || !canSubmit"
                        class="w-full font-semibold py-3 rounded-2xl transition-all duration-200 shadow-sm disabled:opacity-50 flex items-center justify-center gap-2 text-sm"
                        :style="primaryButtonStyle"
                    >
                        <template v-if="isSubmitting">
                            <span class="w-5 h-5 border-2 border-white/35 border-t-white rounded-full animate-spin" />
                            <span>Working...</span>
                        </template>
                        <template v-else>
                            <span>Execute and Go to Login</span>
                            <span class="material-symbols-outlined text-[18px]">arrow_right_alt</span>
                        </template>
                    </button>

                    <div class="text-center">
                        <button type="button" class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors" @click="router.push('/login')">
                            Back to Login
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { apiPost } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';

const router = useRouter();
const brand = useBrandStore();

const mode = ref('upsert');
const secretKey = ref('');
const name = ref('Platform Admin');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');

const isSubmitting = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');

const inputClass = computed(() =>
    'bg-white text-slate-900 border border-slate-200 focus:border-[color:var(--p-primary-color)] focus:ring-4 focus:ring-[color:var(--p-primary-color)]/10'
);

const primaryButtonStyle = computed(() => ({
    background: `linear-gradient(135deg, ${primaryColor.value}, rgba(59,130,246,0.85))`,
    color: 'white',
}));

const logoWrapStyle = computed(() => ({
    background: `linear-gradient(135deg, ${primaryColor.value}, rgba(59,130,246,0.85))`,
}));

const canSubmit = computed(() => {
    if (!secretKey.value) return false;
    if (!password.value || !passwordConfirmation.value) return false;
    if (password.value !== passwordConfirmation.value) return false;
    if (mode.value === 'upsert') {
        if (!name.value || !email.value) return false;
    }
    return true;
});

async function submit() {
    if (!canSubmit.value) return;

    isSubmitting.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    try {
        if (mode.value === 'upsert') {
            const res = await apiPost('/private/platform-admin/upsert', {
                secret_key: secretKey.value,
                name: name.value,
                email: email.value,
                password: password.value,
                password_confirmation: passwordConfirmation.value,
            });
            successMessage.value = `Done. Platform admin: ${res?.user?.email || email.value}`;
        } else {
            const res = await apiPost('/private/platform-admin/reset-first-password', {
                secret_key: secretKey.value,
                password: password.value,
                password_confirmation: passwordConfirmation.value,
            });
            successMessage.value = `Done. Reset password for: ${res?.user?.email || 'platform admin'}`;
        }

        setTimeout(() => router.push('/login'), 800);
    } catch (e) {
        const message = e?.response?.data?.message || e?.message || 'Request failed';
        errorMessage.value = message;
    } finally {
        isSubmitting.value = false;
    }
}
</script>
