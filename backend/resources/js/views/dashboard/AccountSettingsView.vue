<template>
  <div class="max-w-3xl space-y-6">
    <div class="rounded-2xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-card)] p-6">
      <h2 class="text-xl font-semibold text-[color:var(--aq-fg)]">Account Settings</h2>
      <p class="mt-1 text-sm text-[color:var(--aq-muted)]">Control your account identity and workspace metadata.</p>
    </div>

    <div class="rounded-2xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-card)] p-6 space-y-4">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
          <div class="text-xs font-semibold uppercase tracking-widest text-[color:var(--aq-muted)]">User ID</div>
          <div class="mt-1 text-sm text-[color:var(--aq-fg)]">{{ auth.user?.id || 'N/A' }}</div>
        </div>
        <div>
          <div class="text-xs font-semibold uppercase tracking-widest text-[color:var(--aq-muted)]">Role</div>
          <div class="mt-1 text-sm text-[color:var(--aq-fg)]">{{ auth.user?.role || 'N/A' }}</div>
        </div>
        <div>
          <div class="text-xs font-semibold uppercase tracking-widest text-[color:var(--aq-muted)]">Organization ID</div>
          <div class="mt-1 text-sm text-[color:var(--aq-fg)]">{{ auth.user?.organization_id || 'N/A' }}</div>
        </div>
        <div>
          <div class="text-xs font-semibold uppercase tracking-widest text-[color:var(--aq-muted)]">Login Time</div>
          <div class="mt-1 text-sm text-[color:var(--aq-fg)]">{{ loginAt }}</div>
        </div>
      </div>
      <div class="flex items-center justify-end">
        <RouterLink
          :to="{ name: 'dashboard.security_settings' }"
          class="rounded-xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] px-4 py-2 text-sm font-semibold text-[color:var(--aq-fg)] hover:bg-[color:var(--aq-surface)]"
        >
          Manage Password & Security
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const loginAt = computed(() => {
  const raw = localStorage.getItem('auth.login_at');
  if (!raw) return 'Unknown';
  const date = new Date(raw);
  return Number.isNaN(date.getTime()) ? 'Unknown' : date.toLocaleString();
});
</script>

