<template>
  <div class="min-h-screen bg-[var(--app-bg)] text-[var(--app-fg)]">
    <div class="max-w-5xl mx-auto px-6 py-10">
      <!-- Loading state -->
      <div v-if="loading" class="flex items-center justify-center py-20">
        <i class="pi pi-spin pi-spinner text-3xl text-[color:var(--p-primary-color)]"></i>
      </div>

      <!-- Error state -->
      <div v-else-if="error" class="text-center py-20">
        <div class="text-red-400 mb-4">{{ error }}</div>
        <Button label="Try Again" @click="fetchData" />
      </div>

      <!-- Main content -->
      <div v-else>
        <!-- Hero Section -->
        <div class="text-center mb-12">
          <img
            v-if="organization?.logo_path"
            :src="`/api/brand/logo/${organization.id}`"
            :alt="organization.name"
            class="w-20 h-20 rounded-2xl object-cover shadow-lg mx-auto mb-6"
          />
          <h1 class="text-4xl font-display font-bold mb-3 text-[color:var(--aq-fg)]">
            {{ organization?.name || 'Welcome' }}
          </h1>
          <p class="text-lg text-[color:var(--p-text-muted-color)] max-w-2xl mx-auto">
            Your next healthcare opportunity starts here. Join our talent network or browse open positions.
          </p>
        </div>

        <!-- CTA Cards -->
        <div class="grid md:grid-cols-2 gap-6 mb-12">
          <!-- Join Talent Network -->
          <div class="glass-dark rounded-2xl border border-white/5 p-8 hover:border-[color:var(--p-primary-color)]/30 transition-colors">
            <div class="flex items-start gap-5">
              <div class="w-14 h-14 rounded-xl bg-[color:var(--p-primary-color)]/10 flex items-center justify-center shrink-0">
                <i class="pi pi-users text-2xl text-[color:var(--p-primary-color)]"></i>
              </div>
              <div class="flex-1">
                <h2 class="text-xl font-semibold mb-2">Join Our Talent Network</h2>
                <p class="text-[color:var(--p-text-muted-color)] mb-4">
                  Register to get notified about new opportunities. No job selection required.
                </p>
                <Button
                  label="Register Now"
                  icon="pi pi-arrow-right"
                  iconPos="right"
                  class="w-full md:w-auto"
                  @click="showRegisterModal = true"
                />
              </div>
            </div>
          </div>

          <!-- Browse Jobs -->
          <div class="glass-dark rounded-2xl border border-white/5 p-8 hover:border-emerald-500/30 transition-colors">
            <div class="flex items-start gap-5">
              <div class="w-14 h-14 rounded-xl bg-emerald-500/10 flex items-center justify-center shrink-0">
                <i class="pi pi-briefcase text-2xl text-emerald-400"></i>
              </div>
              <div class="flex-1">
                <h2 class="text-xl font-semibold mb-2">Browse Open Positions</h2>
                <p class="text-[color:var(--p-text-muted-color)] mb-4">
                  View available jobs and apply directly. Complete your profile first.
                </p>
                <Button
                  label="View Jobs"
                  icon="pi pi-arrow-down"
                  iconPos="right"
                  severity="secondary"
                  class="w-full md:w-auto"
                  @click="scrollToJobs"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Jobs Section -->
        <div ref="jobsSection" class="mt-8">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-semibold">Open Positions</h2>
            <span class="text-sm text-[color:var(--p-text-muted-color)]">{{ jobs.length }} jobs available</span>
          </div>

          <!-- Empty state -->
          <div v-if="jobs.length === 0" class="text-center py-16 glass-dark rounded-2xl">
            <div class="w-16 h-16 rounded-full bg-[color:var(--p-text-muted-color)]/10 flex items-center justify-center mx-auto mb-4">
              <i class="pi pi-briefcase text-2xl text-[color:var(--p-text-muted-color)]"></i>
            </div>
            <div class="text-[color:var(--p-text-muted-color)] mb-2">No open positions</div>
            <p class="text-sm text-[color:var(--p-text-muted-color)]">
              Check back later or join our talent network to get notified.
            </p>
          </div>

          <!-- Job list -->
          <div v-else class="space-y-4">
            <div
              v-for="job in jobs"
              :key="job.id"
              class="glass-dark rounded-2xl border border-white/5 p-6 hover:border-white/10 transition-colors"
            >
              <div class="flex justify-between items-start gap-4">
                <div class="flex-1">
                  <h3 class="text-lg font-semibold">{{ job.title }}</h3>
                  <p class="mt-1 text-sm text-[color:var(--p-text-muted-color)]">
                    {{ job.specialty }} • {{ job.facility_city || '' }}{{ job.facility_city && job.facility_state ? ', ' : '' }}{{ job.facility_state || '' }}
                  </p>
                  <div class="mt-3 flex flex-wrap gap-3 text-xs text-[color:var(--p-text-muted-color)]">
                    <span v-if="job.pay_rate" class="flex items-center gap-1">
                      <i class="pi pi-dollar"></i>{{ job.pay_rate }}/hr
                    </span>
                    <span v-if="job.work_mode" class="flex items-center gap-1">
                      <i class="pi pi-building"></i>{{ job.work_mode }}
                    </span>
                    <span class="flex items-center gap-1">
                      <i class="pi pi-calendar"></i>Posted {{ formatDate(job.created_at) }}
                    </span>
                  </div>
                </div>
                <Button
                  label="Apply"
                  size="small"
                  @click="applyToJob(job.id)"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Phase 1 Registration Modal -->
    <Dialog
      v-model:visible="showRegisterModal"
      modal
      header="Join Talent Network"
      :style="{ width: '500px' }"
    >
      <form @submit.prevent="submitRegistration" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm mb-1">First Name *</label>
            <InputText v-model="registerForm.first_name" class="w-full" required />
          </div>
          <div>
            <label class="block text-sm mb-1">Last Name *</label>
            <InputText v-model="registerForm.last_name" class="w-full" required />
          </div>
        </div>
        <div>
          <label class="block text-sm mb-1">Email *</label>
          <InputText v-model="registerForm.email" type="email" class="w-full" required />
        </div>
        <div>
          <label class="block text-sm mb-1">Phone *</label>
          <InputText v-model="registerForm.phone" type="tel" class="w-full" required />
        </div>
        <div>
          <label class="block text-sm mb-1">Role / Specialty *</label>
          <Dropdown
            v-model="registerForm.role"
            :options="roleOptions"
            placeholder="Select your role"
            class="w-full"
            required
          />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm mb-1">Years of Experience</label>
            <InputText v-model="registerForm.years_experience" class="w-full" />
          </div>
          <div>
            <label class="block text-sm mb-1">Availability</label>
            <Dropdown
              v-model="registerForm.availability"
              :options="availabilityOptions"
              placeholder="Select availability"
              class="w-full"
            />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm mb-1">City</label>
            <InputText v-model="registerForm.city" class="w-full" />
          </div>
          <div>
            <label class="block text-sm mb-1">State</label>
            <InputText v-model="registerForm.state" class="w-full" />
          </div>
        </div>

        <div v-if="registerError" class="text-red-400 text-sm">{{ registerError }}</div>

        <div class="flex justify-end gap-2 pt-4">
          <Button label="Cancel" severity="secondary" @click="showRegisterModal = false" />
          <Button type="submit" label="Register" :loading="registerLoading" />
        </div>
      </form>
    </Dialog>

    <!-- Registration Success Modal -->
    <Dialog
      v-model:visible="showSuccessModal"
      modal
      header="Registration Successful"
      :style="{ width: '450px' }"
    >
      <div class="text-center py-4">
        <div class="w-16 h-16 rounded-full bg-emerald-500/10 flex items-center justify-center mx-auto mb-4">
          <i class="pi pi-check text-3xl text-emerald-400"></i>
        </div>
        <h3 class="text-lg font-semibold">Welcome, {{ registeredCandidate?.name }}!</h3>
        <p class="mt-2 text-[color:var(--p-text-muted-color)]">
          Your profile has been created. Complete your full profile to apply for jobs.
        </p>
      </div>
      <div class="flex justify-end gap-2 pt-4">
        <Button label="Complete Profile" @click="goToProfile" />
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const orgSlug = route.params.orgSlug;

