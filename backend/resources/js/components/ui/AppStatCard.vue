<template>
  <div
    :class="cn(
      'app-stat-card relative overflow-hidden',
      'rounded-[var(--radius-xl)] border p-5 transition-all duration-[var(--transition-base)]',
      cardToneClass,
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
      <span :class="cn('text-[11px] font-semibold uppercase tracking-wider', labelClass)">
        {{ label }}
      </span>
    </div>
    
    <!-- Value -->
    <div class="flex items-baseline gap-2">
      <span :class="cn('text-2xl sm:text-3xl font-bold tracking-tight', valueClass)">
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
  color: { type: String, default: 'primary' }, // primary, emerald, violet, cyan, rose, amber
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

const cardToneClass = computed(() => {
  const toneMap = {
    primary: 'bg-[color:var(--aq-primary)]/32 border-[color:var(--aq-primary)]/55',
    emerald: 'bg-emerald-600/32 border-emerald-300/45',
    violet: 'bg-violet-600/32 border-violet-300/45',
    cyan: 'bg-cyan-600/32 border-cyan-300/45',
    rose: 'bg-rose-600/32 border-rose-300/45',
    amber: 'bg-amber-600/34 border-amber-300/50',
  };
  return toneMap[props.color] || toneMap.primary;
});

const labelClass = computed(() => {
  const toneMap = {
    primary: 'text-white/90',
    emerald: 'text-emerald-100',
    violet: 'text-violet-100',
    cyan: 'text-cyan-100',
    rose: 'text-rose-100',
    amber: 'text-amber-100',
  };
  return toneMap[props.color] || toneMap.primary;
});

const valueClass = computed(() => {
  const toneMap = {
    primary: 'text-white',
    emerald: 'text-white',
    violet: 'text-white',
    cyan: 'text-white',
    rose: 'text-white',
    amber: 'text-white',
  };
  return toneMap[props.color] || toneMap.primary;
});

const iconBgClass = computed(() => {
  const t = props.trend;
  if (t !== null) {
    if (t > 0) return 'bg-emerald-500/15';
    if (t < 0) return 'bg-rose-500/15';
  }
  const colorMap = {
    primary: 'bg-white/20',
    emerald: 'bg-emerald-100/25',
    violet: 'bg-violet-100/25',
    cyan: 'bg-cyan-100/25',
    rose: 'bg-rose-100/25',
    amber: 'bg-amber-100/25',
  };
  return colorMap[props.color] || colorMap.primary;
});

const iconClass = computed(() => {
  const t = props.trend;
  if (t !== null) {
    if (t > 0) return 'text-emerald-400';
    if (t < 0) return 'text-rose-400';
  }
  const colorMap = {
    primary: 'text-white',
    emerald: 'text-emerald-50',
    violet: 'text-violet-50',
    cyan: 'text-cyan-50',
    rose: 'text-rose-50',
    amber: 'text-amber-50',
  };
  return colorMap[props.color] || colorMap.primary;
});

const progressColorClass = computed(() => {
  const p = props.progress;
  if (p === null) return '';
  if (p >= 80) return 'bg-emerald-500';
  if (p >= 50) return 'bg-amber-500';
  return 'bg-[color:var(--aq-primary)]';
});
</script>
