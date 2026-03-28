<template>
  <div class="space-y-6 max-w-2xl">
    <Card>
      <template #content>
        <h2 class="font-display text-2xl">Change Password</h2>
        <p class="text-sm text-[color:var(--p-text-muted-color)]">
          {{ auth.user?.must_change_password
            ? 'You must change your temporary password before continuing.'
            : 'Update your password.' }}
        </p>
      </template>
    </Card>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
    <Message v-if="success" severity="success" :closable="false">{{ success }}</Message>

    <Card>
      <template #content>
        <form class="space-y-4" @submit.prevent="submit">
          <!-- Hide current password field for forced password change (use temp password) -->
          <div v-if="!auth.user?.must_change_password" class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Current Password</label>
            <Password v-model="currentPassword" class="w-full" toggleMask :feedback="false" />
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">New Password</label>
              <Password v-model="password" class="w-full" toggleMask />
            </div>
            <div class="space-y-2">
              <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Confirm</label>
              <Password v-model="passwordConfirm" class="w-full" toggleMask :feedback="false" />
            </div>
          </div>

          <div class="flex justify-end">
            <Button type="submit" label="Update Password" icon="pi pi-check" :loading="saving" />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { apiPut } from '../../lib/api';
import { useAuthStore } from '../../stores/auth';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Message from 'primevue/message';
import Password from 'primevue/password';

const router = useRouter();
const auth = useAuthStore();

const currentPassword = ref('');
const password = ref('');
const passwordConfirm = ref('');

const saving = ref(false);
const error = ref('');
const success = ref('');

async function submit() {
    saving.value = true;
    error.value = '';
    success.value = '';

    // For forced password change, use temp password from auth store
    const isForcedChange = auth.user?.must_change_password;
    const currentPwd = isForcedChange ? auth._tempPassword : currentPassword.value;

    const payload = {
        current_password: currentPwd,
        password: password.value,
        password_confirmation: passwordConfirm.value,
    };

    try {
        const res = await apiPut('/user/password', payload);

        // Clear temp password after successful change
        if (isForcedChange) {
            auth.clearTempPassword();
        }

        if (res?.user) {
            auth.setSession({ token: auth.token, user: res.user });
        }

        success.value = 'Password updated successfully';
        currentPassword.value = '';
        password.value = '';
        passwordConfirm.value = '';

        setTimeout(() => router.push({ name: 'dashboard.index' }), 600);
    } catch (e) {
        const errors = e?.response?.data?.errors;
        if (errors) {
            const messages = Object.values(errors).flat().join(' ');
            error.value = messages || 'Validation failed';
        } else {
            error.value = e?.response?.data?.message || e?.message || 'Failed to update password';
        }
    } finally {
        saving.value = false;
    }
}
</script>
