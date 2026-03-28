<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <AppPageHeader title="Facilities" subtitle="Manage facilities and provision facility users.">
      <template #actions>
        <AppButton variant="secondary" size="sm" @click="load">
          <RefreshCw class="w-4 h-4" />
          Refresh
        </AppButton>
      </template>
    </AppPageHeader>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <AppStatCard label="Total Facilities" :value="facilities.length" :icon="Building2" />
      <AppStatCard label="Total Users" :value="totalFacilityUsers" :icon="Users" />
      <AppStatCard label="Active Facilities" :value="activeFacilitiesCount" :icon="Building2" />
      <AppStatCard label="Contracts (MSA/SOW)" :value="totalContracts" :icon="FileText" />
    </div>

    <!-- Error Message -->
    <div v-if="error" class="px-4 py-3 rounded-[var(--radius-lg)] bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm">
      {{ error }}
    </div>

    <!-- New Facility Form -->
    <AppCard title="New Facility" subtitle="Add a facility to your organization.">
      <form class="space-y-6" @submit.prevent="createFacility">
        <!-- Basic Info -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Name</label>
            <input v-model="facilityName" type="text" class="app-input" required placeholder="Facility name" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Address</label>
            <input v-model="facilityAddress" type="text" class="app-input" placeholder="Street address" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">City</label>
            <input v-model="facilityCity" type="text" class="app-input" placeholder="City" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">State</label>
            <input v-model="facilityState" type="text" class="app-input" placeholder="State" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Country</label>
            <input v-model="facilityCountry" type="text" class="app-input" placeholder="Country" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Postal Code</label>
            <input v-model="facilityPostalCode" type="text" class="app-input" placeholder="Postal code" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Timezone</label>
            <select v-model="facilityTimezone" class="app-input">
              <option value="">Select timezone</option>
              <option v-for="tz in timezoneOptions" :key="tz.value" :value="tz.value">{{ tz.label }}</option>
            </select>
          </div>
        </div>

        <!-- Contact Info -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Contact Person</label>
            <input v-model="facilityContactPersonName" type="text" class="app-input" placeholder="Contact name" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Contact Email</label>
            <input v-model="facilityContactEmail" type="email" class="app-input" placeholder="email@example.com" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Contact Phone</label>
            <input v-model="facilityContactPhone" type="tel" class="app-input" placeholder="+1 (555) 000-0000" />
          </div>
        </div>

        <!-- Facility Type -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Facility Type</label>
            <select v-model="facilityType" class="app-input">
              <option value="">Select type</option>
              <option v-for="ft in facilityTypeOptions" :key="ft.value" :value="ft.value">{{ ft.label }}</option>
            </select>
          </div>
          <div v-if="facilityType === 'Other'" class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Specify Type</label>
            <input v-model="facilityTypeOther" type="text" class="app-input" placeholder="Enter facility type" />
          </div>
        </div>

        <div class="pt-2">
          <AppButton type="submit" :loading="creatingFacility">
            <Plus class="w-4 h-4" />
            Create Facility
          </AppButton>
        </div>
      </form>
    </AppCard>

    <!-- Facilities Table -->
    <AppCard title="Facilities" subtitle="All registered facilities in your organization.">
      <template #actions>
        <AppButton v-if="facilities.length > 0 && featureFlagStore.enabled('dashboard.advanced_exports', true)" variant="secondary" size="sm" @click="exportFacilities">
          <i class="pi pi-download text-xs" />
          Export CSV
        </AppButton>
        <AppButton v-if="facilities.length > 0" size="sm" @click="openCreateFacilityUser">
          <UserPlus class="w-4 h-4" />
          Create Facility User
        </AppButton>
      </template>

      <div v-if="facilities.length > 0" class="mb-4 flex flex-col lg:flex-row gap-3 lg:items-center lg:justify-between">
        <div class="flex-1 max-w-md">
          <input
            v-model="searchQuery"
            type="text"
            class="app-input"
            placeholder="Search facilities, city, contact..."
          />
        </div>
        <div class="flex items-center gap-2">
          <select v-model="sortBy" class="app-input min-w-[170px]">
            <option value="name">Sort: Name</option>
            <option value="users_count">Sort: Users</option>
            <option value="contracts_count">Sort: Contracts</option>
            <option value="city">Sort: City</option>
          </select>
          <select v-model="sortDir" class="app-input min-w-[120px]">
            <option value="asc">Ascending</option>
            <option value="desc">Descending</option>
          </select>
        </div>
      </div>

      <div v-if="loading" class="py-8">
        <div class="space-y-3">
          <AppSkeleton v-for="i in 5" :key="i" variant="text" />
        </div>
      </div>

      <AppEmpty
        v-else-if="facilities.length === 0"
        title="No facilities yet"
        description="Create your first facility using the form above."
        :icon="Building2"
      />

      <div v-else class="overflow-x-auto -mx-6">
        <table class="w-full">
          <thead>
            <tr class="border-b border-[color:var(--aq-border)]">
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Facility</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Type</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Timezone</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Contact</th>
              <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Users</th>
              <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Contracts</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[color:var(--aq-border)]">
            <tr
              v-for="facility in pagedFacilities"
              :key="facility.id"
              class="hover:bg-[color:var(--aq-surface-2)] transition-colors cursor-pointer"
              @click="openFacilityDetails(facility)"
            >
              <td class="px-6 py-4">
                <div class="font-semibold text-[color:var(--aq-fg)]">{{ facility.name }}</div>
                <div class="text-xs text-[color:var(--aq-muted)] mt-0.5">
                  {{ [facility.address, facility.city, facility.state, facility.postal_code, facility.country].filter(Boolean).join(', ') || 'No address' }}
                </div>
              </td>
              <td class="px-6 py-4">
                <AppBadge variant="default" size="sm">
                  {{ facility.facility_type === 'Other' ? (facility.facility_type_other || 'Other') : (facility.facility_type || '—') }}
                </AppBadge>
              </td>
              <td class="px-6 py-4 text-sm text-[color:var(--aq-muted)]">
                {{ facility.timezone || '—' }}
              </td>
              <td class="px-6 py-4">
                <div class="text-sm font-medium text-[color:var(--aq-fg)]">{{ facility.contact_person_name || '—' }}</div>
                <div class="text-xs text-[color:var(--aq-muted)]">{{ facility.contact_email || '—' }}</div>
                <div class="mt-1">
                  <AppBadge :variant="Number(facility.contracts_count || 0) > 0 ? 'success' : 'default'" size="sm">
                    {{ Number(facility.contracts_count || 0) > 0 ? 'MSA Ready' : 'No Contracts' }}
                  </AppBadge>
                </div>
              </td>
              <td class="px-6 py-4 text-right">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-[color:var(--aq-primary)]/10 text-sm font-semibold text-[color:var(--aq-primary)]">
                  {{ facility.users_count || 0 }}
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <AppButton
                  variant="secondary"
                  size="sm"
                  @click.stop="openFacilityContracts(facility)"
                >
                  {{ facility.contracts_count || 0 }}
                </AppButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="filteredFacilities.length > pageSize" class="mt-4 flex items-center justify-between text-xs text-[color:var(--aq-muted)]">
        <span>
          Showing {{ pageStart + 1 }}-{{ Math.min(pageStart + pageSize, filteredFacilities.length) }} of {{ filteredFacilities.length }}
        </span>
        <div class="flex items-center gap-2">
          <AppButton variant="secondary" size="sm" :disabled="currentPage === 1" @click="currentPage -= 1">Prev</AppButton>
          <span>Page {{ currentPage }} / {{ totalPages }}</span>
          <AppButton variant="secondary" size="sm" :disabled="currentPage >= totalPages" @click="currentPage += 1">Next</AppButton>
        </div>
      </div>
    </AppCard>

    <!-- Create Facility User Modal -->
    <AppModal v-model="createUserOpen" title="Create Facility User" subtitle="Add a new user to a facility.">
      <form class="space-y-4" @submit.prevent="submitFacilityUser">
        <div class="space-y-2">
          <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Facility</label>
          <select v-model="selectedFacilityId" class="app-input">
            <option value="">Select facility</option>
            <option v-for="f in facilityOptions" :key="f.value" :value="f.value">{{ f.label }}</option>
          </select>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Name</label>
            <input v-model="newUserName" type="text" class="app-input" required placeholder="Full name" />
          </div>
          <div class="space-y-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)]">Email</label>
            <input v-model="newUserEmail" type="email" class="app-input" required placeholder="email@example.com" />
          </div>
        </div>
      </form>
      <template #footer>
        <div class="flex items-center gap-3 justify-end">
          <AppButton variant="ghost" @click="createUserOpen = false">Cancel</AppButton>
          <AppButton :loading="creatingUser" @click="submitFacilityUser">Create User</AppButton>
        </div>
      </template>
    </AppModal>

    <!-- Credentials Modal -->
    <AppModal v-model="credentialsDialogOpen" title="Facility Login Details" subtitle="Save these credentials securely.">
      <div class="space-y-4">
        <div class="p-4 rounded-[var(--radius-lg)] bg-[color:var(--aq-surface-2)] border border-[color:var(--aq-border)]">
          <div class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)] mb-1">Email</div>
          <div class="font-mono text-[color:var(--aq-fg)] break-all">{{ createdCredentials?.email }}</div>
        </div>
        <div class="p-4 rounded-[var(--radius-lg)] bg-[color:var(--aq-surface-2)] border border-[color:var(--aq-border)]">
          <div class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)] mb-1">Temporary Password</div>
          <div class="font-mono text-[color:var(--aq-fg)] break-all">{{ createdCredentials?.tempPassword }}</div>
        </div>

        <div v-if="createdCredentials?.emailSent === true" class="flex items-center gap-2 text-sm text-emerald-400">
          <CheckCircle class="w-4 h-4" />
          Email sent to user.
        </div>
        <div v-else-if="createdCredentials?.emailSent === false" class="flex items-center gap-2 text-sm text-amber-400">
          <AlertCircle class="w-4 h-4" />
          Email not sent. Use the credentials above.
        </div>

        <p class="text-xs text-[color:var(--aq-muted)]">
          These credentials are only shown once. Copy them now.
        </p>
      </div>
      <template #footer>
        <div class="flex items-center gap-3 justify-end">
          <AppButton variant="secondary" @click="copyCredentials">
            <Copy class="w-4 h-4" />
            Copy
          </AppButton>
          <AppButton variant="ghost" @click="credentialsDialogOpen = false">Done</AppButton>
        </div>
      </template>
    </AppModal>

    <!-- Facility Details Modal -->
    <AppModal v-model="facilityDetailsOpen" title="Facility Details" size="lg">
      <div v-if="selectedFacility" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="p-4 rounded-[var(--radius-lg)] bg-[color:var(--aq-surface-2)] border border-[color:var(--aq-border)]">
            <div class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)] mb-1">Name</div>
            <div class="font-semibold text-[color:var(--aq-fg)]">{{ selectedFacility.name || '—' }}</div>
          </div>
          <div class="p-4 rounded-[var(--radius-lg)] bg-[color:var(--aq-surface-2)] border border-[color:var(--aq-border)]">
            <div class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)] mb-1">Type</div>
            <div class="font-semibold text-[color:var(--aq-fg)]">
              {{ selectedFacility.facility_type === 'Other' ? (selectedFacility.facility_type_other || 'Other') : (selectedFacility.facility_type || '—') }}
            </div>
          </div>
          <div class="p-4 rounded-[var(--radius-lg)] bg-[color:var(--aq-surface-2)] border border-[color:var(--aq-border)]">
            <div class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)] mb-1">Address</div>
            <div class="font-semibold text-[color:var(--aq-fg)]">
              {{ [selectedFacility.address, selectedFacility.city, selectedFacility.state, selectedFacility.postal_code, selectedFacility.country].filter(Boolean).join(', ') || '—' }}
            </div>
          </div>
          <div class="p-4 rounded-[var(--radius-lg)] bg-[color:var(--aq-surface-2)] border border-[color:var(--aq-border)]">
            <div class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)] mb-1">Timezone</div>
            <div class="font-semibold text-[color:var(--aq-fg)]">{{ selectedFacility.timezone || '—' }}</div>
          </div>
          <div class="p-4 rounded-[var(--radius-lg)] bg-[color:var(--aq-surface-2)] border border-[color:var(--aq-border)]">
            <div class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)] mb-1">Contact Person</div>
            <div class="font-semibold text-[color:var(--aq-fg)]">{{ selectedFacility.contact_person_name || '—' }}</div>
          </div>
          <div class="p-4 rounded-[var(--radius-lg)] bg-[color:var(--aq-surface-2)] border border-[color:var(--aq-border)]">
            <div class="text-xs font-semibold uppercase tracking-wider text-[color:var(--aq-muted)] mb-1">Contact</div>
            <div class="font-semibold text-[color:var(--aq-fg)]">{{ selectedFacility.contact_email || '—' }}</div>
            <div class="text-xs text-[color:var(--aq-muted)] mt-1">{{ selectedFacility.contact_phone || '—' }}</div>
          </div>
        </div>
      </div>
      <template #footer>
        <div class="flex items-center gap-3 justify-end">
          <AppButton @click="openCreateFacilityUserFromDetails">
            <UserPlus class="w-4 h-4" />
            Create Facility User
          </AppButton>
          <AppButton variant="ghost" @click="facilityDetailsOpen = false">Close</AppButton>
        </div>
      </template>
    </AppModal>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { apiGet, apiPost } from '../../lib/api';
