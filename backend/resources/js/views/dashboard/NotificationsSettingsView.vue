<template>
  <div class="max-w-3xl space-y-6">
    <div class="rounded-2xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-card)] p-6">
      <h2 class="text-xl font-semibold text-[color:var(--aq-fg)]">Notification Settings</h2>
      <p class="mt-1 text-sm text-[color:var(--aq-muted)]">Control in-app and email notification behavior for your account.</p>
    </div>

    <div class="rounded-2xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-card)] p-6">
      <form class="space-y-4" @submit.prevent="save">
        <label class="flex items-center gap-2">
          <input v-model="form.notifications_enabled" type="checkbox" />
          <span class="text-sm text-[color:var(--aq-fg)]">Enable in-app notifications</span>
        </label>
        <label class="flex items-center gap-2">
          <input v-model="form.email_notifications_enabled" type="checkbox" />
          <span class="text-sm text-[color:var(--aq-fg)]">Enable email notifications</span>
        </label>
        <label class="flex items-center gap-2">
          <input v-model="form.expiry_reminders_enabled" type="checkbox" />
          <span class="text-sm text-[color:var(--aq-fg)]">Enable compliance reminder notifications</span>
        </label>
        <label class="space-y-1 block">
          <span class="text-xs font-semibold uppercase tracking-widest text-[color:var(--aq-muted)]">Reminder days before expiry</span>
          <input
            v-model.number="form.reminder_days_before"
            type="number"
            min="1"
            max="365"
            class="w-full rounded-xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] px-3 py-2 text-sm text-[color:var(--aq-fg)]"
          />
        </label>
        <div class="mt-2 flex items-center justify-end gap-3 border-t border-[color:var(--aq-border)] pt-4">
          <span v-if="status" class="text-xs text-emerald-400">{{ status }}</span>
          <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" :disabled="saving">
            {{ saving ? 'Saving...' : 'Save Notification Settings' }}
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
  notifications_enabled: true,
  email_notifications_enabled: true,
  expiry_reminders_enabled: true,
  reminder_days_before: 30,
});

onMounted(async () => {
  try {
    const res = await apiGet('/settings');
    const settings = res?.settings || {};
    Object.assign(form, {
      notifications_enabled: settings.notifications_enabled ?? true,
      email_notifications_enabled: settings.email_notifications_enabled ?? true,
      expiry_reminders_enabled: settings.expiry_reminders_enabled ?? true,
      reminder_days_before: Number(settings.reminder_days_before || 30),
    });
  } catch (_) {
    // Keep defaults.
  }
});

async function save() {
  saving.value = true;
  status.value = '';
  try {
    await apiPut('/settings', form);
    status.value = 'Notification settings saved.';
  } finally {
    saving.value = false;
  }
}
</script>

