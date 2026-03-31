<template>
  <div
    :class="cn(
      'rounded-2xl border p-6 transition-all duration-200',
      'bg-[color:var(--aq-surface-card)] border-[color:var(--aq-border)]',
      'hover:shadow-lg hover:shadow-primary/5',
      props.class
    )"
    :style="{
      backgroundColor: 'color-mix(in srgb, var(--aq-surface-card) 88%, var(--aq-primary) 12%)',
      borderColor: 'color-mix(in srgb, var(--aq-border) 84%, var(--aq-primary) 16%)'
    }"
  >
    <div v-if="$slots.header || props.title" class="mb-4 flex items-center justify-between">
      <slot name="header">
        <h3 v-if="props.title" class="font-display text-lg font-semibold tracking-tight text-[color:var(--aq-fg)]">
          {{ props.title }}
        </h3>
      </slot>
    </div>
    <slot />
  </div>
</template>

<script setup>
import { cn } from '../../lib/cn';

const props = defineProps({
  title: { type: String, default: '' },
  class: { type: String, default: '' },
});
</script>
