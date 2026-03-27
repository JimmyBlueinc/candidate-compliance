<template>
  <div class="space-y-8">
    <UiPageHeader title="Facilities" subtitle="Create facilities and provision facility users.">
      <template #actions>
        <Button label="Reload" icon="pi pi-refresh" severity="secondary" outlined size="small" @click="load" />
      </template>
    </UiPageHeader>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <UiStatCard title="Total Facilities" :value="facilities.length">
        <template #icon><Building2 class="w-3.5 h-3.5" /></template>
      </UiStatCard>
      <UiStatCard title="Total Users" :value="totalFacilityUsers">
        <template #icon><Users class="w-3.5 h-3.5" /></template>
      </UiStatCard>
      <UiStatCard title="Active Facilities" :value="facilities.length">
        <template #icon><Building2 class="w-3.5 h-3.5" /></template>
      </UiStatCard>
      <UiStatCard title="Growth" value="+12%" :trend="12">
        <template #icon><Activity class="w-3.5 h-3.5" /></template>
      </UiStatCard>
    </div>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <UiCard title="New Facility">
      <p class="text-sm text-[color:var(--aq-muted)] mb-6">Add a facility to your organization.</p>

      <form class="space-y-3" @submit.prevent="createFacility">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Name</label>
            <InputText v-model="facilityName" class="w-full" required size="small" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Address</label>
            <InputText v-model="facilityAddress" class="w-full" size="small" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">City</label>
            <InputText v-model="facilityCity" class="w-full" size="small" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">State</label>
            <InputText v-model="facilityState" class="w-full" size="small" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Country</label>
            <InputText v-model="facilityCountry" class="w-full" size="small" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Postal Code</label>
            <InputText v-model="facilityPostalCode" class="w-full" size="small" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Contact Email</label>
            <InputText v-model="facilityContactEmail" class="w-full" size="small" type="email" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Contact Phone</label>
            <InputText v-model="facilityContactPhone" class="w-full" size="small" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Contact Person Name</label>
            <InputText v-model="facilityContactPersonName" class="w-full" size="small" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Timezone</label>
            <Dropdown v-model="facilityTimezone" :options="timezoneOptions" optionLabel="label" optionValue="value" class="w-full" filter size="small" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Facility Type</label>
            <Dropdown v-model="facilityType" :options="facilityTypeOptions" optionLabel="label" optionValue="value" class="w-full" size="small" />
          </div>
          <div v-if="facilityType === 'Other'" class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Other Facility Type</label>
            <InputText v-model="facilityTypeOther" class="w-full" size="small" />
          </div>
        </div>

        <div>
          <Button :loading="creatingFacility" type="submit" label="Create Facility" size="small" />
        </div>
      </form>
    </UiCard>

    <UiCard title="Facility List">
      <template #header>
        <div class="flex items-center gap-2">
          <Button v-if="facilities.length > 0" label="Create Facility User" size="small" @click="openCreateFacilityUser" />
          <Button label="Refresh" size="small" severity="secondary" outlined @click="load" />
        </div>
      </template>

      <DataTable :value="facilities" :loading="loading" dataKey="id" stripedRows responsiveLayout="scroll" size="small">
        <Column field="name" header="Facility">
          <template #body="{ data }">
            <button type="button" class="flex flex-col text-left" @click="openFacilityDetails(data)">
              <span class="font-semibold text-primary hover:underline">{{ data.name }}</span>
              <span class="text-xs text-[color:var(--aq-muted)]">{{ [data.address, data.city, data.state, data.postal_code, data.country].filter(Boolean).join(', ') || '—' }}</span>
            </button>
          </template>
        </Column>
        <Column header="Type">
          <template #body="{ data }">
            <span class="text-sm">{{ (data.facility_type === 'Other' ? (data.facility_type_other || 'Other') : (data.facility_type || '—')) }}</span>
          </template>
        </Column>
        <Column header="Timezone">
          <template #body="{ data }">
            <span class="text-sm">{{ data.timezone || '—' }}</span>
          </template>
        </Column>
        <Column header="Contact">
          <template #body="{ data }">
            <div class="flex flex-col">
              <span class="text-sm font-medium">{{ data.contact_person_name || '—' }}</span>
              <span class="text-xs text-[color:var(--aq-muted)]">{{ data.contact_email || '—' }}</span>
              <span class="text-[10px] text-[color:var(--aq-muted)] font-mono">{{ data.contact_phone || '—' }}</span>
            </div>
          </template>
        </Column>
        <Column field="users_count" header="Users" style="width: 1%; white-space: nowrap" />

        <template #empty>
          <div class="py-12 text-center text-[color:var(--aq-muted)]">No facilities yet</div>
        </template>
      </DataTable>
    </UiCard>

    <Dialog v-model:visible="createUserOpen" modal header="Create Facility User" :style="{ width: 'min(900px, 95vw)' }">
      <form class="space-y-4" @submit.prevent="submitFacilityUser">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Facility</label>
            <Dropdown v-model="selectedFacilityId" :options="facilityOptions" optionLabel="label" optionValue="value" class="w-full" filter size="small" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Name</label>
            <InputText v-model="newUserName" class="w-full" required size="small" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Email</label>
            <InputText v-model="newUserEmail" type="email" class="w-full" required size="small" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Role</label>
            <Dropdown v-model="newUserRole" :options="[{ label: 'Facility', value: 'facility' }]" optionLabel="label" optionValue="value" class="w-full" size="small" />
          </div>
        </div>

        <div class="flex gap-2 justify-end pt-2">
          <Button type="button" label="Cancel" severity="secondary" outlined size="small" @click="createUserOpen = false" />
          <Button type="submit" label="Create" :loading="creatingUser" size="small" />
        </div>
      </form>
    </Dialog>

    <Dialog v-model:visible="credentialsDialogOpen" modal header="Facility Login Details" :style="{ width: 'min(700px, 95vw)' }">
      <div class="space-y-3">
        <div class="rounded-2xl border border-[color:var(--p-surface-border)] p-3">
          <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Email</div>
          <div class="mt-1 font-semibold break-all">{{ createdCredentials?.email }}</div>
        </div>
        <div class="rounded-2xl border border-[color:var(--p-surface-border)] p-3">
          <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Temporary Password</div>
          <div class="mt-1 font-mono break-all">{{ createdCredentials?.tempPassword }}</div>
        </div>

        <div v-if="createdCredentials?.emailSent === true" class="text-xs text-emerald-600">
          Email sent.
        </div>
        <div v-else-if="createdCredentials?.emailSent === false" class="text-xs text-amber-600">
          Email not sent. Use test login details above.
        </div>

        <div class="text-xs text-[color:var(--p-text-muted-color)]">
          These credentials are only shown once. Copy them now.
        </div>

        <div class="flex gap-2 justify-end">
          <Button type="button" label="Copy" size="small" @click="copyCredentials" />
          <Button type="button" label="Done" severity="secondary" outlined size="small" @click="credentialsDialogOpen = false" />
        </div>
      </div>
    </Dialog>

    <Dialog v-model:visible="facilityDetailsOpen" modal header="Facility Details" :style="{ width: 'min(900px, 95vw)' }">
      <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="rounded-2xl border border-[color:var(--p-surface-border)] p-3">
            <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Name</div>
            <div class="mt-1 font-semibold">{{ selectedFacility?.name || '—' }}</div>
          </div>
          <div class="rounded-2xl border border-[color:var(--p-surface-border)] p-3">
            <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Type</div>
            <div class="mt-1 font-semibold">{{ (selectedFacility?.facility_type === 'Other' ? (selectedFacility?.facility_type_other || 'Other') : (selectedFacility?.facility_type || '—')) }}</div>
          </div>
          <div class="rounded-2xl border border-[color:var(--p-surface-border)] p-3">
            <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Address</div>
            <div class="mt-1 font-semibold">{{ [selectedFacility?.address, selectedFacility?.city, selectedFacility?.state, selectedFacility?.postal_code, selectedFacility?.country].filter(Boolean).join(', ') || '—' }}</div>
          </div>
          <div class="rounded-2xl border border-[color:var(--p-surface-border)] p-3">
            <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Timezone</div>
            <div class="mt-1 font-semibold">{{ selectedFacility?.timezone || '—' }}</div>
          </div>
          <div class="rounded-2xl border border-[color:var(--p-surface-border)] p-3">
            <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Contact Person</div>
            <div class="mt-1 font-semibold">{{ selectedFacility?.contact_person_name || '—' }}</div>
          </div>
          <div class="rounded-2xl border border-[color:var(--p-surface-border)] p-3">
            <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Contact</div>
            <div class="mt-1 font-semibold break-all">{{ selectedFacility?.contact_email || '—' }}</div>
            <div class="text-xs text-[color:var(--p-text-muted-color)]">{{ selectedFacility?.contact_phone || '—' }}</div>
          </div>
        </div>

        <div class="flex gap-2 justify-end">
          <Button type="button" label="Create Facility User" size="small" @click="openCreateFacilityUserFromDetails" />
          <Button type="button" label="Close" severity="secondary" outlined size="small" @click="facilityDetailsOpen = false" />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { apiGet, apiPost } from '../../lib/api';
