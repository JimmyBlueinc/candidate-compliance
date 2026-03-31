<template>
  <Teleport to="body">
    <Transition name="app-modal">
      <div
        v-if="modelValue"
        class="app-modal-overlay fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="closeOnBackdrop && close()"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" />
        
        <!-- Modal -->
        <div
          :class="cn(
            'app-modal relative w-full overflow-hidden',
            'bg-[color:var(--aq-surface-card)] border border-[color:var(--aq-border)]',
            'rounded-[var(--radius-3xl)] shadow-[var(--shadow-premium)]',
            sizeClasses,
            props.class
          )"
          role="dialog"
          aria-modal="true"
        >
          <!-- Header -->
          <div class="px-6 pt-6 pb-4 flex items-start justify-between gap-4">
            <div>
              <h2 class="font-display text-xl font-semibold text-[color:var(--aq-fg)]">
                {{ title }}
              </h2>
              <p v-if="subtitle" class="mt-1 text-sm text-[color:var(--aq-muted)]">
                {{ subtitle }}
              </p>
            </div>
            <button
              v-if="closable"
              type="button"
              class="p-2 rounded-[var(--radius-lg)] text-[color:var(--aq-muted)] hover:bg-[color:var(--aq-surface-2)] hover:text-[color:var(--aq-fg)] transition-colors"
              @click="close"
            >
              <X class="w-5 h-5" />
            </button>
          </div>
          
          <!-- Content -->
          <div class="px-6 pb-6 max-h-[60vh] overflow-y-auto">
            <slot />
          </div>
          
          <!-- Footer -->
          <div v-if="$slots.footer" class="px-6 py-4 border-t border-[color:var(--aq-border)] bg-[color:var(--aq-surface-1)]/30">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, watch } from 'vue';
import { X } from 'lucide-vue-next';
import { cn } from '../../lib/cn';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  size: { type: String, default: 'md' }, // sm, md, lg, xl, full
  closable: { type: Boolean, default: true },
  closeOnBackdrop: { type: Boolean, default: true },
  closeOnEscape: { type: Boolean, default: true },
  class: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const sizeClasses = computed(() => {
  const sizes = {
    sm: 'max-w-sm',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
    xl: 'max-w-4xl',
    full: 'max-w-[95vw]',
  };
  return sizes[props.size] || sizes.md;
});

function close() {
  emit('update:modelValue', false);
}

// Handle escape key
watch(() => props.modelValue, (isOpen) => {
  if (!isOpen) return;
  
  function handleEscape(e) {
    if (e.key === 'Escape' && props.closeOnEscape) {
      close();
    }
  }
  
  document.addEventListener('keydown', handleEscape);
  
  // Cleanup
  return () => document.removeEventListener('keydown', handleEscape);
});
</script>

<style scoped>
.app-modal-enter-active,
.app-modal-leave-active {
  transition: opacity 200ms ease;
}

.app-modal-enter-active .app-modal,
.app-modal-leave-active .app-modal {
  transition: transform 200ms ease, opacity 200ms ease;
}

.app-modal-enter-from,
.app-modal-leave-to {
  opacity: 0;
}

.app-modal-enter-from .app-modal,
.app-modal-leave-to .app-modal {
  opacity: 0;
  transform: scale(0.95) translateY(10px);
}
</style>
