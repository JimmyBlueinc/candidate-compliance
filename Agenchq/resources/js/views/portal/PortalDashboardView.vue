<template>
  <div class="portal-hub space-y-8 scroll-smooth-page">
    <section class="career-hero aq-on-dark reveal-up">
      <img
        :src="heroImage"
        alt="Professional candidate reviewing career opportunities"
        class="hero-image"
        loading="lazy"
        @error="onHeroImageError"
      />
      <div class="hero-overlay" />
      <div class="hero-content">
        <div class="flex items-center gap-3">
          <img v-if="avatarUrl" :src="avatarUrl" alt="Profile avatar" class="h-11 w-11 rounded-full border-2 border-white/70 object-cover shadow-md" />
          <div v-else class="h-11 w-11 rounded-full border-2 border-white/70 bg-white/30 backdrop-blur-sm" />
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-cyan-100/95">Career Hub</p>
            <h1 class="text-2xl font-semibold text-white md:text-3xl">
              {{ greetingName }}
            </h1>
          </div>
        </div>
        <p class="mt-4 max-w-2xl text-sm leading-relaxed text-slate-100/95 md:text-base">
          Find your next opportunity with confidence. We match your profile with trusted hiring teams that fit your strengths and preferred schedule.
        </p>
        <div class="mt-6 flex flex-wrap gap-3">
          <button type="button" class="hero-btn hero-btn-primary" @click="scrollToSection('recommended-jobs')">View recommended jobs</button>
          <button type="button" class="hero-btn hero-btn-secondary" @click="scrollToSection('compliance-status')">View readiness status</button>
        </div>
      </div>
    </section>

    <section
      v-if="showApplicationPrompt"
      class="rounded-2xl border border-amber-300 bg-amber-50 p-5 shadow-sm reveal-up"
    >
      <p class="text-xs font-semibold uppercase tracking-[0.14em] text-amber-700">Continue Application</p>
      <h2 class="mt-2 text-lg font-bold text-amber-900">Your application is not complete yet.</h2>
      <p class="mt-2 text-sm text-amber-800">{{ applicationPromptText }}</p>
      <div class="mt-4 flex flex-wrap gap-2">
        <RouterLink :to="{ name: 'portal.profile' }" class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Complete Phase 1</RouterLink>
        <RouterLink :to="{ name: 'portal.credentials' }" class="rounded-xl border border-amber-400 bg-white px-4 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-100">Complete Phase 2</RouterLink>
        <RouterLink
          v-if="pendingJobId"
          :to="{ name: 'portal.jobs.detail', params: { id: pendingJobId } }"
          class="rounded-xl border border-amber-400 bg-white px-4 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-100"
        >
          Continue for selected job
        </RouterLink>
      </div>
    </section>

    <section id="recommended-jobs" class="space-y-4 reveal-up">
      <div class="section-head">
        <h2>Recommended jobs</h2>
        <RouterLink :to="{ name: 'portal.jobs' }" class="section-link">View all jobs</RouterLink>
      </div>
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <article v-for="job in recommendedJobs" :key="job.id" class="hub-card job-card">
          <div class="job-meta">{{ job.specialty || 'Professional Role' }}</div>
          <h3 class="job-title">{{ job.title || 'Open Position' }}</h3>
          <p class="job-subtitle">
            {{ job.facility_name || job.location || 'Facility details available in job posting' }}
          </p>
          <div class="job-tags">
            <span>{{ job.shift || 'Flexible shift' }}</span>
            <span>{{ formatPay(job) }}</span>
          </div>
          <RouterLink :to="{ name: 'portal.jobs.detail', params: { id: job.id } }" class="job-cta">Easy apply</RouterLink>
        </article>
      </div>
    </section>

    <section id="compliance-status" class="grid gap-4 lg:grid-cols-3 reveal-up">
      <article class="hub-card">
        <p class="kicker">Profile completion</p>
        <p class="kpi">{{ profileStrength }}%</p>
        <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200">
          <div class="h-2 rounded-full transition-all duration-500" :style="{ width: `${profileStrength}%`, backgroundColor: primaryColor }" />
        </div>
        <p class="helper mt-3">Keep your profile complete to improve match quality.</p>
      </article>

      <article class="hub-card">
        <p class="kicker">Credentials verified</p>
        <p class="kpi">{{ approvedCredentialsCount }}/{{ credentialsCount || 1 }}</p>
        <p class="helper mt-3">Missing items may delay interviews and onboarding.</p>
      </article>

      <article class="hub-card">
        <p class="kicker">Quick actions</p>
        <div class="mt-4 space-y-2">
          <RouterLink :to="{ name: 'portal.profile' }" class="quick-action">Update profile</RouterLink>
          <RouterLink :to="{ name: 'portal.credentials' }" class="quick-action">Upload CV / credentials</RouterLink>
          <RouterLink :to="{ name: 'portal.messages' }" class="quick-action">Open messages</RouterLink>
        </div>
      </article>
    </section>

    <section class="grid gap-4 lg:grid-cols-2 reveal-up">
      <article class="hub-card">
        <div class="section-head mb-3">
          <h2>Activity</h2>
        </div>
        <ul class="space-y-3">
          <li v-for="item in activityItems" :key="item.title" class="activity-item">
            <div class="activity-dot" :style="{ backgroundColor: item.color }" />
            <div>
              <p class="activity-title">{{ item.title }}</p>
              <p class="activity-desc">{{ item.desc }}</p>
            </div>
          </li>
        </ul>
      </article>

      <article class="hub-card">
        <div class="section-head mb-3">
          <h2>Career momentum</h2>
        </div>
        <p class="helper">
          Stay active by applying to at least 3 roles this week. Candidates with complete profiles and active applications get faster recruiter follow-up.
        </p>
        <div class="mt-5 grid grid-cols-2 gap-3">
          <div class="mini-stat">
            <p class="mini-label">Available roles</p>
            <p class="mini-value">{{ jobs.length }}</p>
          </div>
          <div class="mini-stat">
            <p class="mini-label">Unread messages</p>
            <p class="mini-value">{{ unreadMessages }}</p>
          </div>
          <div class="mini-stat col-span-2">
            <p class="mini-label">Upcoming interviews</p>
            <p class="mini-value">{{ upcomingInterviews }}</p>
          </div>
        </div>
      </article>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { apiGet } from '../../lib/api';
