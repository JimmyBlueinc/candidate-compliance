<template>
  <div
    :class="cn(
      'app-stat-card relative overflow-hidden',
      'rounded-[var(--radius-xl)] border p-5 transition-all duration-[var(--transition-base)]',
      'bg-[color:var(--aq-surface-card)] border-[color:var(--aq-border)]',
      interactive && 'hover:shadow-[var(--shadow-lg)] hover:border-[color:var(--aq-primary)]/20 hover:-translate-y-0.5 cursor-pointer',
      props.class
    )"
  >
    <!-- Icon & Label -->
    <div class="flex items-center gap-2.5 mb-3">
      <div
        v-if="$slots.icon || icon"
        :class="cn(
          'flex items-center justify-center w-8 h-8 rounded-[var(--radius-lg)]',
          iconBgClass
        )"
      >
        <slot name="icon">
          <component :is="icon" v-if="icon" class="w-4 h-4" :class="iconClass" />
        </slot>
      </div>
      <span class="text-[11px] font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">
        {{ label }}
      </span>
    </div>
    
    <!-- Value -->
    <div class="flex items-baseline gap-2">
      <span class="text-2xl sm:text-3xl font-bold tracking-tight text-[color:var(--aq-fg)]">
        {{ formattedValue }}
      </span>
      <span
        v-if="trend !== null"
        :class="cn(
          'text-sm font-semibold',
          trend > 0 ? 'text-emerald-400' : trend < 0 ? 'text-rose-400' : 'text-[color:var(--aq-muted)]'
        )"
      >
        {{ trend > 0 ? '+' : '' }}{{ trend }}%
      </span>
    </div>
    
    <!-- Secondary Value -->
    <div v-if="secondaryValue" class="mt-1 text-sm text-[color:var(--aq-muted)]">
      {{ secondaryValue }}
    </div>
    
    <!-- Progress Bar -->
    <div v-if="progress !== null" class="mt-4">
      <div class="h-1.5 rounded-full bg-[color:var(--aq-surface-2)] overflow-hidden">
        <div
          class="h-full rounded-full transition-all duration-500"
          :class="progressColorClass"
          :style="{ width: `${Math.min(100, Math.max(0, progress))}%` }"
        />
      </div>
    </div>
    
    <!-- Glow effect -->
    <div
      v-if="interactive"
      class="absolute -right-8 -top-8 w-32 h-32 rounded-full opacity-0 group-hover:opacity-20 transition-opacity duration-500"
      :style="{ background: `radial-gradient(circle, var(--aq-primary), transparent 70%)` }"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { cn } from '../../lib/cn';

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], required: true },
  icon: { type: Object, default: null },
  trend: { type: Number, default: null },
  progress: { type: Number, default: null },
  secondaryValue: { type: String, default: '' },
  interactive: { type: Boolean, default: true },
  format: { type: String, default: 'auto' }, // auto, currency, percent, number
  class: { type: String, default: '' },
});

const formattedValue = computed(() => {
  const v = props.value;
  if (props.format === 'currency') {
    const n = Number(v) || 0;
    return `$${n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
  }
  if (props.format === 'percent') {
    return `${v}%`;
  }
  if (props.format === 'number') {
    return Number(v).toLocaleString();
  }
  return v;
});

const iconBgClass = computed(() => {
  const t = props.trend;
  if (t !== null) {
    if (t > 0) return 'bg-emerald-500/10';
    if (t < 0) return 'bg-rose-500/10';
  }
  return 'bg-[color:var(--aq-primary)]/10';
});

const iconClass = computed(() => {
  const t = props.trend;
  if (t !== null) {
    if (t > 0) return 'text-emerald-400';
    if (t < 0) return 'text-rose-400';
  }
  return 'text-[color:var(--aq-primary)]';
});

const progressColorClass = computed(() => {
  const p = props.progress;
  if (p === null) return '';
  if (p >= 80) return 'bg-emerald-500';
  if (p >= 50) return 'bg-amber-500';
  return 'bg-[color:var(--aq-primary)]';
});
</script>