import UiCard from '../../components/ui/UiCard.vue';
import UiStatCard from '../../components/ui/UiStatCard.vue';
import UiPageHeader from '../../components/ui/UiPageHeader.vue';
import { Building2, Users, Activity } from 'lucide-vue-next';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Button from 'primevue/button';

const router = useRouter();

const facilities = ref([]);
const loading = ref(true);
const error = ref('');

const facilityName = ref('');
const facilityAddress = ref('');
const facilityCity = ref('');
const facilityState = ref('');
const facilityCountry = ref('');
const facilityPostalCode = ref('');
const facilityTimezone = ref('');
const facilityType = ref('');
const facilityTypeOther = ref('');
const facilityContactPersonName = ref('');
const facilityContactEmail = ref('');
const facilityContactPhone = ref('');
const creatingFacility = ref(false);

const facilityTypeOptions = [
  { label: 'Hospital', value: 'Hospital' },
  { label: 'Clinic', value: 'Clinic' },
  { label: 'Long-Term Care (LTC)', value: 'Long-Term Care (LTC)' },
  { label: 'Skilled Nursing Facility (SNF)', value: 'Skilled Nursing Facility (SNF)' },
  { label: 'Assisted Living', value: 'Assisted Living' },
  { label: 'Rehab Center', value: 'Rehab Center' },
  { label: 'Home Health', value: 'Home Health' },
  { label: 'Urgent Care', value: 'Urgent Care' },
  { label: 'Surgery Center', value: 'Surgery Center' },
  { label: 'Other', value: 'Other' },
];

