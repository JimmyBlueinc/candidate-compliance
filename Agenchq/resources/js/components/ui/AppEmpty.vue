<template>
  <div
    :class="cn(
      'app-empty flex flex-col items-center justify-center text-center p-8',
      compact ? 'py-8' : 'py-16',
      props.class
    )"
  >
    <!-- Icon -->
    <div
      :class="cn(
        'flex items-center justify-center rounded-full mb-4',
        compact ? 'w-12 h-12' : 'w-16 h-16',
        'bg-[color:var(--aq-surface-2)]'
      )"
    >
      <component 
        :is="icon" 
        :class="cn('text-[color:var(--aq-muted)]', compact ? 'w-6 h-6' : 'w-8 h-8')" 
      />
    </div>
    
    <!-- Title -->
    <h3 class="font-display font-semibold text-[color:var(--aq-fg)] mb-2">
      {{ title }}
    </h3>
    
    <!-- Description -->
    <p v-if="description" class="text-sm text-[color:var(--aq-muted)] max-w-sm mb-6">
      {{ description }}
    </p>
    
    <!-- Action -->
    <slot name="action" />
  </div>
</template>

<script setup>
import { Inbox } from 'lucide-vue-next';
import { cn } from '../../lib/cn';

const props = defineProps({
  title: { type: String, default: 'No data yet' },
  description: { type: String, default: '' },
  icon: { type: Object, default: () => Inbox },
  compact: { type: Boolean, default: false },
  class: { type: String, default: '' },
});
</script>
