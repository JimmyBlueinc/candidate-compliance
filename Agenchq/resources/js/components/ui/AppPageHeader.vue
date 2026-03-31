<template>
  <div class="app-page-header app-page-header-shell mb-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <!-- Title Section -->
      <div class="min-w-0">
        <div class="flex items-center gap-3">
          <span class="aq-header-pulse" aria-hidden="true"></span>
          <!-- Breadcrumb -->
          <div v-if="breadcrumb" class="flex items-center gap-2 text-sm text-[color:var(--aq-muted)]">
            <RouterLink 
              v-if="breadcrumb.to" 
              :to="breadcrumb.to"
              class="hover:text-[color:var(--aq-primary)] transition-colors"
            >
              {{ breadcrumb.label }}
            </RouterLink>
            <span v-else>{{ breadcrumb.label }}</span>
            <ChevronRight class="w-4 h-4" />
          </div>
          <h1 class="font-display text-2xl sm:text-3xl font-bold tracking-tight text-[color:var(--aq-fg)] truncate">
            {{ title }}
          </h1>
        </div>
        <p v-if="subtitle" class="mt-1.5 text-sm sm:text-base text-[color:var(--aq-muted)] max-w-2xl">
          {{ subtitle }}
        </p>
      </div>
      
      <!-- Actions -->
      <div v-if="$slots.actions" class="flex items-center gap-3 shrink-0">
        <slot name="actions" />
      </div>
    </div>
    
    <!-- Tabs -->
    <div v-if="$slots.tabs" class="mt-6 -mb-8">
      <slot name="tabs" />
    </div>
  </div>
</template>

<script setup>
import { ChevronRight } from 'lucide-vue-next';

defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  breadcrumb: { 
    type: Object, 
    default: null 
    // { label: string, to?: RouteLocation }
  },
});
</script>

<style scoped>
.app-page-header-shell {
  position: relative;
  border: 1px solid color-mix(in srgb, var(--aq-border) 85%, transparent);
  border-radius: var(--radius-xl);
  padding: 1rem 1.1rem;
  background: linear-gradient(
    180deg,
    color-mix(in srgb, var(--aq-surface-card) 86%, white 14%),
    color-mix(in srgb, var(--aq-surface-2) 82%, transparent)
  );
  box-shadow: 0 10px 32px rgba(15, 23, 42, 0.08);
}

.app-page-header-shell::after {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  height: 2px;
  border-radius: var(--radius-xl) var(--radius-xl) 0 0;
  background: linear-gradient(
    90deg,
    transparent,
    color-mix(in srgb, var(--aq-primary) 60%, white 40%),
    transparent
  );
}

.aq-header-pulse {
  width: 0.55rem;
  height: 0.55rem;
  border-radius: 999px;
  background: color-mix(in srgb, var(--aq-primary) 85%, white 15%);
  box-shadow: 0 0 0 5px color-mix(in srgb, var(--aq-primary) 14%, transparent);
}
</style>
