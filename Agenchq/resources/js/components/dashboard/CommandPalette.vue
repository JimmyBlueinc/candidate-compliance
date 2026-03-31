<template>
  <Teleport to="body">
    <div v-if="open" class="cp-overlay" @click.self="close">
      <div class="cp-panel">
        <div class="cp-header">
          <i class="pi pi-search text-sm text-[color:var(--aq-muted)]" />
          <input
            ref="inputEl"
            v-model="query"
            type="text"
            class="cp-input"
            placeholder="Search pages, facilities, users, contracts..."
            @keydown.esc.prevent="close"
          />
          <span class="cp-hint">ESC</span>
        </div>

        <div class="cp-body">
          <section class="cp-section">
            <div class="cp-label">Quick Actions</div>
            <button
              v-for="action in filteredActions"
              :key="action.id"
              type="button"
              class="cp-item"
              @click="runAction(action)"
            >
              <span class="cp-title">{{ action.label }}</span>
              <span class="cp-meta">{{ action.meta }}</span>
            </button>
          </section>

          <section class="cp-section">
            <div class="cp-label">Navigation</div>
            <button
              v-for="routeItem in filteredRoutes"
              :key="routeItem.id"
              type="button"
              class="cp-item"
              @click="goRoute(routeItem)"
            >
              <span class="cp-title">{{ routeItem.label }}</span>
              <span class="cp-meta">Route</span>
            </button>
          </section>

          <section class="cp-section">
            <div class="cp-label">Live Results</div>
            <button
              v-for="result in liveResults"
              :key="result.id"
              type="button"
              class="cp-item"
              @click="goLiveResult(result)"
            >
              <span class="cp-title">{{ result.label }}</span>
              <span class="cp-meta">{{ result.kind }}</span>
            </button>
            <div v-if="query.trim().length >= 2 && !liveResults.length" class="cp-empty">
              No matching entities
            </div>
          </section>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { apiGet } from '../../lib/api';

const router = useRouter();

const open = ref(false);
const query = ref('');
const inputEl = ref(null);
const liveResults = ref([]);
let debounceTimer = null;

const quickActions = [
  {
    id: 'create-facility',
    label: 'Create Facility',
    meta: 'Operations',
    run: () => router.push({ name: 'dashboard.facilities' }),
  },
  {
    id: 'open-contracts',
    label: 'Open Contracts Workspace',
    meta: 'Compliance',
    run: () => router.push({ name: 'dashboard.facilities' }),
  },
  {
    id: 'open-users',
    label: 'Go to User Management',
    meta: 'Admin',
    run: () => router.push({ name: 'dashboard.org_users' }),
  },
  {
    id: 'open-billing',
    label: 'Go to Billing / Finance',
    meta: 'Finance',
    run: () => router.push({ name: 'dashboard.finance' }),
  },
];

const routeCandidates = computed(() => {
  return router
    .getRoutes()
    .filter((r) => r.name && String(r.name).startsWith('dashboard.') && !String(r.name).includes('detail'))
    .map((r) => ({
      id: String(r.name),
      label: prettifyName(String(r.name)),
      routeName: String(r.name),
    }));
});

const filteredActions = computed(() => {
  const q = query.value.trim().toLowerCase();
  if (!q) return quickActions;
  return quickActions.filter((a) => `${a.label} ${a.meta}`.toLowerCase().includes(q));
});

const filteredRoutes = computed(() => {
  const q = query.value.trim().toLowerCase();
  if (!q) return routeCandidates.value.slice(0, 10);
  return routeCandidates.value.filter((r) => r.label.toLowerCase().includes(q)).slice(0, 12);
});

function prettifyName(name) {
  return name
    .replace('dashboard.', '')
    .replaceAll('_', ' ')
    .replaceAll('.', ' ')
    .replace(/\b\w/g, (m) => m.toUpperCase());
}

function openPalette() {
  open.value = true;
  nextTick(() => inputEl.value?.focus());
}

function close() {
  open.value = false;
  query.value = '';
  liveResults.value = [];
}

function runAction(action) {
  action.run?.();
  close();
}

function goRoute(item) {
  router.push({ name: item.routeName });
  close();
}

function goLiveResult(result) {
  if (result.kind === 'Facility') {
    router.push({
      name: 'dashboard.facilities.detail',
      params: { id: result.entityId },
      query: { tab: 'overview' },
    });
  } else if (result.kind === 'Contract') {
    router.push({
      name: 'dashboard.facilities.detail',
      params: { id: result.facilityId },
      query: { tab: 'contracts' },
    });
  } else if (result.kind === 'User') {
    router.push({ name: 'dashboard.org_users' });
  }
  close();
}

