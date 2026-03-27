<template>
  <div
    class="flex flex-col gap-1.5 rounded-2xl border p-5 transition-all duration-200 bg-[color:var(--aq-surface-card)] border-[color:var(--aq-border)] hover:translate-y-[-2px] hover:shadow-md"
  >
    <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.2em] text-[color:var(--aq-muted)]">
      <component :is="props.icon" v-if="props.icon" class="w-3.5 h-3.5" />
      <span>{{ props.label }}</span>
    </div>
    <div class="flex items-baseline gap-2">
      <span class="text-2xl font-bold tracking-tight text-[color:var(--aq-fg)]">{{ props.value }}</span>
      <span v-if="props.trend" :class="cn('text-xs font-bold', props.trend > 0 ? 'text-emerald-500' : 'text-rose-500')">
        {{ props.trend > 0 ? '+' : '' }}{{ props.trend }}%
      </span>
    </div>
  </div>
</template>

<script setup>
import { cn } from '../../lib/cn';

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], required: true },
  icon: { type: Object, default: null },
  trend: { type: Number, default: null },
});
</script>
