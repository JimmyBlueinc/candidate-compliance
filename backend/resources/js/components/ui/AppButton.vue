<template>
  <button
    :type="nativeType"
    :disabled="disabled || loading"
    :class="cn(
      'app-button inline-flex items-center justify-center gap-2 font-semibold transition-all duration-[var(--transition-fast)]',
      'focus:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--aq-primary)]/50 focus-visible:ring-offset-2 focus-visible:ring-offset-[color:var(--aq-bg)]',
      'disabled:opacity-50 disabled:cursor-not-allowed',
      sizeClasses,
      variantClasses,
      props.class
    )"
  >
    <!-- Loading spinner -->
    <svg
      v-if="loading"
      class="animate-spin w-4 h-4"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
    </svg>
    
    <!-- Left icon -->
    <component v-if="icon && !loading && !iconRight" :is="icon" :class="iconSizeClass" />
    
    <!-- Content -->
    <slot>{{ label }}</slot>
    
    <!-- Right icon -->
    <component v-if="icon && !loading && iconRight" :is="icon" :class="iconSizeClass" />
  </button>
</template>

<script setup>
import { computed } from 'vue';
import { cn } from '../../lib/cn';

const props = defineProps({
  label: { type: String, default: '' },
  icon: { type: Object, default: null },
  iconRight: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  nativeType: { type: String, default: 'button' },
  variant: { 
    type: String, 
    default: 'primary' // primary, secondary, ghost, danger, success
  },
  size: { 
    type: String, 
    default: 'md' // sm, md, lg
  },
  class: { type: String, default: '' },
});

const sizeClasses = computed(() => {
  const sizes = {
    sm: 'px-3 py-1.5 text-xs rounded-[var(--radius-md)]',
    md: 'px-4 py-2 text-sm rounded-[var(--radius-lg)]',
    lg: 'px-5 py-2.5 text-base rounded-[var(--radius-xl)]',
  };
  return sizes[props.size] || sizes.md;
});

const variantClasses = computed(() => {
  const variants = {
    primary: cn(
      'bg-[color:var(--aq-primary)] text-white',
      'hover:bg-[color:var(--aq-primary)]/90',
      'shadow-sm hover:shadow-md'
    ),
    secondary: cn(
      'bg-[color:var(--aq-surface-2)] text-[color:var(--aq-fg)] border border-[color:var(--aq-border)]',
      'hover:bg-[color:var(--aq-surface-card)] hover:border-[color:var(--aq-primary)]/30'
    ),
    ghost: cn(
      'bg-transparent text-[color:var(--aq-muted)]',
      'hover:bg-[color:var(--aq-surface-2)] hover:text-[color:var(--aq-fg)]'
    ),
    danger: cn(
      'bg-rose-500/10 text-rose-400 border border-rose-500/20',
      'hover:bg-rose-500/20 hover:border-rose-500/30'
    ),
    success: cn(
      'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
      'hover:bg-emerald-500/20 hover:border-emerald-500/30'
    ),
  };
  return variants[props.variant] || variants.primary;
});

const iconSizeClass = computed(() => {
  const sizes = { sm: 'w-3.5 h-3.5', md: 'w-4 h-4', lg: 'w-5 h-5' };
  return sizes[props.size] || sizes.md;
});
</script>
