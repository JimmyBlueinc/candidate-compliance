<template>
  <Dialog
    v-model:visible="visible"
    modal
    header="Update Password"
    :closable="false"
    :style="{ width: 'min(450px, 95vw)' }"
    class="aq-password-modal"
  >
    <div class="space-y-4 pt-2">
      <p class="text-sm text-[color:var(--aq-muted)]">
        You are using a temporary password. For security, you must update it before continuing.
      </p>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--aq-muted)]">New Password</label>
          <Password
            v-model="newPassword"
            toggleMask
            class="w-full"
            inputClass="w-full"
            :feedback="true"
            required
          />
        </div>

        <div class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--aq-muted)]">Confirm Password</label>
          <Password
            v-model="confirmPassword"
            toggleMask
            :feedback="false"
            class="w-full"
            inputClass="w-full"
            required
          />
        </div>

        <Message v-if="error" severity="error" :closable="false" class="mt-2">{{ error }}</Message>

        <div class="flex pt-2">
          <Button
            type="submit"
            label="Update Password"
            class="w-full"
            :loading="loading"
          />
        </div>
      </form>
    </div>
  </Dialog>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { apiPut } from '../../lib/api';
import Dialog from 'primevue/dialog';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Message from 'primevue/message';

const auth = useAuthStore();
const visible = computed(() => !!auth.user?.must_change_password);

const newPassword = ref('');
const confirmPassword = ref('');
const loading = ref(false);
const error = ref('');

async function handleSubmit() {
  if (newPassword.value !== confirmPassword.value) {
    error.value = 'Passwords do not match.';
    return;
  }

  if (newPassword.value.length < 8) {
    error.value = 'Password must be at least 8 characters.';
    return;
  }

  // Check for temp password - if missing, force re-login
  if (!auth._tempPassword) {
    error.value = 'Session expired. Please log in again.';
    setTimeout(() => {
      auth.logout();
    }, 1500);
    return;
  }

  loading.value = true;
  error.value = '';

  const payload = {
    current_password: auth._tempPassword,
    password: newPassword.value,
    password_confirmation: confirmPassword.value,
  };
  
  console.log('[FORCE_PASSWORD_MODAL] submitting payload keys:', Object.keys(payload));
  console.log('[FORCE_PASSWORD_MODAL] has current_password:', Boolean(payload.current_password));

  try {
    const res = await apiPut('/user/password', payload);
    console.log('[FORCE_PASSWORD_MODAL] response:', res);
    
    // Clear temp password after success
    auth.clearTempPassword();
    
    // Refresh user data to clear the must_change_password flag
    await auth.fetchUser();
    
    newPassword.value = '';
    confirmPassword.value = '';
  } catch (e) {
    console.log('[FORCE_PASSWORD_MODAL] error:', e?.response?.data);
    const errors = e?.response?.data?.errors;
    if (errors) {
      const messages = Object.values(errors).flat().join(' ');
      error.value = messages || 'Validation failed';
    } else {
      error.value = e?.response?.data?.message || e?.message || 'Failed to update password.';
    }
  } finally {
    loading.value = false;
  }
}
</script>

<style>
.aq-password-modal .p-dialog-header {
  border-bottom: 1px solid var(--aq-border);
}
</style>
