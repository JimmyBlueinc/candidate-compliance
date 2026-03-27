<template>
  <div class="space-y-8 max-w-5xl">
    <UiPageHeader title="Your Profile" subtitle="Manage your personal information and account security." />

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
    <Message v-if="success" severity="success" :closable="false">{{ success }}</Message>

    <div v-if="fetching" class="flex items-center gap-3 text-[color:var(--aq-muted)]">
      <i class="pi pi-spin pi-spinner text-xl"></i>
      <span>Loading profile...</span>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <div class="lg:col-span-1 space-y-6">
        <UiCard>
          <div class="flex flex-col items-center text-center gap-4 py-4">
            <div class="relative group">
              <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-[color:var(--aq-border)] shadow-xl transition-transform group-hover:scale-[1.02]">
                <img :src="avatarPreviewUrl" alt="Avatar" class="w-full h-full object-cover" />
              </div>
              <label class="absolute bottom-0 right-0 cursor-pointer">
                <span class="sr-only">Upload avatar</span>
                <Button icon="pi pi-camera" rounded size="small" class="shadow-lg" />
                <input type="file" class="hidden" accept="image/*" @change="onAvatarChange" />
              </label>
            </div>

            <div class="space-y-1">
              <div class="font-display text-xl font-bold text-[color:var(--aq-fg)]">{{ formData.name }}</div>
              <UiBadge variant="primary">{{ auth.user?.role?.replace('_', ' ') }}</UiBadge>
            </div>
          </div>
        </UiCard>
      </div>

      <div class="lg:col-span-2 space-y-6">
        <UiCard title="Personal Information">
          <form class="space-y-6" @submit.prevent="handleSubmit">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--aq-muted)]">Full Name</label>
                <InputText v-model="formData.name" class="w-full" placeholder="John Doe" required />
              </div>

              <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--aq-muted)]">Email Address</label>
                <InputText v-model="formData.email" type="email" class="w-full" placeholder="john@example.com" required />
              </div>

              <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--aq-muted)]">Phone Number</label>
                <InputText v-model="formData.phone" class="w-full" placeholder="+1 (555) 000-0000" />
              </div>

              <div class="space-y-2 md:col-span-2">
                <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--aq-muted)]">Address</label>
                <InputText v-model="formData.address" class="w-full" placeholder="123 Main St, City, State" />
              </div>

              <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--aq-muted)]">Job Title</label>
                <InputText v-model="formData.job_title" class="w-full" placeholder="Operations Manager" />
              </div>

              <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--aq-muted)]">Department</label>
                <InputText v-model="formData.department" class="w-full" placeholder="Operations" />
              </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-[color:var(--aq-border)]">
              <Button type="submit" label="Save Changes" icon="pi pi-check" :loading="loading" />
            </div>
          </form>
        </UiCard>

        <!-- Change Password Section -->
        <UiCard title="Change Password">
          <form class="space-y-4" @submit.prevent="handlePasswordSubmit">
            <div class="space-y-2">
              <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--aq-muted)]">Current Password</label>
              <Password v-model="passwordForm.currentPassword" class="w-full" toggleMask :feedback="false" required />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--aq-muted)]">New Password</label>
                <Password v-model="passwordForm.password" class="w-full" toggleMask required />
              </div>
              <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--aq-muted)]">Confirm Password</label>
                <Password v-model="passwordForm.passwordConfirm" class="w-full" toggleMask :feedback="false" required />
              </div>
            </div>

            <Message v-if="passwordError" severity="error" :closable="false">{{ passwordError }}</Message>
            <Message v-if="passwordSuccess" severity="success" :closable="false">{{ passwordSuccess }}</Message>

            <div class="flex justify-end pt-4 border-t border-[color:var(--aq-border)]">
              <Button type="submit" label="Update Password" icon="pi pi-lock" :loading="passwordLoading" />
            </div>
          </form>
        </UiCard>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import axios from 'axios';
import { apiGet, apiPut } from '../../lib/api';
import { useAuthStore } from '../../stores/auth';
import UiPageHeader from '../../components/ui/UiPageHeader.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiBadge from '../../components/ui/UiBadge.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Password from 'primevue/password';

