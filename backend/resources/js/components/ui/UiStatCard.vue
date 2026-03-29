<template>
  <div
    :class="cn(
      'flex flex-col gap-1.5 rounded-2xl border p-5 transition-all duration-200 hover:translate-y-[-2px] hover:shadow-md',
      cardToneClass
    )"
  >
    <div :class="cn('flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.2em]', labelClass)">
      <component :is="props.icon" v-if="props.icon" class="w-3.5 h-3.5" />
      <span>{{ props.label }}</span>
    </div>
    <div class="flex items-baseline gap-2">
      <span :class="cn('text-2xl font-bold tracking-tight', valueClass)">{{ props.value }}</span>
      <span v-if="props.trend" :class="cn('text-xs font-bold', props.trend > 0 ? 'text-emerald-500' : 'text-rose-500')">
        {{ props.trend > 0 ? '+' : '' }}{{ props.trend }}%
      </span>
    </div>
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
  color: { type: String, default: 'primary' }, // primary, emerald, violet, cyan, rose, amber
});

const cardToneClass = computed(() => {
  const toneMap = {
    primary: 'bg-[color:var(--aq-primary)]/14 border-[color:var(--aq-primary)]/35',
    emerald: 'bg-emerald-500/14 border-emerald-400/35',
    violet: 'bg-violet-500/14 border-violet-400/35',
    cyan: 'bg-cyan-500/14 border-cyan-400/35',
    rose: 'bg-rose-500/14 border-rose-400/35',
    amber: 'bg-amber-500/14 border-amber-400/35',
  };
  return toneMap[props.color] || toneMap.primary;
});

const labelClass = computed(() => {
  const toneMap = {
    primary: 'text-[color:var(--aq-primary)]',
    emerald: 'text-emerald-300',
    violet: 'text-violet-300',
    cyan: 'text-cyan-300',
    rose: 'text-rose-300',
    amber: 'text-amber-300',
  };
  return toneMap[props.color] || toneMap.primary;
});

const valueClass = computed(() => {
  const toneMap = {
    primary: 'text-[color:var(--aq-fg)]',
    emerald: 'text-emerald-50',
    violet: 'text-violet-50',
    cyan: 'text-cyan-50',
    rose: 'text-rose-50',
    amber: 'text-amber-50',
  };
  return toneMap[props.color] || toneMap.primary;
});
</script>
