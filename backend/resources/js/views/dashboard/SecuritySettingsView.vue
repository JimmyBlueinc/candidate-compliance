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
      <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
        <label class="flex items-center gap-2">
          <input v-model="securityForm.expiry_reminders_enabled" type="checkbox" />
          <span class="text-sm text-[color:var(--aq-fg)]">Enable security/compliance reminders</span>
        </label>
        <label class="space-y-1">
          <span class="text-xs font-semibold uppercase tracking-widest text-[color:var(--aq-muted)]">Reminder Days</span>
          <input
            v-model.number="securityForm.reminder_days_before"
            type="number"
            min="1"
            max="365"
            class="w-full rounded-xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] px-3 py-2 text-sm text-[color:var(--aq-fg)]"
          />
        </label>
      </div>
      <div class="mt-6 flex items-center justify-end gap-3 border-t border-[color:var(--aq-border)] pt-4">
        <span v-if="status" class="text-xs text-emerald-400">{{ status }}</span>
        <button
          type="button"
          class="rounded-xl border border-[color:var(--aq-border)] bg-[color:var(--aq-surface-2)] px-4 py-2 text-sm font-semibold text-[color:var(--aq-fg)] hover:bg-[color:var(--aq-surface)]"
          :disabled="saving"
          @click="saveSecurity"
        >
          {{ saving ? 'Saving...' : 'Save Security Preferences' }}
        </button>
      </div>
      <div class="mt-4 flex items-center justify-end">
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
import { computed, onMounted, reactive, ref } from 'vue';
import { apiGet, apiPut } from '../../lib/api';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const saving = ref(false);
const status = ref('');
const securityForm = reactive({
  expiry_reminders_enabled: true,
  reminder_days_before: 30,
});

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

onMounted(async () => {
  try {
    const res = await apiGet('/settings');
    const settings = res?.settings || {};
    securityForm.expiry_reminders_enabled = settings.expiry_reminders_enabled ?? true;
    securityForm.reminder_days_before = Number(settings.reminder_days_before || 30);
  } catch (_) {
    // Keep defaults.
  }
});

async function saveSecurity() {
  saving.value = true;
  status.value = '';
  try {
    await apiPut('/settings', {
      expiry_reminders_enabled: securityForm.expiry_reminders_enabled,
      reminder_days_before: securityForm.reminder_days_before,
    });
    status.value = 'Security preferences saved.';
  } finally {
    saving.value = false;
  }
}
</script>