import { useAuthStore } from '../../stores/auth';
import { useBrandStore } from '../../stores/brand';

const auth = useAuthStore();
const brand = useBrandStore();
const route = useRoute();
const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');

const me = ref(null);
const jobs = ref([]);
const unreadMessages = ref(0);
const interviewRows = ref([]);
const credentialsCount = ref(0);
const approvedCredentialsCount = ref(0);
const onboardingStatus = ref(null);
const heroImage = ref('https://images.unsplash.com/photo-1517048676732-d65bc937f952?auto=format&fit=crop&w=1800&q=80');
const pendingJobId = computed(() => {
    const v = String(route.query?.job_id || '').trim();
    return v || '';
});

const avatarUrl = computed(() => auth.user?.avatar_url || auth.user?.avatar_path || '');
const greetingName = computed(() => {
    const candidateName = me.value?.candidate?.first_name ? `${me.value.candidate.first_name}` : '';
    return candidateName || auth.user?.name || 'Clinician';
});

const recommendedJobs = computed(() => jobs.value.slice(0, 6));

const activityItems = computed(() => [
    {
        title: `${jobs.value.length || 0} active opportunities available`,
        desc: 'Roles are personalized based on your profile and specialty.',
        color: '#0ea5e9',
    },
    {
        title: `${approvedCredentialsCount.value} credentials verified`,
        desc: 'Upload any missing documents to speed up placement.',
        color: '#10b981',
    },
    {
        title: `${unreadMessages.value} unread conversation updates`,
        desc: 'Respond quickly to recruiters to keep momentum.',
        color: '#6366f1',
    },
    {
        title: `${upcomingInterviews.value} upcoming interviews`,
        desc: 'Keep your calendar available and meeting links ready.',
        color: '#a855f7',
    },
]);

