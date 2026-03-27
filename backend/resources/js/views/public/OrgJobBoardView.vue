<template>
  <div class="min-h-screen bg-[var(--app-bg)] text-[var(--app-fg)]">
    <div class="max-w-4xl mx-auto px-6 py-10">
      <!-- Loading state -->
      <div v-if="loading" class="flex items-center justify-center py-20">
        <i class="pi pi-spin pi-spinner text-3xl text-[color:var(--p-primary-color)]"></i>
      </div>

      <!-- Error state -->
      <div v-else-if="error" class="text-center py-20">
        <div class="text-red-400 mb-4">{{ error }}</div>
        <Button label="Try Again" @click="fetchJobs" />
      </div>

      <!-- Job board content -->
      <div v-else>
        <!-- Header with org branding -->
        <div class="mb-8 flex items-center gap-4">
          <img
            v-if="organization?.logo_path"
            :src="`/api/brand/logo/${organization.id}`"
            :alt="organization.name"
            class="w-12 h-12 rounded-lg object-cover"
          />
          <div>
            <h1 class="text-3xl font-display font-bold">
              {{ organization?.name || 'My Job Board' }}
            </h1>
            <p class="mt-1 text-[color:var(--p-text-muted-color)]">
              Open positions available to you
            </p>
          </div>
        </div>

        <!-- Empty state -->
        <div v-if="jobs.length === 0" class="text-center py-20">
          <div class="text-[color:var(--p-text-muted-color)] mb-2">No open positions</div>
          <p class="text-sm text-[color:var(--p-text-muted-color)]">
            Check back later for new opportunities.
          </p>
        </div>

        <!-- Job list -->
        <div v-else class="space-y-4">
          <div
            v-for="job in jobs"
            :key="job.id"
            class="glass-dark rounded-2xl border border-white/5 p-6 hover:border-white/10 transition-colors cursor-pointer"
            @click="viewJob(job.id)"
          >
            <h2 class="text-xl font-semibold">{{ job.title }}</h2>
            <p class="mt-2 text-[color:var(--p-text-muted-color)] text-sm line-clamp-3">
              {{ job.description || 'No description provided.' }}
            </p>
            <div class="mt-4 text-xs text-[color:var(--p-text-muted-color)]">
              Posted {{ formatDate(job.created_at) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import Button from 'primevue/button';
import { apiGet } from '../../lib/api';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const auth = useAuthStore();

const loading = ref(true);
const error = ref(null);
const organization = ref(null);
const jobs = ref([]);

function formatDate(dateStr) {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

function viewJob(id) {
  router.push({ name: 'my-org.job', params: { id } });
}

async function fetchJobs() {
  loading.value = true;
  error.value = null;

  try {
    const res = await apiGet('/my-org/jobs');
    organization.value = res.organization;
    jobs.value = res.jobs || [];
  } catch (e) {
    const status = e?.response?.status;
    if (status === 403) {
      error.value = 'You are not associated with an organization.';
    } else {
      error.value = e?.response?.data?.message || 'Failed to load jobs.';
    }
  } finally {
    loading.value = false;
  }
}

onMounted(fetchJobs);
</script>