const auth = useAuthStore();

const formData = ref({
    name: auth.user?.name || '',
    email: auth.user?.email || '',
    phone: auth.user?.phone || '',
    address: auth.user?.address || '',
    job_title: auth.user?.job_title || '',
    department: auth.user?.department || '',
});

const loading = ref(false);
const fetching = ref(true);
const error = ref('');
const success = ref('');
const avatar = ref(null);
const avatarObjectUrl = ref('');

// Password change form
const passwordForm = ref({
    currentPassword: '',
    password: '',
    passwordConfirm: '',
});
const passwordLoading = ref(false);
const passwordError = ref('');
const passwordSuccess = ref('');

const avatarPreviewUrl = computed(() => {
    if (avatarObjectUrl.value) return avatarObjectUrl.value;
    const name = encodeURIComponent(formData.value.name || 'User');
    return `https://ui-avatars.com/api/?name=${name}&background=8B5CF6&color=fff&size=128`;
});

function onAvatarChange(e) {
    const file = e?.target?.files?.[0];
    avatar.value = file || null;

    if (avatarObjectUrl.value) {
        URL.revokeObjectURL(avatarObjectUrl.value);
        avatarObjectUrl.value = '';
    }
    if (file) {
        avatarObjectUrl.value = URL.createObjectURL(file);
    }
}

async function fetchProfile() {
    try {
        fetching.value = true;
        error.value = '';
        const res = await apiGet('/user');
        const u = res?.user || res;
        formData.value = {
            name: u?.name || '',
            email: u?.email || '',
            phone: u?.phone || '',
            address: u?.address || '',
            job_title: u?.job_title || '',
            department: u?.department || '',
        };
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Failed to load profile';
    } finally {
        fetching.value = false;
    }
}

async function handleSubmit() {
    loading.value = true;
    error.value = '';
    success.value = '';

    try {
        const data = new FormData();
        data.append('name', formData.value.name);
        data.append('email', formData.value.email);
        data.append('phone', formData.value.phone || '');
        data.append('address', formData.value.address || '');
        data.append('job_title', formData.value.job_title || '');
        data.append('department', formData.value.department || '');
        if (avatar.value) {
            data.append('avatar', avatar.value);
        }

        const res = await axios.put('/api/user/profile', data);
        const payload = res.data;

        success.value = 'Profile updated successfully';

        if (payload?.user) {
            auth.setSession({ token: auth.token, user: payload.user });
        } else {
            await auth.fetchUser();
        }
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Failed to update profile';
    } finally {
        loading.value = false;
    }
}

async function handlePasswordSubmit() {
    passwordLoading.value = true;
    passwordError.value = '';
    passwordSuccess.value = '';

    if (passwordForm.value.password !== passwordForm.value.passwordConfirm) {
        passwordError.value = 'Passwords do not match.';
        passwordLoading.value = false;
        return;
    }

    if (passwordForm.value.password.length < 8) {
        passwordError.value = 'Password must be at least 8 characters.';
        passwordLoading.value = false;
        return;
    }

    try {
        const res = await apiPut('/user/password', {
            current_password: passwordForm.value.currentPassword,
            password: passwordForm.value.password,
            password_confirmation: passwordForm.value.passwordConfirm,
        });

        if (res?.user) {
            auth.setSession({ token: auth.token, user: res.user });
        }

        passwordSuccess.value = 'Password updated successfully';
        passwordForm.value = {
            currentPassword: '',
            password: '',
            passwordConfirm: '',
        };
    } catch (e) {
        const errors = e?.response?.data?.errors;
        if (errors) {
            const messages = Object.values(errors).flat().join(' ');
            passwordError.value = messages || 'Validation failed';
        } else {
            passwordError.value = e?.response?.data?.message || e?.message || 'Failed to update password';
        }
    } finally {
        passwordLoading.value = false;
    }
}

onMounted(fetchProfile);

onBeforeUnmount(() => {
    if (avatarObjectUrl.value) {
        URL.revokeObjectURL(avatarObjectUrl.value);
    }
});
</script>
