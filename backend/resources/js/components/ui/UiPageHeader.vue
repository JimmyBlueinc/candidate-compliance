<template>
  <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div v-motion :initial="{ opacity: 0, x: -10 }" :enter="{ opacity: 1, x: 0 }">
      <h1 class="font-display text-3xl font-bold tracking-tight text-[color:var(--aq-fg)]">
        {{ props.title }}
      </h1>
      <p v-if="props.subtitle" class="mt-1 text-sm text-[color:var(--aq-muted)]">
        {{ props.subtitle }}
      </p>
    </div>
    <div 
      v-if="$slots.actions" 
      v-motion 
      :initial="{ opacity: 0, x: 10 }" 
      :enter="{ opacity: 1, x: 0 }"
      class="flex items-center gap-2"
    >
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
});
</script>
