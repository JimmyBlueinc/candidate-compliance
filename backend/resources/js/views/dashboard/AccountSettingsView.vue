<template>
  <div class="max-w-3xl space-y-6">
    <div class="rounded-2xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-card)] p-6">
      <h2 class="text-xl font-semibold text-[color:var(--aq-fg)]">Account Settings</h2>
      <p class="mt-1 text-sm text-[color:var(--aq-muted)]">Control your account identity and workspace metadata.</p>
    </div>

    <div class="rounded-2xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-card)] p-6 space-y-4">
      <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="save">
        <label class="space-y-1">
          <span class="text-xs font-semibold uppercase tracking-widest text-[color:var(--aq-muted)]">Phone</span>
          <input v-model="form.phone" type="text" class="w-full rounded-xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] px-3 py-2 text-sm text-[color:var(--aq-fg)]" />
        </label>
        <label class="space-y-1">
          <span class="text-xs font-semibold uppercase tracking-widest text-[color:var(--aq-muted)]">Department</span>
          <input v-model="form.department" type="text" class="w-full rounded-xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] px-3 py-2 text-sm text-[color:var(--aq-fg)]" />
        </label>
        <label class="space-y-1 md:col-span-2">
          <span class="text-xs font-semibold uppercase tracking-widest text-[color:var(--aq-muted)]">Address</span>
          <input v-model="form.address" type="text" class="w-full rounded-xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] px-3 py-2 text-sm text-[color:var(--aq-fg)]" />
        </label>
        <div class="md:col-span-2 grid grid-cols-1 gap-4 md:grid-cols-2">
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
        <div class="md:col-span-2 mt-2 flex items-center justify-end gap-3 border-t border-[color:var(--aq-border)] pt-4">
          <span v-if="status" class="text-xs text-emerald-400">{{ status }}</span>
          <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" :disabled="saving">
            {{ saving ? 'Saving...' : 'Save Account Settings' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { apiPut } from '../../lib/api';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const saving = ref(false);
const status = ref('');
const form = reactive({
  phone: auth.user?.phone || '',
  department: auth.user?.department || '',
  address: auth.user?.address || '',
});

const loginAt = computed(() => {
  const raw = localStorage.getItem('auth.login_at');
  if (!raw) return 'Unknown';
  const date = new Date(raw);
  return Number.isNaN(date.getTime()) ? 'Unknown' : date.toLocaleString();
});

async function save() {
  saving.value = true;
  status.value = '';
  try {
    const res = await apiPut('/user/profile', {
      phone: form.phone || null,
      department: form.department || null,
      address: form.address || null,
    });
    if (res?.user) {
      auth.setSession({ token: auth.token, user: res.user });
    }
    status.value = 'Account settings saved.';
  } finally {
    saving.value = false;
  }
}
</script>

