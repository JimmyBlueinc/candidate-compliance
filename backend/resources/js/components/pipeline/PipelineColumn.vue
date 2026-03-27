<template>
  <div class="rounded-2xl border border-white/5 bg-white/[0.02] overflow-hidden flex flex-col h-full">
    <div
      class="px-4 py-3 border-b text-xs font-black tracking-widest uppercase flex items-center justify-between"
      :style="headerStyle"
    >
      <span>{{ label }}</span>
      <span class="opacity-60">({{ count }})</span>
    </div>

    <div class="p-3 space-y-3 flex-1 overflow-y-auto min-h-[200px]">
      <slot></slot>
      <div v-if="count === 0" class="py-12 text-center text-sm text-[color:var(--p-text-muted-color)] italic">
        No candidates
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  id: { type: String, required: true },
  label: { type: String, required: true },
  count: { type: Number, default: 0 },
  active: { type: Boolean, default: false },
  primaryColor: { type: String, default: '' },
  primarySoftBg: { type: String, default: '' },
  primarySoftBorder: { type: String, default: '' }
});

const headerStyle = computed(() => {
  if (props.active) {
    return {
      backgroundColor: props.primarySoftBg,
      borderColor: props.primarySoftBorder,
      color: props.primaryColor,
    };
  }
  return {
    backgroundColor: 'transparent',
    borderColor: 'rgba(255,255,255,0.06)',
    color: 'var(--p-text-muted-color)',
  };
});
</script>
