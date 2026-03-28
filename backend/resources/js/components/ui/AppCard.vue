<template>
  <div
    :class="cn(
      'app-card group relative overflow-hidden',
      'rounded-[var(--radius-2xl)] border transition-all duration-[var(--transition-base)]',
      surfaceClasses,
      hoverable && 'hover:shadow-[var(--shadow-premium)] hover:border-[color:var(--aq-primary)]/20 cursor-pointer',
      props.class
    )"
  >
    <!-- Gradient accent line -->
    <div
      v-if="accent"
      :class="cn('absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-transparent to-transparent opacity-80', accentGradientClass)"
    />
    
    <!-- Header slot -->
    <div v-if="$slots.header || title" class="px-6 pt-6 pb-4 flex items-start justify-between gap-4">
      <slot name="header">
        <div>
          <h3 v-if="title" class="font-display text-lg font-semibold tracking-tight text-[color:var(--aq-fg)]">
            {{ title }}
          </h3>
          <p v-if="subtitle" class="mt-1 text-sm text-[color:var(--aq-muted)]">
            {{ subtitle }}
          </p>
        </div>
      </slot>
      <div v-if="$slots.actions" class="shrink-0">
        <slot name="actions" />
      </div>
    </div>
    
    <!-- Content -->
    <div :class="cn(!title && !$slots.header && 'pt-6', 'px-6 pb-6')">
      <slot />
    </div>
    
    <!-- Footer -->
    <div v-if="$slots.footer" class="px-6 pb-6 pt-0">
      <slot name="footer" />
    </div>
    
    <!-- Premium glow effect on hover -->
    <div
      v-if="hoverable"
      class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"
      :style="{
        background: `radial-gradient(600px at 50% 0%, color-mix(in srgb, var(--aq-primary) 8%, transparent), transparent 70%)`
      }"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { cn } from '../../lib/cn';

const props = defineProps({
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  accent: { type: Boolean, default: false },
  accentColor: { type: String, default: 'primary' }, // primary, emerald, violet, cyan, rose, amber
  hoverable: { type: Boolean, default: false },
  surface: { type: String, default: 'default' }, // default, elevated, glass
  class: { type: String, default: '' },
});

const surfaceClasses = computed(() => {
  const surfaces = {
    default: 'bg-[color:var(--aq-surface-card)] border-[color:var(--aq-border)]',
    elevated: 'bg-[color:var(--aq-surface-2)] border-[color:var(--aq-border)] shadow-[var(--shadow-lg)]',
    glass: 'bg-[color:var(--aq-surface-card)]/60 backdrop-blur-xl border-[color:var(--aq-border)]',
  };
  return surfaces[props.surface] || surfaces.default;
});

const accentGradientClass = computed(() => {
  const colors = {
    primary: 'via-[color:var(--aq-primary)]',
    emerald: 'via-emerald-500',
    violet: 'via-violet-500',
    cyan: 'via-cyan-500',
    rose: 'via-rose-500',
    amber: 'via-amber-500',
  };
  return colors[props.accentColor] || colors.primary;
});
</script>
