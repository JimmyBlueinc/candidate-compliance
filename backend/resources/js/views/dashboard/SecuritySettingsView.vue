<template>
  <div class="max-w-3xl space-y-6">
    <div class="rounded-2xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-card)] p-6">
      <h2 class="text-xl font-semibold text-[color:var(--aq-fg)]">Security</h2>
      <p class="mt-1 text-sm text-[color:var(--aq-muted)]">Manage password security and account access posture.</p>
    </div>

    <div class="rounded-2xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-card)] p-6">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
          <div class="text-xs font-semibold uppercase tracking-widest text-[color:var(--aq-muted)]">Current Session Started</div>
          <div class="mt-1 text-sm text-[color:var(--aq-fg)]">{{ loginAt }}</div>
        </div>
        <div>
          <div class="text-xs font-semibold uppercase tracking-widest text-[color:var(--aq-muted)]">Last Active</div>
          <div class="mt-1 text-sm text-[color:var(--aq-fg)]">{{ lastActive }}</div>
        </div>
      </div>
      <div class="mt-5 flex items-center justify-end">
        <RouterLink
          :to="{ name: 'dashboard.change_password' }"
          class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
        >
          Change Password
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
  const d = new Date(raw);
  return Number.isNaN(d.getTime()) ? 'Unknown' : d.toLocaleString();
});

const lastActive = computed(() => {
  const raw = auth.user?.last_activity_at;
  if (!raw) return 'Unknown';
  const d = new Date(raw);
  return Number.isNaN(d.getTime()) ? 'Unknown' : d.toLocaleString();
});
</script>

