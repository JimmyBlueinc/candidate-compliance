<template>
  <span
    :class="cn(
      'app-badge inline-flex items-center gap-1.5 font-medium transition-all duration-[var(--transition-fast)]',
      sizeClasses,
      variantClasses,
      props.class
    )"
  >
    <component v-if="icon && !$slots.default" :is="icon" class="w-3 h-3" />
    <slot />
  </span>
</template>

<script setup>
import { computed } from 'vue';
import { cn } from '../../lib/cn';

const props = defineProps({
  variant: { 
    type: String, 
    default: 'default' // default, primary, success, warning, danger, info, outline, ghost
  },
  size: { 
    type: String, 
    default: 'md' // sm, md, lg
  },
  icon: { type: Object, default: null },
  class: { type: String, default: '' },
});

const sizeClasses = computed(() => {
  const sizes = {
    sm: 'px-2 py-0.5 text-[10px] rounded-[var(--radius-sm)]',
    md: 'px-2.5 py-1 text-xs rounded-[var(--radius-md)]',
    lg: 'px-3 py-1.5 text-sm rounded-[var(--radius-lg)]',
  };
  return sizes[props.size] || sizes.md;
});

const variantClasses = computed(() => {
  const variants = {
    default: 'bg-[color:var(--aq-surface-2)] text-[color:var(--aq-muted)] border border-[color:var(--aq-border)]',
    primary: 'bg-[color:var(--aq-primary)]/10 text-[color:var(--aq-primary)] border border-[color:var(--aq-primary)]/20',
    success: 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
    warning: 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
    danger: 'bg-rose-500/10 text-rose-400 border border-rose-500/20',
    info: 'bg-sky-500/10 text-sky-400 border border-sky-500/20',
    outline: 'bg-transparent text-[color:var(--aq-fg)] border border-[color:var(--aq-border)]',
    ghost: 'bg-transparent text-[color:var(--aq-muted)]',
  };
  return variants[props.variant] || variants.default;
});
</script>
