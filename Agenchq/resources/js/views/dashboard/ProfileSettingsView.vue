<template>
  <div class="max-w-3xl space-y-6">
    <div class="rounded-2xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-card)] p-6">
      <h2 class="text-xl font-semibold text-[color:var(--aq-fg)]">Profile Settings</h2>
      <p class="mt-1 text-sm text-[color:var(--aq-muted)]">Update your personal profile details used across the workspace.</p>
    </div>

    <div class="rounded-2xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-card)] p-6">
      <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="save">
        <label class="space-y-1">
          <span class="text-xs font-semibold uppercase tracking-widest text-[color:var(--aq-muted)]">Name</span>
          <input v-model="form.name" type="text" class="w-full rounded-xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] px-3 py-2 text-sm" />
        </label>
        <label class="space-y-1">
          <span class="text-xs font-semibold uppercase tracking-widest text-[color:var(--aq-muted)]">Email</span>
          <input v-model="form.email" type="email" class="w-full rounded-xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] px-3 py-2 text-sm" />
        </label>
        <label class="space-y-1">
          <span class="text-xs font-semibold uppercase tracking-widest text-[color:var(--aq-muted)]">Phone</span>
          <input v-model="form.phone" type="text" class="w-full rounded-xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] px-3 py-2 text-sm" />
        </label>
        <label class="space-y-1">
          <span class="text-xs font-semibold uppercase tracking-widest text-[color:var(--aq-muted)]">Job Title</span>
          <input v-model="form.job_title" type="text" class="w-full rounded-xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] px-3 py-2 text-sm" />
        </label>
        <div class="md:col-span-2 mt-2 flex items-center justify-end gap-3 border-t border-[color:var(--aq-border)] pt-4">
          <span v-if="status" class="text-xs text-emerald-400">{{ status }}</span>
          <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" :disabled="saving">
            {{ saving ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { apiPut } from '../../lib/api';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const saving = ref(false);
const status = ref('');
const form = reactive({
  name: auth.user?.name || '',
  email: auth.user?.email || '',
  phone: auth.user?.phone || '',
  job_title: auth.user?.job_title || '',
});

async function save() {
  saving.value = true;
  status.value = '';
  try {
    const res = await apiPut('/user/profile', form);
    if (res?.user) {
      auth.setSession({ token: auth.token, user: res.user });
    }
    status.value = 'Profile settings saved.';
  } finally {
    saving.value = false;
  }
}
</script>

