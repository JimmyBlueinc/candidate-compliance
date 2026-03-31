<template>
  <div class="space-y-6">
    <div class="glass-dark rounded-[32px] p-8 border border-white/5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="font-display text-2xl text-white">Admin Intake</h2>
          <p class="text-sm text-[color:var(--p-text-muted-color)] mt-1">Create or update a Web-Lead candidate (tagged as Web-Lead/New).</p>
        </div>
      </div>

      <Message v-if="msg" :severity="msgSeverity" :closable="false" class="mt-4">{{ msg }}</Message>

      <form class="mt-6 space-y-4" @submit.prevent="submit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">First Name</label>
            <InputText v-model="firstName" class="w-full" required size="small" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Last Name</label>
            <InputText v-model="lastName" class="w-full" required size="small" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Email</label>
            <InputText v-model="email" type="email" class="w-full" required size="small" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Phone</label>
            <InputText v-model="phone" class="w-full" required size="small" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Specialty</label>
            <InputText v-model="specialty" class="w-full" required size="small" />
          </div>
        </div>

        <div class="flex justify-end">
          <Button type="submit" label="Submit Lead" size="small" :loading="loading" />
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { apiPost } from '../../lib/api';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';

const firstName = ref('');
const lastName = ref('');
const email = ref('');
const phone = ref('');
const specialty = ref('');

const loading = ref(false);
const msg = ref('');
const msgSeverity = ref('success');

async function submit() {
    loading.value = true;
    msg.value = '';
    try {
        const res = await apiPost('/v1/intake/admin', {
            first_name: firstName.value,
            last_name: lastName.value,
            email: email.value,
            phone: phone.value,
            specialty: specialty.value,
        });

        msgSeverity.value = 'success';
        msg.value = res?.updated ? 'Lead updated.' : 'Lead created.';

        firstName.value = '';
        lastName.value = '';
        email.value = '';
        phone.value = '';
        specialty.value = '';
    } catch (e) {
        msgSeverity.value = 'error';
        msg.value = e?.response?.data?.message || e?.message || 'Failed to submit.';
    } finally {
        loading.value = false;
    }
}
</script>
