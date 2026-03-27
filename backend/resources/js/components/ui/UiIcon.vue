<template>
  <component
    v-if="props.icon"
    :is="props.icon"
    v-bind="$attrs"
  />
  <span
    v-else
    class="material-symbols-outlined"
    v-bind="$attrs"
  >{{ props.fallback }}</span>
</template>

<script setup>
defineOptions({ inheritAttrs: false });

const props = defineProps({
  icon: { type: [Object, Function], default: null },
  fallback: { type: String, default: '' },
});
</script>
