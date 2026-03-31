<template>
  <div ref="el"></div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { createElement } from 'react';
import { createRoot } from 'react-dom/client';

const props = defineProps({
  component: { type: [Object, Function], required: true },
  componentProps: { type: Object, default: () => ({}) },
});

const el = ref(null);
let root = null;

function render() {
  if (!root) return;
  root.render(createElement(props.component, props.componentProps));
}

onMounted(() => {
  if (!el.value) return;
  root = createRoot(el.value);
  render();
});

watch(
  () => [props.component, props.componentProps],
  () => {
    render();
  },
  { deep: true }
);

onBeforeUnmount(() => {
  if (root) root.unmount();
  root = null;
});
</script>
