<template>
  <div class="max-w-3xl space-y-6">
    <div class="rounded-2xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-card)] p-6">
      <h2 class="text-xl font-semibold text-[color:var(--aq-fg)]">Preferences</h2>
      <p class="mt-1 text-sm text-[color:var(--aq-muted)]">Configure dashboard behavior, timezone, and notification defaults.</p>
    </div>

    <div class="rounded-2xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-card)] p-6">
      <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="save">
        <label class="space-y-1">
          <span class="text-xs font-semibold uppercase tracking-widest text-[color:var(--aq-muted)]">Language</span>
          <select v-model="form.language" class="w-full rounded-xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] px-3 py-2 text-sm">
            <option value="en">English</option>
            <option value="es">Spanish</option>
            <option value="fr">French</option>
          </select>
        </label>
        <label class="space-y-1">
          <span class="text-xs font-semibold uppercase tracking-widest text-[color:var(--aq-muted)]">Timezone</span>
          <input v-model="form.timezone" type="text" class="w-full rounded-xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] px-3 py-2 text-sm" />
        </label>
        <label class="space-y-1 flex items-center gap-2 md:col-span-2">
          <input v-model="form.notifications_enabled" type="checkbox" />
          <span class="text-sm text-[color:var(--aq-fg)]">Enable in-app notifications</span>
        </label>
        <label class="space-y-1 flex items-center gap-2 md:col-span-2">
          <input v-model="form.email_notifications_enabled" type="checkbox" />
          <span class="text-sm text-[color:var(--aq-fg)]">Enable email notifications</span>
        </label>
        <div class="md:col-span-2 mt-2 flex items-center justify-end gap-3 border-t border-[color:var(--aq-border)] pt-4">
          <span v-if="status" class="text-xs text-emerald-400">{{ status }}</span>
          <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" :disabled="saving">
            {{ saving ? 'Saving...' : 'Save Preferences' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { apiGet, apiPut } from '../../lib/api';

const saving = ref(false);
const status = ref('');
const form = reactive({
  language: 'en',
  timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
  notifications_enabled: true,
  email_notifications_enabled: true,
});

onMounted(async () => {
  try {
    const res = await apiGet('/settings');
    const settings = res?.settings || {};
    Object.assign(form, {
      language: settings.language || form.language,
      timezone: settings.timezone || form.timezone,
      notifications_enabled: settings.notifications_enabled ?? true,
      email_notifications_enabled: settings.email_notifications_enabled ?? true,
    });
  } catch (_) {
    // Keep defaults when endpoint is unavailable.
  }
});

async function save() {
  saving.value = true;
  status.value = '';
  try {
    await apiPut('/settings', form);
    status.value = 'Preferences saved.';
  } finally {
    saving.value = false;
  }
}
</script>