import { useFeatureFlagStore } from '../../stores/featureFlags';
import { Building2, Users, FileText, Plus, UserPlus, RefreshCw, CheckCircle, AlertCircle, Copy } from 'lucide-vue-next';
import AppCard from '../../components/ui/AppCard.vue';
import AppStatCard from '../../components/ui/AppStatCard.vue';
import AppPageHeader from '../../components/ui/AppPageHeader.vue';
import AppButton from '../../components/ui/AppButton.vue';
import AppBadge from '../../components/ui/AppBadge.vue';
import AppModal from '../../components/ui/AppModal.vue';
import AppEmpty from '../../components/ui/AppEmpty.vue';
import AppSkeleton from '../../components/ui/AppSkeleton.vue';

const router = useRouter();
const featureFlagStore = useFeatureFlagStore();

const facilities = ref([]);
const loading = ref(true);
const error = ref('');
const searchQuery = ref('');
const sortBy = ref('name');
const sortDir = ref('asc');
const currentPage = ref(1);
const pageSize = 10;

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

const filteredFacilities = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return facilities.value;
  return facilities.value.filter((f) =>
    `${f.name || ''} ${f.city || ''} ${f.state || ''} ${f.contact_person_name || ''} ${f.contact_email || ''}`
      .toLowerCase()
      .includes(q)
  );
});