const timezoneOptions = [
  { label: 'UTC', value: 'UTC' },
  { label: 'Africa/Lagos', value: 'Africa/Lagos' },
  { label: 'Europe/London', value: 'Europe/London' },
  { label: 'Europe/Paris', value: 'Europe/Paris' },
  { label: 'America/New_York', value: 'America/New_York' },
  { label: 'America/Chicago', value: 'America/Chicago' },
  { label: 'America/Denver', value: 'America/Denver' },
  { label: 'America/Los_Angeles', value: 'America/Los_Angeles' },
];

const createUserOpen = ref(false);
const selectedFacilityId = ref('');
const newUserName = ref('');
const newUserEmail = ref('');
const newUserRole = ref('facility');
const creatingUser = ref(false);

const credentialsDialogOpen = ref(false);
const createdCredentials = ref(null);

const facilityDetailsOpen = ref(false);
const selectedFacility = ref(null);

const facilityOptions = computed(() => {
  return facilities.value.map((f) => ({ label: f.name, value: String(f.id) }));
});

const totalFacilityUsers = computed(() => {
  return facilities.value.reduce((acc, f) => acc + Number(f.users_count || 0), 0);
});

async function load() {
  loading.value = true;
  error.value = '';
  try {
    const res = await apiGet('/v1/facilities');
    facilities.value = Array.isArray(res?.data) ? res.data : [];

    if (!selectedFacilityId.value && facilities.value.length > 0) {
      selectedFacilityId.value = String(facilities.value[0].id);
    }
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to load facilities.';
    facilities.value = [];
  } finally {
    loading.value = false;
  }
}

