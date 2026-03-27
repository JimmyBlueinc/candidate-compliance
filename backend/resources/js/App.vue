<template>
  <RouterView v-slot="{ Component }">
    <Transition name="aq-page" mode="out-in">
      <component :is="Component" />
    </Transition>
  </RouterView>
</template>

<script setup>
import { watch } from 'vue';
import { useAuthStore } from './stores/auth';
import { useBrandStore } from './stores/brand';
import { useUiStore } from './stores/ui';

console.log('[APP] BOOTSTRAP START');
console.log('[APP] build version:', document.querySelector('meta[name="build-version"]')?.content);

const auth = useAuthStore();
console.log('[APP] calling auth.initFromStorage');
auth.initFromStorage();
console.log('[APP] auth.initFromStorage done, auth.isAuthenticated:', auth.isAuthenticated);

const brand = useBrandStore();
const ui = useUiStore();

// Initialize brand from storage first for instant display
brand.initFromStorage();

if (auth.isAuthenticated) {
  console.log('[APP] authenticated, calling fetchUser');
  auth.fetchUser().then(() => {
    console.log('[APP] fetchUser done, loading brand');
    brand.load();
  }).catch((e) => {
    console.log('[APP] fetchUser ERROR:', e);
    brand.load();
  });
} else {
  console.log('[APP] not authenticated, loading brand');
  brand.load();
}

watch(
  () => auth.tenantId,
  (next, prev) => {
    if (next && next !== prev) {
      console.log('[APP] tenantId changed:', { prev, next });
      brand.load();
    }
  }
);

ui.initTheme();

if (auth.isAuthenticated) {
  ui.syncFromServer();
}

console.log('[APP] BOOTSTRAP COMPLETE');
</script>

<style>
.aq-page-enter-active,
.aq-page-leave-active {
  transition: opacity 180ms ease, transform 220ms ease;
}

.aq-page-enter-from,
.aq-page-leave-to {
  opacity: 0;
  transform: translateY(6px);
}
</style>
