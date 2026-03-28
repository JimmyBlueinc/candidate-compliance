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
import { useFeatureFlagStore } from './stores/featureFlags';

const auth = useAuthStore();
auth.initFromStorage();

const brand = useBrandStore();
const ui = useUiStore();
const featureFlags = useFeatureFlagStore();

// Initialize brand from storage first for instant display
brand.initFromStorage();

if (auth.isAuthenticated) {
  auth.fetchUser().then(() => {
    brand.load();
  }).catch(() => {
    brand.load();
  });
} else {
  brand.load();
}

watch(
  () => auth.tenantId,
  (next, prev) => {
    if (next && next !== prev) {
      brand.load();
    }
  }
);

ui.initTheme();

if (auth.isAuthenticated) {
  ui.syncFromServer();
  featureFlags.load();
}
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