async function createFacility() {
  try {
    creatingFacility.value = true;
    error.value = '';

    const res = await apiPost('/v1/facilities', {
      name: facilityName.value,
      address: facilityAddress.value || null,
      city: facilityCity.value || null,
      state: facilityState.value || null,
      country: facilityCountry.value || null,
      postal_code: facilityPostalCode.value || null,
      timezone: facilityTimezone.value || null,
      facility_type: facilityType.value || null,
      facility_type_other: facilityType.value === 'Other' ? (facilityTypeOther.value || null) : null,
      contact_person_name: facilityContactPersonName.value || null,
      contact_email: facilityContactEmail.value || null,
      contact_phone: facilityContactPhone.value || null,
    });

    const created = res?.data?.facility || res?.facility || null;

    facilityName.value = '';
    facilityAddress.value = '';
    facilityCity.value = '';
    facilityState.value = '';
    facilityCountry.value = '';
    facilityPostalCode.value = '';
    facilityTimezone.value = '';
    facilityType.value = '';
    facilityTypeOther.value = '';
    facilityContactPersonName.value = '';
    facilityContactEmail.value = '';
    facilityContactPhone.value = '';

    await load();

    const createdId = created?.id ? String(created.id) : '';
    if (createdId) {
      selectedFacilityId.value = createdId;
      newUserName.value = created?.contact_person_name || created?.name || '';
      newUserEmail.value = created?.contact_email || '';
      newUserRole.value = 'facility';
      createUserOpen.value = true;
    }
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to create facility.';
  } finally {
    creatingFacility.value = false;
  }
}

function openFacilityDetails(facility) {
  // Navigate to facility detail workspace
  router.push({ 
    name: 'dashboard.facilities.detail', 
    params: { id: facility.id } 
  });
}

function openCreateFacilityUserFromDetails() {
  if (selectedFacility.value?.id) {
    selectedFacilityId.value = String(selectedFacility.value.id);
  }
  newUserName.value = selectedFacility.value?.contact_person_name || selectedFacility.value?.name || '';
  newUserEmail.value = selectedFacility.value?.contact_email || '';
  newUserRole.value = 'facility';
  facilityDetailsOpen.value = false;
  createUserOpen.value = true;
}

function openCreateFacilityUser() {
  createUserOpen.value = true;
  newUserName.value = '';
  newUserEmail.value = '';
  newUserRole.value = 'facility';
  error.value = '';
}

async function submitFacilityUser() {
  if (!selectedFacilityId.value) return;

  try {
    creatingUser.value = true;
    error.value = '';

    const res = await apiPost(`/v1/facilities/${encodeURIComponent(String(selectedFacilityId.value))}/users`, {
      name: newUserName.value,
      email: newUserEmail.value,
      role: newUserRole.value,
    });

    const payload = res?.data || res;
    const creds = payload?.credentials || null;

    if (creds) {
      createdCredentials.value = {
        email: creds.email,
        tempPassword: creds.temp_password,
        emailSent: payload?.email_sent,
      };
      credentialsDialogOpen.value = true;
    }

    createUserOpen.value = false;
    await load();
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to create facility user.';
  } finally {
    creatingUser.value = false;
  }
}

async function copyCredentials() {
  const email = createdCredentials.value?.email || '';
  const temp = createdCredentials.value?.tempPassword || '';
  const text = `Email: ${email}\nTemp Password: ${temp}`;
  try {
    await navigator.clipboard.writeText(text);
  } catch {
    // ignore
  }
}

onMounted(load);
</script>
