<template>
  <div class="space-y-6">
    <div class="glass-dark rounded-3xl border border-white/5 p-6">
      <h2 class="font-display text-2xl text-white">{{ title }}</h2>
      <p class="text-slate-500 text-sm">This page hasn’t been migrated to Vue yet.</p>
    </div>
  </div>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
});
</script>
