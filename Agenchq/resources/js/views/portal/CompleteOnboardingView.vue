<template>
  <div class="min-h-screen bg-[var(--app-bg)] text-[var(--app-fg)]">
    <div class="max-w-3xl mx-auto px-6 py-10">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-2xl font-display font-bold">Complete Your Profile</h1>
        <p class="mt-2 text-[color:var(--p-text-muted-color)]">
          Upload your documents to apply for jobs
        </p>
      </div>

      <!-- Progress -->
      <div class="mb-8">
        <div class="flex justify-between text-sm mb-2">
          <span>Profile Completion</span>
          <span>{{ completionPercent }}%</span>
        </div>
        <ProgressBar :value="completionPercent" :showValue="false" />
      </div>

      <!-- Form -->
      <div class="space-y-6">
        <!-- Personal Info -->
        <div class="glass-dark rounded-2xl border border-white/5 p-6">
          <h2 class="text-lg font-semibold mb-4">Personal Information</h2>
          <div class="grid md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm mb-1">First Name</label>
              <InputText v-model="form.first_name" class="w-full" />
            </div>
            <div>
              <label class="block text-sm mb-1">Last Name</label>
              <InputText v-model="form.last_name" class="w-full" />
            </div>
            <div>
              <label class="block text-sm mb-1">Email</label>
              <InputText v-model="form.email" type="email" class="w-full" disabled />
            </div>
            <div>
              <label class="block text-sm mb-1">Phone</label>
              <InputText v-model="form.phone" class="w-full" />
            </div>
            <div>
              <label class="block text-sm mb-1">City</label>
              <InputText v-model="form.city" class="w-full" />
            </div>
            <div>
              <label class="block text-sm mb-1">State</label>
              <InputText v-model="form.state" class="w-full" />
            </div>
          </div>
        </div>

        <!-- Professional Info -->
        <div class="glass-dark rounded-2xl border border-white/5 p-6">
          <h2 class="text-lg font-semibold mb-4">Professional Details</h2>
          <div class="grid md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm mb-1">Role / Specialty</label>
              <InputText v-model="form.role" class="w-full" />
            </div>
            <div>
              <label class="block text-sm mb-1">Years of Experience</label>
              <InputText v-model="form.years_experience" class="w-full" />
            </div>
            <div>
              <label class="block text-sm mb-1">License Type</label>
              <InputText v-model="form.license_type" class="w-full" />
            </div>
            <div>
              <label class="block text-sm mb-1">Availability</label>
              <Dropdown
                v-model="form.availability"
                :options="availabilityOptions"
                placeholder="Select availability"
                class="w-full"
              />
            </div>
          </div>
        </div>

        <!-- Documents -->
        <div class="glass-dark rounded-2xl border border-white/5 p-6">
          <h2 class="text-lg font-semibold mb-4">Documents</h2>
          <p class="text-sm text-[color:var(--p-text-muted-color)] mb-4">
            Upload your resume, certifications, and licenses.
          </p>

          <!-- Resume -->
          <div class="mb-4">
            <label class="block text-sm mb-1">Resume / CV</label>
            <FileUpload
              mode="basic"
              accept=".pdf,.doc,.docx"
              :maxFileSize="5000000"
              chooseLabel="Upload Resume"
              @select="onResumeUpload"
              class="w-full"
            />
            <div v-if="form.resume_path" class="mt-2 text-sm text-emerald-400">
              <i class="pi pi-check-circle mr-1"></i>Resume uploaded
            </div>
          </div>

          <!-- Certifications -->
          <div class="mb-4">
            <label class="block text-sm mb-1">Certifications & Licenses</label>
            <FileUpload
              mode="basic"
              accept=".pdf,.jpg,.jpeg,.png"
              :maxFileSize="5000000"
              chooseLabel="Upload Certifications"
              @select="onCertUpload"
              class="w-full"
            />
            <div v-if="certUploaded" class="mt-2 text-sm text-emerald-400">
              <i class="pi pi-check-circle mr-1"></i>Certifications uploaded
            </div>
          </div>
        </div>

        <!-- Compliance -->
        <div class="glass-dark rounded-2xl border border-white/5 p-6">
          <h2 class="text-lg font-semibold mb-4">Compliance Status</h2>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span>Work Authorization</span>
              <Checkbox v-model="form.work_authorization" binary />
            </div>
            <div class="flex items-center justify-between">
              <span>Background Check Cleared</span>
              <Checkbox v-model="form.background_check" binary />
            </div>
            <div class="flex items-center justify-between">
              <span>Drug Screen Passed</span>
              <Checkbox v-model="form.drug_screen" binary />
            </div>
            <div class="flex items-center justify-between">
              <span>Vaccination Complete</span>
              <Checkbox v-model="form.vaccination" binary />
            </div>
          </div>
        </div>

        <!-- Error -->
        <div v-if="error" class="text-red-400 text-sm text-center">{{ error }}</div>

        <!-- Submit -->
        <div class="flex justify-end gap-4">
          <Button label="Save Draft" severity="secondary" @click="saveDraft" :loading="saving" />
          <Button label="Complete Profile" @click="submitComplete" :loading="submitting" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Checkbox from 'primevue/checkbox';
