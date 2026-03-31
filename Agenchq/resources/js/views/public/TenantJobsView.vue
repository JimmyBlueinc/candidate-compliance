<template>
  <div class="min-h-screen bg-[var(--app-bg)] text-[var(--app-fg)]">
    <!-- Header -->
    <header class="border-b border-[color:var(--p-surface-border)] bg-[color:var(--p-surface-card)]">
      <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div
            class="w-10 h-10 rounded-xl flex items-center justify-center"
            :style="{ backgroundColor: primarySolid }"
          >
            <img v-if="brand.logoUrl" :src="brand.logoUrl" alt="Logo" class="h-6 w-6 object-contain" />
            <span v-else class="text-white font-bold text-lg">{{ brand.name?.charAt(0) || 'A' }}</span>
          </div>
          <div>
            <h1 class="font-display text-lg font-semibold text-[color:var(--aq-fg)]">{{ brand.name || 'Organization' }}</h1>
            <p class="text-xs text-[color:var(--p-text-muted-color)]">Open Positions</p>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <Button label="Back to Home" severity="secondary" size="small" @click="goHome" text />
          <Button label="Dashboard" size="small" @click="goToDashboard" />
        </div>
      </div>
    </header>

    <!-- Jobs List -->
    <section class="py-8 px-6">
      <div class="max-w-4xl mx-auto">
        <h2 class="text-2xl font-display font-bold mb-6 text-[color:var(--aq-fg)]">Open Positions</h2>

        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center py-20">
          <i class="pi pi-spin pi-spinner text-3xl text-[color:var(--p-primary-color)]"></i>
        </div>

        <!-- Empty State -->
        <div v-else-if="jobs.length === 0" class="text-center py-20">
          <i class="pi pi-briefcase text-5xl text-[color:var(--p-text-muted-color)] mb-4"></i>
          <h3 class="text-xl font-semibold mb-2">No Open Positions</h3>
          <p class="text-[color:var(--p-text-muted-color)]">Check back soon for new opportunities.</p>
        </div>

        <!-- Jobs Grid -->
        <div v-else class="grid gap-4">
          <div
            v-for="job in jobs"
            :key="job.id"
            class="bg-[color:var(--p-surface-card)] rounded-2xl border border-[color:var(--p-surface-border)] p-6 hover:border-[color:var(--p-primary-color)]/50 transition-colors cursor-pointer"
            @click="viewJob(job)"
          >
            <div class="flex items-start justify-between gap-4">
              <div>
                <h3 class="font-semibold text-lg mb-1">{{ job.title }}</h3>
                <p class="text-[color:var(--p-text-muted-color)] text-sm mb-3">
                  {{ [job.facility_city, job.facility_state].filter(Boolean).join(', ') || job.facility_name || 'Multiple locations' }}
                  · {{ formatWorkMode(job.work_mode) }}
                </p>
                <p class="text-sm line-clamp-2">{{ job.description }}</p>
              </div>
              <Button label="Apply" size="small" @click.stop="applyToJob(job)" />
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useBrandStore } from '../../stores/brand';
import { useAuthStore } from '../../stores/auth';
import { apiGet } from '../../lib/api';
import Button from 'primevue/button';

const router = useRouter();
const brand = useBrandStore();
const auth = useAuthStore();

const jobs = ref([]);
const loading = ref(true);

const primarySolid = computed(() => brand.primaryColor || '#3b82f6');

function goHome() {
  router.push({ name: 'tenant.home' });
}

function goToDashboard() {
  if (auth.isAuthenticated) {
    router.push({ name: 'dashboard.index' });
  } else {
    router.push({ name: 'login' });
  }
}

function viewJob(job) {
  router.push({ name: 'tenant.job-detail', params: { id: job.id } });
}

function applyToJob(job) {
  router.push({ name: 'tenant.job-apply', params: { id: job.id } });
}

async function fetchJobs() {
  loading.value = true;
  try {
    const org = String(brand.slug || '').trim();
    const res = await apiGet('/public/job-board', {
      params: org ? { org } : {},
    });
    jobs.value = Array.isArray(res?.data) ? res.data : [];
  } catch (e) {
    console.error('[TENANT_JOBS] Error:', e);
    jobs.value = [];
  } finally {
    loading.value = false;
  }
}

function formatWorkMode(v) {
  if (!v) return 'Open';
  if (v === 'on_site') return 'On-site';
  if (v === 'remote') return 'Remote';
  return String(v).replace(/_/g, ' ');
}

onMounted(async () => {
  if (!brand.loaded && !brand.loading) {
    await brand.load();
  }
  await fetchJobs();
});
</script>