const sortedFacilities = computed(() => {
  const key = sortBy.value;
  const dir = sortDir.value === 'desc' ? -1 : 1;
  return [...filteredFacilities.value].sort((a, b) => {
    const av = a?.[key] ?? '';
    const bv = b?.[key] ?? '';
    if (typeof av === 'number' || typeof bv === 'number') {
      return (Number(av) - Number(bv)) * dir;
    }
    return String(av).localeCompare(String(bv)) * dir;
  });
});

const totalPages = computed(() => Math.max(1, Math.ceil(sortedFacilities.value.length / pageSize)));
const pageStart = computed(() => (currentPage.value - 1) * pageSize);
const pagedFacilities = computed(() => sortedFacilities.value.slice(pageStart.value, pageStart.value + pageSize));

watch([searchQuery, sortBy, sortDir], () => {
  currentPage.value = 1;
});

const totalFacilityUsers = computed(() => {
  return facilities.value.reduce((acc, f) => acc + Number(f.users_count || 0), 0);
});

const activeFacilitiesCount = computed(() => {
  return facilities.value.length;
});

const totalContracts = computed(() => {
  return facilities.value.reduce((acc, f) => acc + Number(f.contracts_count || 0), 0);
});

async function load() {
  loading.value = true;
  error.value = '';
  try {
    const res = await apiGet('/v1/facilities');
    const payload = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : []);
    facilities.value = payload;

    if (!selectedFacilityId.value && facilities.value.length > 0) {
      selectedFacilityId.value = String(facilities.value[0].id);
    }
    if (currentPage.value > totalPages.value) currentPage.value = totalPages.value;
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

    // Reset form
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
  router.push({
    name: 'dashboard.facilities.detail',
    params: { id: facility.id },
  });
}

function exportFacilities() {
  window.open('/api/v1/facilities/export', '_blank');
}

function openFacilityContracts(facility) {
  router.push({
    name: 'dashboard.facilities.detail',
    params: { id: facility.id },
    query: { tab: 'contracts' },
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

<style scoped>
.app-input {
  width: 100%;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  border-radius: var(--radius-lg);
  border: 1px solid var(--aq-border);
  background: var(--aq-surface-2);
  color: var(--aq-fg);
  transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
}

.app-input::placeholder {
  color: var(--aq-muted);
}

.app-input:focus {
  outline: none;
  border-color: color-mix(in srgb, var(--aq-primary) 50%, transparent);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--aq-primary) 10%, transparent);
}
</style>
