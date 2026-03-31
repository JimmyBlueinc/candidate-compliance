<template>
  <div
    :class="cn(
      'app-skeleton animate-pulse rounded-[var(--radius-lg)]',
      'bg-[color:var(--aq-surface-2)]',
      variantClasses,
      props.class
    )"
  />
</template>

<script setup>
import { computed } from 'vue';
import { cn } from '../../lib/cn';

const props = defineProps({
  variant: { 
    type: String, 
    default: 'text' // text, title, avatar, button, card, image
  },
  width: { type: String, default: '' },
  height: { type: String, default: '' },
  class: { type: String, default: '' },
});

const variantClasses = computed(() => {
  const variants = {
    text: 'h-4 w-full',
    title: 'h-6 w-3/4',
    avatar: 'h-10 w-10 rounded-full',
    button: 'h-9 w-24',
    card: 'h-32 w-full',
    image: 'h-48 w-full',
  };
  
  let classes = variants[props.variant] || variants.text;
  
  if (props.width) classes += ` ${props.width}`;
  if (props.height) classes = classes.replace(/h-\d+/, props.height);
  
  return classes;
});
</script>
