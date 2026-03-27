<template>
  <div class="min-h-screen text-[var(--app-fg)]" :style="pageStyle">
    <div class="max-w-5xl mx-auto px-6 sm:px-10 py-10">
      <div class="glass-dark rounded-[36px] overflow-hidden border border-white/5 shadow-[0_22px_80px_rgba(0,0,0,0.30)]">
        <div class="p-8 sm:p-10">
          <div class="flex items-start justify-between gap-6">
            <div class="min-w-0">
              <div class="text-[10px] font-black tracking-[0.30em] uppercase text-[color:var(--p-text-muted-color)]">
                Secure Candidate Submission
              </div>
              <div class="mt-2 font-display text-3xl leading-tight text-white truncate">
                {{ brand?.name || 'Agency' }}
              </div>
              <div class="mt-1 text-sm text-[color:var(--p-text-muted-color)]">
                This page is read-only. Candidate contact info is masked.
              </div>
            </div>

            <div v-if="brand?.logo_url" class="shrink-0">
              <img :src="brand.logo_url" alt="Agency logo" class="h-12 w-auto object-contain" />
            </div>
          </div>

          <div v-if="loading" class="mt-8 text-sm text-[color:var(--p-text-muted-color)]">Loading...</div>
          <div v-else-if="error" class="mt-8 text-sm text-red-400">{{ error }}</div>

          <div v-else class="mt-8 space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
              <div class="lg:col-span-2 p-6 rounded-3xl bg-white/[0.03] border border-white/5">
                <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Candidate Snapshot</div>

                <div class="mt-3 text-2xl font-display text-white">
                  {{ candidateName }}
                </div>

                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                  <div class="p-4 rounded-2xl border border-white/5 bg-white/[0.02]">
                    <div class="text-xs text-[color:var(--p-text-muted-color)]">Specialty</div>
                    <div class="mt-1 font-semibold text-white">{{ candidate?.specialty || '—' }}</div>
                  </div>

                  <div class="p-4 rounded-2xl border border-white/5 bg-white/[0.02]">
                    <div class="text-xs text-[color:var(--p-text-muted-color)]">Experience</div>
                    <div class="mt-1 font-semibold text-white">{{ candidate?.experience || '—' }}</div>
                  </div>
                </div>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                  <div class="p-4 rounded-2xl border border-white/5 bg-white/[0.02]">
                    <div class="text-xs text-[color:var(--p-text-muted-color)]">Email</div>
                    <div class="mt-1 font-semibold text-white">{{ candidate?.email_masked || '—' }}</div>
                  </div>

                  <div class="p-4 rounded-2xl border border-white/5 bg-white/[0.02]">
                    <div class="text-xs text-[color:var(--p-text-muted-color)]">Phone</div>
                    <div class="mt-1 font-semibold text-white">{{ candidate?.phone_masked || '—' }}</div>
                  </div>
                </div>

                <div class="mt-5">
                  <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Skills</div>
                  <div class="mt-3 flex flex-wrap gap-2">
                    <span
                      v-for="s in skills"
                      :key="s"
                      class="px-3 py-1 rounded-full text-xs font-bold border"
                      :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
                    >
                      {{ s }}
                    </span>
                    <span v-if="skills.length === 0" class="text-sm text-[color:var(--p-text-muted-color)]">No skills listed.</span>
                  </div>
                </div>
              </div>

              <div class="p-6 rounded-3xl bg-white/[0.03] border border-white/5">
                <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Job Order</div>

                <div class="mt-3 text-lg font-semibold text-white">
                  {{ jobOrder?.title || '—' }}
                </div>
                <div class="mt-1 text-sm text-[color:var(--p-text-muted-color)]">
                  {{ jobOrder?.facility_name || '—' }}
                  <span class="opacity-40">•</span>
                  {{ jobOrder?.specialty || '—' }}
                </div>

                <div class="mt-6 p-4 rounded-2xl border border-white/5 bg-white/[0.02]">
                  <div class="text-xs text-[color:var(--p-text-muted-color)]">Link Views</div>
                  <div class="mt-1 text-2xl font-display" :style="{ color: primaryColor }">{{ submission?.view_count ?? 0 }}</div>
                  <div v-if="submission?.expires_at" class="mt-1 text-xs text-[color:var(--p-text-muted-color)]">
                    Expires: {{ formatDateTime(submission.expires_at) }}
                  </div>
                </div>
              </div>
            </div>

            <div class="p-6 rounded-3xl bg-white/[0.03] border border-white/5">
              <div class="flex items-start justify-between gap-4">
                <div>
                  <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Document Gallery</div>
                  <div class="mt-1 text-sm text-[color:var(--p-text-muted-color)]">
                    Thumbnails are blurred for privacy.
                  </div>
                </div>
              </div>

              <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div
                  v-for="doc in credentials"
                  :key="doc.id"
                  class="p-4 rounded-2xl border border-white/5 bg-white/[0.02]"
                >
                  <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                      <div class="text-sm font-semibold text-white truncate">{{ doc.credential_type }}</div>
                      <div class="mt-1 text-xs text-[color:var(--p-text-muted-color)]">Status: {{ doc.status || '—' }}</div>
                    </div>
                    <div
                      class="shrink-0 px-2 py-1 rounded-full text-[10px] font-black tracking-widest uppercase border"
                      :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
                    >
                      {{ doc.has_document ? 'On File' : 'Missing' }}
                    </div>
                  </div>

                  <div class="mt-4 h-24 rounded-xl border border-white/5 bg-white/[0.03] relative overflow-hidden">
                    <div class="absolute inset-0" :style="thumbStyle"></div>
                    <div class="absolute inset-0 flex items-center justify-center text-xs font-black tracking-widest uppercase text-white/70">
                      Verified
                    </div>
                  </div>
                </div>

                <div v-if="credentials.length === 0" class="text-sm text-[color:var(--p-text-muted-color)]">
                  No credentials available.
                </div>
              </div>
            </div>

            <div class="text-xs text-[color:var(--p-text-muted-color)]">
              If you need to contact this clinician, reply to the recruiter who sent you this link.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { apiGet } from '../../lib/api';

