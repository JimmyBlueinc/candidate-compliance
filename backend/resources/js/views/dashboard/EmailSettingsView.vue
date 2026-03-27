<template>
  <div class="space-y-6 max-w-5xl">
    <Card>
      <template #content>
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
          <div>
            <h2 class="font-display text-2xl">Email Settings</h2>
            <p class="text-sm text-[color:var(--p-text-muted-color)]">Configure SMTP credentials for outbound system notifications.</p>
          </div>

          <div class="flex gap-3">
            <Button
              type="button"
              label="Send Test"
              icon="pi pi-flask"
              severity="secondary"
              outlined
              :loading="testing"
              :disabled="loading"
              @click="onTest"
            />
            <Button
              type="button"
              label="Save Changes"
              icon="pi pi-save"
              :loading="saving"
              :disabled="loading"
              @click="onSave"
            />
          </div>
        </div>
      </template>
    </Card>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <div v-if="loading" class="flex items-center gap-3 text-[color:var(--p-text-muted-color)]">
      <ProgressSpinner style="width: 20px; height: 20px" strokeWidth="6" />
      <span>Loading…</span>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <Card>
        <template #content>
          <h3 class="font-display text-xl">SMTP</h3>
          <p class="text-sm text-[color:var(--p-text-muted-color)] mt-1">Connection details to your mail server.</p>

          <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Host</label>
              <InputText v-model="fieldModel('smtp_host').value" class="w-full" />
            </div>
            <div class="space-y-2">
              <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Port</label>
              <InputText v-model="fieldModel('smtp_port').value" class="w-full" />
            </div>
            <div class="space-y-2">
              <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Username</label>
              <InputText v-model="fieldModel('smtp_username').value" class="w-full" />
            </div>
            <div class="space-y-2">
              <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Password</label>
              <Password v-model="fieldModel('smtp_password').value" class="w-full" toggleMask :feedback="false" />
            </div>
          </div>

          <div class="mt-6 p-4 rounded-2xl border border-[color:var(--p-surface-border)]">
            <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Tip</div>
            <p class="text-sm text-[color:var(--p-text-muted-color)] mt-2">
              Use an app password or SMTP relay credentials. Don’t use your personal mailbox password.
            </p>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <h3 class="font-display text-xl">Sender</h3>
          <p class="text-sm text-[color:var(--p-text-muted-color)] mt-1">Default “From” identity for outbound emails.</p>

          <div class="mt-6 grid grid-cols-1 gap-4">
            <div class="space-y-2">
              <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">From Email</label>
              <InputText v-model="fieldModel('from_email').value" class="w-full" />
            </div>
            <div class="space-y-2">
              <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">From Name</label>
              <InputText v-model="fieldModel('from_name').value" class="w-full" />
            </div>
          </div>

          <div class="mt-6 p-4 rounded-2xl border border-[color:var(--p-surface-border)]">
            <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Test email</div>
            <p class="text-sm text-[color:var(--p-text-muted-color)] mt-2">
              We’ll send a test message to the email configured above.
            </p>
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { apiGet, apiPost, apiPut } from '../../lib/api';
import Button from 'primevue/button';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Password from 'primevue/password';
import ProgressSpinner from 'primevue/progressspinner';

const settings = ref({});
const loading = ref(true);
const saving = ref(false);
const testing = ref(false);
const error = ref('');

function setField(key, value) {
    settings.value = { ...settings.value, [key]: value };
}

function fieldModel(key) {
    return computed({
        get: () => String(settings.value?.[key] ?? ''),
        set: (v) => setField(key, v),
    });
}

async function load() {
    try {
        loading.value = true;
        error.value = '';
        const res = await apiGet('/email-settings');
        settings.value = res || {};
    } catch (e) {
        settings.value = {};
        error.value = e?.response?.data?.message || e?.message || 'Failed to load email settings';
    } finally {
        loading.value = false;
    }
}

async function onSave() {
    try {
        saving.value = true;
        error.value = '';
        await apiPut('/email-settings', settings.value);
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Failed to save email settings';
    } finally {
        saving.value = false;
    }
}

async function onTest() {
    try {
        testing.value = true;
        error.value = '';
        const email = settings.value.from_email || settings.value.smtp_username || '';
        await apiPost('/email-settings/test', { email });
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Failed to send test email';
    } finally {
        testing.value = false;
    }
}

onMounted(load);
</script>
