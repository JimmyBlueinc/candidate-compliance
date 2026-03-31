<template>
  <div v-if="msg" class="w-full border-b border-white/10" :style="bannerStyle">
    <div class="max-w-6xl mx-auto px-6 sm:px-10 py-3 flex items-start justify-between gap-4">
      <div class="text-sm font-semibold text-white">
        {{ msg.message }}
      </div>
      <button
        type="button"
        class="shrink-0 text-xs font-bold border border-white/15 bg-white/5 hover:bg-white/10 text-white px-3 py-1.5 rounded-full"
        @click="dismiss"
      >
        Dismiss
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { apiGet } from '../lib/api';
import { useBrandStore } from '../stores/brand';

const brand = useBrandStore();

const msg = ref(null);
let timer = null;

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const bannerStyle = computed(() => ({
    backgroundColor: `color-mix(in srgb, ${primaryColor.value} 18%, rgba(0,0,0,0.35))`,
}));

function dismiss() {
    msg.value = null;
}

async function refresh() {
    try {
        const res = await apiGet('/v1/system/banner');
        msg.value = res?.data || null;
    } catch {
        // ignore
    }
}

onMounted(async () => {
    await refresh();
    timer = window.setInterval(refresh, 15000);
});

onUnmounted(() => {
    if (timer) window.clearInterval(timer);
});
</script>