const loading = ref(true);
const error = ref(null);
const organization = ref(null);
const jobs = ref([]);
const jobsSection = ref(null);

// Registration
const showRegisterModal = ref(false);
const showSuccessModal = ref(false);
const registerLoading = ref(false);
const registerError = ref(null);
const registeredCandidate = ref(null);

const registerForm = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  role: '',
  years_experience: '',
  availability: '',
  city: '',
  state: '',
});

const roleOptions = [
  'Registered Nurse (RN)',
  'Licensed Practical Nurse (LPN)',
  'Certified Nursing Assistant (CNA)',
  'Nurse Practitioner (NP)',
  'Physician Assistant (PA)',
  'Medical Technologist',
  'Physical Therapist',
  'Occupational Therapist',
  'Respiratory Therapist',
  'Other',
];

const availabilityOptions = [
  'Immediately',
  'Within 2 weeks',
  'Within 1 month',
  'Flexible',
];

function formatDate(dateStr) {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

function scrollToJobs() {
  jobsSection.value?.scrollIntoView({ behavior: 'smooth' });
}

function applyToJob(jobId) {
  router.push({ name: 'public.org.jobs.apply', params: { orgSlug, id: jobId } });
}

async function fetchData() {
  loading.value = true;
  error.value = null;

  try {
    // Fetch jobs for this organization on public job-board endpoint
    const res = await axios.get(`/api/public/job-board`, {
      params: { org: orgSlug }
    });

    jobs.value = res.data.data || [];

    // Get org info from first job or separate call
    if (jobs.value.length > 0) {
      organization.value = {
        id: jobs.value[0].tenant_id,
        name: jobs.value[0].organization_name,
        slug: jobs.value[0].organization_slug,
      };
    }
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to load jobs.';
  } finally {
    loading.value = false;
  }
}

async function submitRegistration() {
  registerLoading.value = true;
  registerError.value = null;

  try {
    const res = await axios.post(`/api/public/${orgSlug}/register`, registerForm.value);

    registeredCandidate.value = res.candidate;
    showRegisterModal.value = false;
    showSuccessModal.value = true;

    // Reset form
    registerForm.value = {
      first_name: '',
      last_name: '',
      email: '',
      phone: '',
      role: '',
      years_experience: '',
      availability: '',
      city: '',
      state: '',
    };
  } catch (e) {
    const status = e?.response?.status;
    if (status === 409) {
      registerError.value = e?.response?.data?.message || 'An account with this email already exists.';
    } else {
      registerError.value = e?.response?.data?.message || 'Registration failed. Please try again.';
    }
  } finally {
    registerLoading.value = false;
  }
}

function goToProfile() {
  showSuccessModal.value = false;
  // Redirect to login/portal to complete profile
  router.push({ name: 'login' });
}

onMounted(fetchData);
</script>
