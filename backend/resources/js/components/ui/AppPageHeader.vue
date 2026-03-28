<template>
  <div class="app-page-header mb-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <!-- Title Section -->
      <div class="min-w-0">
        <div class="flex items-center gap-3">
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
