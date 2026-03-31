<template>
  <span
    :class="cn(
      'inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest border transition-colors',
      variantClasses[props.variant],
      props.class
    )"
  >
    <slot />
  </span>
</template>

<script setup>
import { cn } from '../../lib/cn';

const props = defineProps({
  variant: { type: String, default: 'default' },
  class: { type: String, default: '' },
});

const variantClasses = {
  default: 'bg-[color:var(--aq-surface-2)] border-[color:var(--aq-border)] text-[color:var(--aq-muted)]',
  primary: 'bg-primary/10 border-primary/20 text-primary',
  success: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-500',
  warning: 'bg-amber-500/10 border-amber-500/20 text-amber-500',
  danger: 'bg-rose-500/10 border-rose-500/20 text-rose-500',
  outline: 'bg-transparent border-[color:var(--aq-border)] text-[color:var(--aq-muted)]',
};
</script>