const route = useRoute();

const loading = ref(false);
const error = ref('');

const brand = ref(null);
const submission = ref(null);
const candidate = ref(null);
const jobOrder = ref(null);
const credentials = ref([]);

const primaryColor = computed(() => {
    return brand.value?.primary_color || 'var(--brand-primary, var(--p-primary-color))';
});
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const pageStyle = computed(() => {
    return {
        background: 'linear-gradient(180deg, rgba(6,8,16,1) 0%, rgba(10,12,24,1) 40%, rgba(7,8,18,1) 100%)',
    };
});

const candidateName = computed(() => {
    const first = String(candidate.value?.first_name || '').trim();
    const lastMasked = String(candidate.value?.last_name_masked || '').trim();
    const suffix = lastMasked ? ` ${lastMasked}` : '';
    return first ? `${first}${suffix}` : 'Candidate';
});

const skills = computed(() => {
    const s = candidate.value?.skills;
    return Array.isArray(s) ? s.filter(Boolean).slice(0, 16) : [];
});

const thumbStyle = computed(() => {
    return {
        background: `repeating-linear-gradient(135deg, rgba(255,255,255,0.06) 0px, rgba(255,255,255,0.06) 10px, rgba(255,255,255,0.02) 10px, rgba(255,255,255,0.02) 20px)` ,
        filter: 'blur(1.5px)',
        opacity: 0.9,
    };
});

function formatDateTime(v) {
    if (!v) return '—';
    const d = new Date(v);
    if (Number.isNaN(d.getTime())) return String(v);
    return d.toLocaleString();
}

async function load() {
    const token = String(route.params.token || '').trim();
    if (!token) return;

    loading.value = true;
    error.value = '';
    try {
        const res = await apiGet(`/public/submission/${token}`);
        const data = res?.data || null;

        brand.value = data?.brand || null;
        submission.value = data?.submission || null;
        candidate.value = data?.candidate || null;
        jobOrder.value = data?.job_order || null;
        credentials.value = Array.isArray(data?.credentials) ? data.credentials : [];

        if (brand.value?.primary_color) {
            document.documentElement.style.setProperty('--brand-primary', brand.value.primary_color);
        }
    } catch (e) {
        error.value = e?.message || 'Failed to load submission.';
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    await load();
});
</script>