async function loadLiveResults() {
  const q = query.value.trim();
  if (q.length < 2) {
    liveResults.value = [];
    return;
  }

  try {
    const [facilitiesRes, usersRes] = await Promise.all([
      apiGet('/v1/facilities'),
      apiGet('/admin/users'),
    ]);

    const facilities = Array.isArray(facilitiesRes?.data) ? facilitiesRes.data : (Array.isArray(facilitiesRes) ? facilitiesRes : []);
    const users = Array.isArray(usersRes?.users) ? usersRes.users : [];

    const matchedFacilities = facilities
      .filter((f) => String(f.name || '').toLowerCase().includes(q.toLowerCase()))
      .slice(0, 5)
      .map((f) => ({
        id: `facility-${f.id}`,
        label: f.name,
        kind: 'Facility',
        entityId: f.id,
      }));

    const matchedContracts = facilities
      .filter((f) => Number(f.contracts_count || 0) > 0 && String(f.name || '').toLowerCase().includes(q.toLowerCase()))
      .slice(0, 3)
      .map((f) => ({
        id: `contract-facility-${f.id}`,
        label: `${f.name} contracts`,
        kind: 'Contract',
        facilityId: f.id,
      }));

    const matchedUsers = users
      .filter((u) => `${u.name || ''} ${u.email || ''}`.toLowerCase().includes(q.toLowerCase()))
      .slice(0, 5)
      .map((u) => ({
        id: `user-${u.id}`,
        label: `${u.name || 'User'} (${u.email || 'no-email'})`,
        kind: 'User',
      }));

    liveResults.value = [...matchedFacilities, ...matchedContracts, ...matchedUsers].slice(0, 10);
  } catch {
    liveResults.value = [];
  }
}

watch(query, () => {
  if (debounceTimer) clearTimeout(debounceTimer);
  debounceTimer = setTimeout(loadLiveResults, 200);
});

function onGlobalKeydown(e) {
  const isCmdK = (e.metaKey || e.ctrlKey) && String(e.key).toLowerCase() === 'k';
  if (isCmdK) {
    e.preventDefault();
    if (open.value) close();
    else openPalette();
  }
}

onMounted(() => window.addEventListener('keydown', onGlobalKeydown));
onUnmounted(() => window.removeEventListener('keydown', onGlobalKeydown));

defineExpose({ openPalette });
</script>

<style scoped>
.cp-overlay {
  position: fixed;
  inset: 0;
  z-index: 1200;
  background: rgba(3, 7, 18, 0.48);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding-top: 9vh;
}

.cp-panel {
  width: min(860px, 94vw);
  max-height: 78vh;
  border-radius: 1rem;
  border: 1px solid var(--aq-border);
  background: var(--aq-surface-card);
  overflow: hidden;
  box-shadow: 0 30px 80px rgba(0, 0, 0, 0.28);
  animation: cpIn 180ms ease;
}

.cp-header {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.85rem 1rem;
  border-bottom: 1px solid var(--aq-border);
  background: color-mix(in srgb, var(--aq-surface-2) 92%, white 8%);
}

.cp-input {
  flex: 1;
  background: transparent;
  border: 0;
  outline: 0;
  color: var(--aq-fg);
  font-size: 0.94rem;
}

.cp-hint {
  border: 1px solid var(--aq-border);
  border-radius: 0.45rem;
  padding: 0.08rem 0.4rem;
  font-size: 0.68rem;
  color: var(--aq-muted);
}

.cp-body {
  max-height: calc(78vh - 56px);
  overflow: auto;
  padding: 0.6rem;
  display: grid;
  gap: 0.7rem;
}

.cp-section {
  border: 1px solid var(--aq-border);
  border-radius: 0.8rem;
  overflow: hidden;
}

.cp-label {
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--aq-muted);
  padding: 0.5rem 0.75rem;
  background: color-mix(in srgb, var(--aq-surface-2) 88%, transparent);
}

.cp-item {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.8rem;
  padding: 0.6rem 0.75rem;
  border: 0;
  border-top: 1px solid var(--aq-border);
  background: transparent;
  text-align: left;
  cursor: pointer;
}

.cp-item:hover {
  background: color-mix(in srgb, var(--aq-primary) 12%, transparent);
}

.cp-title {
  color: var(--aq-fg);
  font-size: 0.86rem;
  font-weight: 600;
}

.cp-meta {
  color: var(--aq-muted);
  font-size: 0.72rem;
}

.cp-empty {
  padding: 0.75rem;
  color: var(--aq-muted);
  font-size: 0.8rem;
}

@keyframes cpIn {
  from {
    opacity: 0;
    transform: translateY(-10px) scale(0.985);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
</style>
