<template>
  <div
    :class="cn(
      'app-table-shell overflow-hidden',
      'rounded-[var(--radius-2xl)] border',
      'bg-[color:var(--aq-surface-card)] border-[color:var(--aq-border)]',
      props.class
    )"
  >
    <!-- Table Header -->
    <div v-if="$slots.header || title" class="px-6 py-4 border-b border-[color:var(--aq-border)]">
      <div class="flex items-center justify-between gap-4">
        <div>
          <h3 v-if="title" class="font-display font-semibold text-[color:var(--aq-fg)]">
            {{ title }}
          </h3>
          <p v-if="subtitle" class="text-sm text-[color:var(--aq-muted)] mt-0.5">
            {{ subtitle }}
          </p>
        </div>
        <div v-if="$slots.actions" class="flex items-center gap-2">
          <slot name="actions" />
        </div>
      </div>
    </div>
    
    <!-- Search/Filter Bar -->
    <div v-if="searchable || $slots.filters" class="px-6 py-3 border-b border-[color:var(--aq-border)] bg-[color:var(--aq-surface-1)]/50">
      <div class="flex items-center gap-3">
        <div v-if="searchable" class="relative flex-1 max-w-md">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[color:var(--aq-muted)]" />
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="searchPlaceholder"
            class="w-full pl-10 pr-4 py-2 text-sm rounded-[var(--radius-lg)] border bg-[color:var(--aq-surface-2)] border-[color:var(--aq-border)] text-[color:var(--aq-fg)] placeholder:text-[color:var(--aq-muted)] focus:outline-none focus:border-[color:var(--aq-primary)]/50"
          />
        </div>
        <slot name="filters" />
      </div>
    </div>
    
    <!-- Loading State -->
    <div v-if="loading" class="p-8">
      <div class="space-y-3">
        <div v-for="i in 5" :key="i" class="flex items-center gap-4">
          <div class="h-4 w-4 rounded bg-[color:var(--aq-surface-2)] animate-pulse" />
          <div class="h-4 flex-1 rounded bg-[color:var(--aq-surface-2)] animate-pulse" />
          <div class="h-4 w-24 rounded bg-[color:var(--aq-surface-2)] animate-pulse" />
        </div>
      </div>
    </div>
    
    <!-- Empty State -->
    <AppEmpty
      v-else-if="!loading && empty"
      :title="emptyTitle"
      :description="emptyDescription"
      :icon="emptyIcon"
      compact
    >
      <template v-if="$slots.emptyAction" #action>
        <slot name="emptyAction" />
      </template>
    </AppEmpty>
    
    <!-- Table Content -->
    <div v-else class="overflow-x-auto">
      <slot :search-query="searchQuery" />
    </div>
    
    <!-- Footer/Pagination -->
    <div v-if="$slots.footer && !empty" class="px-6 py-3 border-t border-[color:var(--aq-border)] bg-[color:var(--aq-surface-1)]/30">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Search } from 'lucide-vue-next';
import { cn } from '../../lib/cn';
import AppEmpty from './AppEmpty.vue';

defineProps({
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  empty: { type: Boolean, default: false },
  emptyTitle: { type: String, default: 'No results' },
  emptyDescription: { type: String, default: 'Try adjusting your filters or search.' },
  emptyIcon: { type: Object, default: null },
  searchable: { type: Boolean, default: false },
  searchPlaceholder: { type: String, default: 'Search...' },
  class: { type: String, default: '' },
});

const searchQuery = ref('');
</script>