const profileStrength = computed(() => {
    const c = me.value?.candidate || null;
    const contactFields = [c?.first_name, c?.last_name, c?.email, c?.phone];
    const contactScore = contactFields.filter(Boolean).length / 4;

    const docs = Math.min(1, (Number(approvedCredentialsCount.value || 0) / 5));

    return Math.round((contactScore * 0.6 + docs * 0.4) * 100);
});

const upcomingInterviews = computed(() => {
    const now = new Date().getTime();
    return interviewRows.value.filter((row) => {
        const starts = new Date(row?.starts_at || '').getTime();
        return Number.isFinite(starts) && starts >= now && row?.status === 'scheduled';
    }).length;
});

const showApplicationPrompt = computed(() => {
    const requested = String(route.query?.continue_application || '') === '1';
    const phase1Complete = Boolean(onboardingStatus.value?.phase1_complete);
    const phase2Complete = Boolean(onboardingStatus.value?.phase2_complete);
    return requested || !phase1Complete || !phase2Complete;
});

const applicationPromptText = computed(() => {
    const phase1Complete = Boolean(onboardingStatus.value?.phase1_complete);
    const phase2Complete = Boolean(onboardingStatus.value?.phase2_complete);
    if (!phase1Complete && !phase2Complete) {
        return 'Finish your personal profile (phase 1), then upload credentials/documents (phase 2) before your job application can be fully submitted.';
    }
    if (!phase1Complete) {
        return 'Finish phase 1 personal profile details to continue your application.';
    }
    if (!phase2Complete) {
        return 'Phase 1 is complete. Upload and complete phase 2 credentials/documents to finalize your application.';
    }
    return 'Your profile is complete. Open your selected job and submit final interest.';
});

function onHeroImageError() {
    heroImage.value = '/images/public/tenant-careers-hero.svg';
}

function scrollToSection(id) {
    const section = document.getElementById(id);
    section?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function formatPay(job) {
    const min = Number(job?.pay_rate_min || 0);
    const max = Number(job?.pay_rate_max || 0);
    if (!min && !max) return 'Pay details listed';
    if (min && max) return `$${min} - $${max}/hr`;
    return `$${min || max}/hr`;
}

async function loadMe() {
    try {
        const response = await apiGet('/v1/portal/me');
        me.value = response?.data || response;
        credentialsCount.value = Number(me.value?.credentials_count || 0);
        approvedCredentialsCount.value = Number(me.value?.approved_credentials_count || 0);
    } catch (e) {
        console.error('Failed to load candidate profile', e);
    }
}

async function loadOnboardingStatus() {
    try {
        const response = await apiGet('/v1/portal/profile');
        const payload = response?.data || response;
        onboardingStatus.value = payload?.onboarding || null;
    } catch {
        onboardingStatus.value = null;
    }
}

async function loadJobs() {
    try {
        const response = await apiGet('/v1/portal/jobs');
        const payload = response?.data || response;
        jobs.value = Array.isArray(payload) ? payload : [];
    } catch (e) {
        jobs.value = [];
    }
}

async function loadMessageCount() {
    try {
        const response = await apiGet('/v1/notifications/unread-count');
        const payload = response?.data || response;
        unreadMessages.value = Number(payload?.count || 0);
    } catch (e) {
        unreadMessages.value = 0;
    }
}

async function loadInterviews() {
    try {
        const response = await apiGet('/v1/candidate/interviews');
        const payload = response?.data || response;
        interviewRows.value = Array.isArray(payload) ? payload : [];
    } catch {
        interviewRows.value = [];
    }
}

onMounted(async () => {
    if (!brand.loaded && !brand.loading) {
        await brand.load();
    }
    await loadMe();
    await loadOnboardingStatus();
    await loadJobs();
    await loadMessageCount();
    await loadInterviews();
});
</script>

<style scoped>
.portal-hub {
  --hub-border: rgba(148, 163, 184, 0.25);
}

.career-hero {
  position: relative;
  overflow: hidden;
  border-radius: 1.6rem;
  min-height: 270px;
  border: 1px solid rgba(255, 255, 255, 0.35);
  box-shadow: 0 28px 70px -36px rgba(15, 23, 42, 0.55);
}

.hero-image {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.hero-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(120deg, rgba(15, 23, 42, 0.78), rgba(15, 23, 42, 0.35), rgba(6, 182, 212, 0.26));
}

.hero-content {
  position: relative;
  z-index: 1;
  padding: 2rem;
}

.hero-btn {
  border-radius: 0.8rem;
  font-size: 0.8rem;
  font-weight: 700;
  padding: 0.58rem 0.9rem;
  transition: transform 180ms ease, background-color 180ms ease;
}

.hero-btn:hover {
  transform: translateY(-1px);
}

.hero-btn-primary {
  background: #fff;
  color: #0f172a;
}

.hero-btn-secondary {
  border: 1px solid rgba(255, 255, 255, 0.5);
  color: #fff;
  background: rgba(255, 255, 255, 0.07);
}

.hub-card {
  border-radius: 1.15rem;
  border: 1px solid var(--hub-border);
  background: rgba(255, 255, 255, 0.94);
  padding: 1rem;
  box-shadow: 0 18px 48px -32px rgba(15, 23, 42, 0.35);
}

.section-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.section-head h2 {
  font-size: 0.96rem;
  font-weight: 700;
  color: #0f172a;
}

.section-link {
  font-size: 0.75rem;
  font-weight: 700;
  color: #334155;
}

.section-link:hover {
  color: #0f172a;
}

.job-card {
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
}

.job-meta {
  width: fit-content;
  border-radius: 999px;
  background: rgba(14, 165, 233, 0.1);
  color: #0369a1;
  padding: 0.2rem 0.5rem;
  font-size: 0.66rem;
  font-weight: 700;
}

.job-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #0f172a;
}

