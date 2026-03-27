<template>
  <div class="min-h-screen bg-gray-50 text-gray-900">
    <header class="border-b bg-white">
      <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3">
          <div class="flex h-9 w-9 items-center justify-center rounded bg-[var(--brand-primary)] text-white">
            <img
              v-if="branding.logoUrl"
              :src="branding.logoUrl"
              alt="Logo"
              class="h-7 w-7 object-contain"
            />
            <span v-else class="text-sm font-semibold">A</span>
          </div>
          <div class="leading-tight">
            <div class="text-sm font-semibold">
              {{ branding.appName }}
            </div>
            <div class="text-xs text-gray-500">
              {{ branding.tagline }}
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <div v-if="auth.user" class="text-sm text-gray-700">
            {{ auth.user.name }}
          </div>
          <button
            v-if="auth.isAuthenticated"
            type="button"
            class="rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:bg-gray-800"
            @click="handleLogout"
          >
            Logout
          </button>
        </div>
      </div>
    </header>

    <main class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();

const branding = computed(() => {
    return {
        appName: 'AgencyHQ',
        tagline: 'Candidate Compliance',
        logoUrl: null,
        primaryColor: '#0f172a',
        secondaryColor: '#334155',
    };
});

function applyBrandCssVars() {
    document.documentElement.style.setProperty('--brand-primary', branding.value.primaryColor);
    document.documentElement.style.setProperty('--brand-secondary', branding.value.secondaryColor);
}

async function handleLogout() {
    await auth.logout();
}

onMounted(() => {
    applyBrandCssVars();
});
</script>

<style scoped>
:global(:root) {
  --brand-primary: #0f172a;
  --brand-secondary: #334155;
}
</style>
