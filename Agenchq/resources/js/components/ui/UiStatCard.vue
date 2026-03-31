<template>
  <div
    :class="cn(
      'ui-stat-card aq-on-tint flex flex-col gap-1.5 rounded-2xl border p-5 transition-all duration-200 hover:translate-y-[-2px] hover:shadow-md',
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
    primary: 'bg-gradient-to-br from-indigo-700 via-indigo-600 to-violet-600 border-indigo-200/45',
    emerald: 'bg-gradient-to-br from-emerald-700 via-emerald-600 to-teal-500 border-emerald-200/45',
    violet: 'bg-gradient-to-br from-violet-700 via-violet-600 to-purple-500 border-violet-200/45',
    cyan: 'bg-gradient-to-br from-cyan-700 via-cyan-600 to-sky-500 border-cyan-200/45',
    rose: 'bg-gradient-to-br from-rose-700 via-rose-600 to-pink-500 border-rose-200/45',
    amber: 'bg-gradient-to-br from-amber-700 via-orange-600 to-amber-500 border-amber-200/45',
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
</script>
