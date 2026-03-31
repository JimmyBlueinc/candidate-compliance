<template>
  <div class="space-y-6 max-w-4xl">
    <Card>
      <template #content>
        <div>
          <h2 class="font-display text-2xl">Global Configuration</h2>
          <p class="text-sm text-[color:var(--p-text-muted-color)]">Configure system-wide preferences and defaults.</p>
        </div>
      </template>
    </Card>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
    <Message v-if="success" severity="success" :closable="false">{{ success }}</Message>

    <div v-if="loading" class="flex items-center gap-3 text-[color:var(--p-text-muted-color)]">
      <ProgressSpinner style="width: 20px; height: 20px" strokeWidth="6" />
      <span>Loading…</span>
    </div>

    <Card v-else>
      <template #content>
        <form class="space-y-6" @submit.prevent="handleSave">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Language</label>
              <Dropdown v-model="settings.language" :options="languageOptions" optionLabel="label" optionValue="value" class="w-full" />
            </div>
            <div class="space-y-2">
              <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Timezone</label>
              <InputText v-model="settings.timezone" class="w-full" placeholder="UTC" />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Theme</label>
              <Dropdown v-model="settings.theme" :options="themeOptions" optionLabel="label" optionValue="value" class="w-full" />
            </div>
            <div class="space-y-2">
              <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Reminder Days Before</label>
              <InputText v-model.number="settings.reminder_days_before" type="number" min="1" max="365" class="w-full" />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="p-4 rounded-2xl border border-[color:var(--p-surface-border)] flex items-center justify-between gap-6">
              <div>
                <div class="font-semibold">Notifications</div>
                <div class="text-sm text-[color:var(--p-text-muted-color)]">Enable in-app notifications</div>
              </div>
              <InputSwitch v-model="settings.notifications_enabled" />
            </div>

            <div class="p-4 rounded-2xl border border-[color:var(--p-surface-border)] flex items-center justify-between gap-6">
              <div>
                <div class="font-semibold">Email Notifications</div>
                <div class="text-sm text-[color:var(--p-text-muted-color)]">Enable email notifications</div>
              </div>
              <InputSwitch v-model="settings.email_notifications_enabled" />
            </div>

            <div class="p-4 rounded-2xl border border-[color:var(--p-surface-border)] flex items-center justify-between gap-6">
              <div>
                <div class="font-semibold">Expiry Reminders</div>
                <div class="text-sm text-[color:var(--p-text-muted-color)]">Remind when credentials are expiring</div>
              </div>
              <InputSwitch v-model="settings.expiry_reminders_enabled" />
            </div>
          </div>

          <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <Button type="submit" label="Save Settings" icon="pi pi-save" :loading="saving" :disabled="loading" />
            <Button type="button" label="Reset Defaults" severity="secondary" outlined :loading="saving" :disabled="loading" @click="resetDefaults" />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { apiGet, apiPut, apiPost } from '../../lib/api';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Dropdown from 'primevue/dropdown';
import InputSwitch from 'primevue/inputswitch';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import ProgressSpinner from 'primevue/progressspinner';

const settings = ref({});
const loading = ref(true);
const saving = ref(false);
const error = ref('');
const success = ref('');

const languageOptions = [
    { label: 'English', value: 'en' },
    { label: 'Spanish', value: 'es' },
    { label: 'French', value: 'fr' },
];

const themeOptions = [
    { label: 'Auto', value: 'auto' },
    { label: 'Dark', value: 'dark' },
    { label: 'Light', value: 'light' },
];

async function fetchSettings() {
    try {
        loading.value = true;
        error.value = '';
        success.value = '';
        const res = await apiGet('/settings');
        settings.value = res?.settings || res || {};
    } catch (err) {
        error.value = err?.response?.data?.message || err?.message || 'Failed to load settings';
    } finally {
        loading.value = false;
    }
}

async function handleSave() {
    saving.value = true;
    error.value = '';
    success.value = '';
    try {
        await apiPut('/settings', settings.value);
        success.value = 'Settings updated successfully';
        await fetchSettings();
    } catch (err) {
        error.value = err?.response?.data?.message || err?.message || 'Failed to save settings';
    } finally {
        saving.value = false;
    }
}

async function resetDefaults() {
    saving.value = true;
    error.value = '';
    success.value = '';
    try {
        await apiPost('/settings/reset', {});
        success.value = 'Settings reset to defaults';
        await fetchSettings();
    } catch (err) {
        error.value = err?.response?.data?.message || err?.message || 'Failed to reset settings';
    } finally {
        saving.value = false;
    }
}

onMounted(fetchSettings);
</script>