.job-subtitle {
  font-size: 0.75rem;
  color: #475569;
}

.job-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.job-tags span {
  border-radius: 999px;
  border: 1px solid rgba(148, 163, 184, 0.35);
  padding: 0.16rem 0.46rem;
  font-size: 0.66rem;
  color: #334155;
}

.job-cta {
  margin-top: 0.3rem;
  display: inline-flex;
  width: fit-content;
  border-radius: 0.7rem;
  background: color-mix(in srgb, var(--p-primary-color) 14%, white);
  color: color-mix(in srgb, var(--p-primary-color) 80%, #0f172a);
  padding: 0.4rem 0.62rem;
  font-size: 0.72rem;
  font-weight: 700;
}

.kicker {
  font-size: 0.68rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  font-weight: 700;
  color: #64748b;
}

.kpi {
  margin-top: 0.32rem;
  font-size: 1.5rem;
  font-weight: 700;
  color: #0f172a;
}

.helper {
  font-size: 0.75rem;
  line-height: 1.45;
  color: #475569;
}

.quick-action {
  display: block;
  border-radius: 0.72rem;
  border: 1px solid rgba(148, 163, 184, 0.32);
  background: #fff;
  padding: 0.5rem 0.68rem;
  font-size: 0.75rem;
  font-weight: 700;
  color: #1e293b;
  transition: border-color 160ms ease, transform 160ms ease;
}

.quick-action:hover {
  transform: translateY(-1px);
  border-color: color-mix(in srgb, var(--p-primary-color) 34%, #94a3b8);
}

.activity-item {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
}

.activity-dot {
  margin-top: 0.34rem;
  height: 0.45rem;
  width: 0.45rem;
  border-radius: 999px;
}

.activity-title {
  font-size: 0.78rem;
  font-weight: 700;
  color: #0f172a;
}

.activity-desc {
  margin-top: 0.12rem;
  font-size: 0.72rem;
  color: #64748b;
}

.mini-stat {
  border-radius: 0.8rem;
  border: 1px solid rgba(148, 163, 184, 0.28);
  background: #fff;
  padding: 0.6rem 0.7rem;
}

.mini-label {
  font-size: 0.67rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #64748b;
}

.mini-value {
  margin-top: 0.2rem;
  font-size: 1.2rem;
  font-weight: 700;
  color: #0f172a;
}

.reveal-up {
  animation: revealUp 520ms ease both;
}

@keyframes revealUp {
  from { opacity: 0; transform: translateY(12px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