import ProgressBar from 'primevue/progressbar';
import FileUpload from 'primevue/fileupload';
import { apiGet, apiPut } from '../../lib/api';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const auth = useAuthStore();

const loading = ref(true);
const saving = ref(false);
const submitting = ref(false);
const error = ref(null);
const certUploaded = ref(false);

const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  city: '',
  state: '',
  role: '',
  years_experience: '',
  license_type: '',
  availability: '',
  resume_path: '',
  work_authorization: false,
  background_check: false,
  drug_screen: false,
  vaccination: false,
});

const availabilityOptions = [
  'Immediately',
  'Within 2 weeks',
  'Within 1 month',
  'Flexible',
];

const completionPercent = computed(() => {
  let filled = 0;
  const required = ['first_name', 'last_name', 'email', 'phone', 'role'];
  const documents = ['resume_path'];

  required.forEach(f => {
    if (form.value[f]) filled++;
  });

  documents.forEach(f => {
    if (form.value[f]) filled++;
  });

  // Add compliance checks
  if (form.value.work_authorization) filled++;
  if (form.value.background_check) filled++;
  if (form.value.drug_screen) filled++;
  if (form.value.vaccination) filled++;

  return Math.round((filled / 10) * 100);
});

async function loadProfile() {
  loading.value = true;
  try {
    const res = await apiGet('/portal/profile');
    if (res.candidate) {
      Object.keys(form.value).forEach(key => {
        if (res.candidate[key] !== undefined) {
          form.value[key] = res.candidate[key];
        }
      });
    }
  } catch (e) {
    error.value = 'Failed to load profile.';
  } finally {
    loading.value = false;
  }
}

function onResumeUpload(event) {
  // Handle file upload - would need actual upload endpoint
  form.value.resume_path = 'uploaded';
}

function onCertUpload(event) {
  certUploaded.value = true;
}

async function saveDraft() {
  saving.value = true;
  error.value = null;

  try {
    await apiPut('/portal/profile', form.value);
  } catch (e) {
    error.value = 'Failed to save.';
  } finally {
    saving.value = false;
  }
}

async function submitComplete() {
  submitting.value = true;
  error.value = null;

  try {
    const res = await apiPut('/portal/profile', {
      ...form.value,
      onboarding_stage: 'fully_completed',
    });

    // Update auth store
    auth.user.onboarding_stage = 'fully_completed';

    // Redirect to jobs or back to application
    router.push({ name: 'portal.jobs' });
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to complete profile.';
  } finally {
    submitting.value = false;
  }
}

onMounted(loadProfile);
</script>
