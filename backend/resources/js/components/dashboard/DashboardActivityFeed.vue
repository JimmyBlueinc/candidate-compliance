<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <i class="pi pi-bolt text-[color:var(--aq-primary)] text-xs" />
        <span class="text-xs font-semibold tracking-widest uppercase text-[color:var(--aq-muted)]">Live Activity</span>
      </div>
      <div class="flex items-center gap-2">
        <select v-model="actionFilter" class="feed-filter">
          <option value="">All</option>
          <option value="created">Created</option>
          <option value="updated">Updated</option>
          <option value="deleted">Deleted</option>
          <option value="reviewed">Reviewed</option>
          <option value="approved">Approved</option>
        </select>
        <button type="button" class="feed-refresh" @click="loadFeed">
          Refresh
        </button>
      </div>
    </div>

    <div v-if="loading" class="feed-loading">Loading activity...</div>
    <div v-else-if="items.length === 0" class="feed-empty">No recent activity</div>
    <div v-else class="feed-list">
      <article v-for="item in items" :key="item.id" class="feed-item">
        <div class="feed-dot" />
        <div class="min-w-0">
          <div class="feed-line">
            <span class="font-semibold text-[color:var(--aq-fg)]">{{ item.user?.name || 'System' }}</span>
            <span class="text-[color:var(--aq-muted)]">{{ item.action || 'updated' }}</span>
            <span class="font-medium text-[color:var(--aq-fg)] truncate">{{ item.entity_name || item.entity || 'record' }}</span>
          </div>
          <div class="feed-meta">
            <span>{{ item.description || 'No details' }}</span>
            <span>•</span>
            <span>{{ formatTime(item.created_at) }}</span>
          </div>
        </div>
      </article>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { apiGet } from '../../lib/api';
import { usePolling } from '../../composables/usePolling';

const items = ref([]);
const loading = ref(false);
const actionFilter = ref('');
const latestId = ref(0);

function extractMaxId(list = []) {
  return list.reduce((max, row) => Math.max(max, Number(row?.id || 0)), 0);
}

function dedupeById(list = []) {
  const seen = new Set();
  return list.filter((row) => {
    const id = Number(row?.id || 0);
    if (!id || seen.has(id)) return false;
    seen.add(id);
    return true;
  });
}

async function loadFeed(incremental = false) {
  if (!incremental) loading.value = true;
  try {
    const params = new URLSearchParams({ per_page: '12' });
    if (actionFilter.value) params.set('action', actionFilter.value);
    if (incremental && latestId.value > 0) {
      params.set('since_id', String(latestId.value));
    }
    const res = await apiGet(`/activity-logs?${params.toString()}`);
    const payload = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : []);

    if (incremental) {
      if (payload.length === 0) return;
      const next = dedupeById([...payload.slice().reverse(), ...items.value]).slice(0, 10);
      items.value = next;
      latestId.value = Math.max(latestId.value, extractMaxId(payload));
      return;
    }

    items.value = payload.slice(0, 10);
    latestId.value = extractMaxId(items.value);
  } catch {
    if (!incremental) items.value = [];
  } finally {
    if (!incremental) loading.value = false;
  }
}

function formatTime(dateStr) {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  const diffMin = Math.floor((Date.now() - d.getTime()) / 60000);
  if (diffMin < 1) return 'just now';
  if (diffMin < 60) return `${diffMin}m ago`;
  const diffHr = Math.floor(diffMin / 60);
  if (diffHr < 24) return `${diffHr}h ago`;
  return d.toLocaleDateString();
}

watch(actionFilter, () => {
  latestId.value = 0;
  loadFeed(false);
});
usePolling(() => loadFeed(true), 5000, { immediate: false });
loadFeed(false);
</script>

<style scoped>
.feed-filter {
  border: 1px solid var(--aq-border);
  background: var(--aq-surface-2);
  color: var(--aq-fg);
  border-radius: 0.5rem;
  font-size: 0.72rem;
  padding: 0.3rem 0.45rem;
}

.feed-refresh {
  border: 1px solid var(--aq-border);
  border-radius: 0.5rem;
  font-size: 0.72rem;
  font-weight: 600;
  padding: 0.3rem 0.5rem;
  color: var(--aq-muted);
}

.feed-refresh:hover {
  color: var(--aq-fg);
  background: var(--aq-surface-2);
}

.feed-loading,
.feed-empty {
  color: var(--aq-muted);
  font-size: 0.8rem;
  padding: 0.5rem 0.1rem;
}

.feed-list {
  display: grid;
  gap: 0.55rem;
}

.feed-item {
  display: flex;
  gap: 0.55rem;
  padding: 0.55rem 0.6rem;
  border: 1px solid var(--aq-border);
  border-radius: 0.7rem;
  background: color-mix(in srgb, var(--aq-surface-2) 80%, transparent);
}

.feed-dot {
  width: 0.55rem;
  height: 0.55rem;
  margin-top: 0.35rem;
  border-radius: 999px;
  background: color-mix(in srgb, var(--aq-primary) 80%, white 20%);
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--aq-primary) 15%, transparent);
}

.feed-line {
  display: flex;
  align-items: center;
  gap: 0.38rem;
  font-size: 0.8rem;
}

.feed-meta {
  margin-top: 0.16rem;
  display: flex;
  align-items: center;
  gap: 0.28rem;
  color: var(--aq-muted);
  font-size: 0.72rem;
}
</style>
