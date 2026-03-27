<template>
  <div class="min-h-screen bg-[var(--app-bg)] text-[var(--app-fg)] selection:bg-purple-500/30">
    <div class="max-w-7xl mx-auto px-6 sm:px-10 py-10">
      <div class="flex items-start justify-between gap-4">
        <div>
          <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-white/10 bg-white/5 text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">
            Public Job Board
          </div>
          <h1 class="mt-5 font-display text-4xl sm:text-5xl text-white tracking-tight">Find a role. Apply in minutes.</h1>
          <p class="mt-3 text-sm sm:text-base text-[color:var(--p-text-muted-color)] max-w-2xl">
            Browse open jobs across organizations. When you apply, your candidate account is created (or verified) for that organization automatically.
          </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <RouterLink
            to="/"
            class="px-4 py-2 rounded-full text-xs font-black tracking-widest uppercase border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10"
          >
            Home
          </RouterLink>
          <RouterLink
            to="/portal/login"
            class="px-4 py-2 rounded-full text-xs font-black tracking-widest uppercase border"
            :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
          >
            Candidate Portal
          </RouterLink>
        </div>
      </div>

      <div class="mt-8 grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-4 glass-dark rounded-[32px] p-6 border border-white/5">
          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Filters</div>
              <div class="mt-1 text-sm text-slate-200">Narrow down the list</div>
            </div>
            <button
              type="button"
              class="px-3 py-1.5 rounded-full text-[11px] font-black tracking-widest uppercase border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10"
              :disabled="loading"
              @click="reset"
            >
              Reset
            </button>
          </div>

          <div class="mt-5 space-y-4">
            <div>
              <div class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Search</div>
              <input
                v-model="filters.q"
                type="text"
                class="mt-2 w-full rounded-2xl px-4 py-3 text-sm bg-white/5 border border-white/10 text-white placeholder:text-slate-500 outline-none focus:border-white/20"
                placeholder="Title, org, specialty..."
                @keydown.enter.prevent="refresh"
              />
            </div>

            <div>
              <div class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Organization</div>
              <input
                v-model="filters.org"
                type="text"
                class="mt-2 w-full rounded-2xl px-4 py-3 text-sm bg-white/5 border border-white/10 text-white placeholder:text-slate-500 outline-none focus:border-white/20"
                placeholder="org slug"
                @keydown.enter.prevent="refresh"
              />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <div class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Specialty</div>
                <input
                  v-model="filters.specialty"
                  type="text"
                  class="mt-2 w-full rounded-2xl px-4 py-3 text-sm bg-white/5 border border-white/10 text-white placeholder:text-slate-500 outline-none focus:border-white/20"
                  placeholder="e.g. RN"
                  @keydown.enter.prevent="refresh"
                />
              </div>
              <div>
                <div class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">Work mode</div>
                <select
                  v-model="filters.work_mode"
                  class="mt-2 w-full rounded-2xl px-4 py-3 text-sm bg-white/5 border border-white/10 text-white outline-none focus:border-white/20"
                  @change="refresh"
                >
                  <option value="">Any</option>
                  <option value="on_site">On-site</option>
                  <option value="remote">Remote</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <div class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">City</div>
                <input
                  v-model="filters.city"
                  type="text"
                  class="mt-2 w-full rounded-2xl px-4 py-3 text-sm bg-white/5 border border-white/10 text-white placeholder:text-slate-500 outline-none focus:border-white/20"
                  placeholder="City"
                  @keydown.enter.prevent="refresh"
                />
              </div>
              <div>
                <div class="text-[11px] font-black text-[color:var(--p-text-muted-color)] uppercase tracking-widest ml-1">State</div>
                <input
                  v-model="filters.state"
                  type="text"
                  class="mt-2 w-full rounded-2xl px-4 py-3 text-sm bg-white/5 border border-white/10 text-white placeholder:text-slate-500 outline-none focus:border-white/20"
                  placeholder="State"
                  @keydown.enter.prevent="refresh"
                />
              </div>
            </div>

            <button
              type="button"
              class="w-full px-4 py-3 rounded-2xl text-xs font-black tracking-widest uppercase border"
              :style="{ backgroundColor: primaryColor, borderColor: primaryColor, color: '#fff' }"
              :disabled="loading"
              @click="refresh"
            >
              {{ loading ? 'Searching…' : 'Search jobs' }}
            </button>
          </div>
        </div>

        <div class="lg:col-span-8">
          <div v-if="error" class="mb-4 text-sm text-red-400">{{ error }}</div>

          <div class="glass-dark rounded-[32px] border border-white/5 overflow-hidden">
            <div class="px-6 py-5 border-b border-white/5 flex items-center justify-between gap-4">
              <div>
                <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Results</div>
                <div class="mt-1 text-sm text-slate-200">{{ loading ? 'Loading…' : `${items.length} job${items.length === 1 ? '' : 's'}` }}</div>
              </div>
              <button
                type="button"
                class="px-3 py-1.5 rounded-full text-[11px] font-black tracking-widest uppercase border"
                :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
                :disabled="loading"
                @click="refresh"
              >
                Refresh
              </button>
            </div>

            <div class="p-4 sm:p-6">
              <div v-if="loading" class="text-sm text-[color:var(--p-text-muted-color)]">Loading…</div>
              <div v-else-if="items.length === 0" class="text-sm text-[color:var(--p-text-muted-color)]">No jobs found.</div>

              <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                  v-for="j in items"
                  :key="j.id"
                  class="group p-5 rounded-[28px] bg-white/[0.03] border border-white/5 hover:bg-white/[0.06] hover:border-white/10 transition-all"
                  role="button"
                  tabindex="0"
                  @click="goToDetail(j)"
                >
                  <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                      <div class="text-white font-semibold truncate">{{ j.title }}</div>
                      <div class="mt-1 text-xs text-[color:var(--p-text-muted-color)] truncate">
                        {{ j.organization_name }}
                        <span class="opacity-40">•</span>
                        {{ j.facility_name }}
                      </div>

                      <div class="mt-3 flex flex-wrap gap-2">
                        <span class="px-3 py-1 rounded-full text-[11px] font-black tracking-widest uppercase border border-white/10 bg-white/5 text-slate-200">
                          {{ j.specialty || 'Specialty —' }}
                        </span>
                        <span class="px-3 py-1 rounded-full text-[11px] font-black tracking-widest uppercase border"
                              :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }">
                          {{ formatWorkMode(j.work_mode) }}
                        </span>
                        <span
                          v-if="j.facility_city || j.facility_state"
                          class="px-3 py-1 rounded-full text-[11px] font-black tracking-widest uppercase border border-white/10 bg-white/5 text-slate-200"
                        >
                          <span v-if="j.facility_city">{{ j.facility_city }}</span><span v-if="j.facility_city && j.facility_state">, </span><span v-if="j.facility_state">{{ j.facility_state }}</span>
                        </span>
                      </div>
                    </div>

                    <div class="shrink-0 flex flex-col items-end gap-2">
                      <RouterLink
                        :to="{ name: 'public.jobs.apply', params: { id: j.id } }"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-2xl text-xs font-black tracking-widest uppercase border"
                        :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
                        @click.stop
                      >
                        Apply
                      </RouterLink>

                      <span class="text-[11px] text-[color:var(--p-text-muted-color)] opacity-0 group-hover:opacity-100 transition-opacity">View details</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { apiGet } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';

const router = useRouter();
const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const items = ref([]);
const loading = ref(false);
const error = ref('');

const filters = ref({
  q: '',
  org: '',
  specialty: '',
  work_mode: '',
  city: '',
  state: '',
});

function formatWorkMode(v) {
  if (!v) return '—';
  if (v === 'on_site') return 'On-site';
  if (v === 'remote') return 'Remote';
  return String(v);
}

function buildQuery() {
  const q = new URLSearchParams();
  for (const [k, v] of Object.entries(filters.value)) {
    const raw = String(v || '').trim();
    if (raw) q.set(k, raw);
  }
  const s = q.toString();
  return s ? `?${s}` : '';
}

async function refresh() {
  loading.value = true;
  error.value = '';
  try {
    const res = await apiGet(`/public/job-board${buildQuery()}`);
    items.value = Array.isArray(res?.data) ? res.data : [];
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to load jobs.';
  } finally {
    loading.value = false;
  }
}

async function reset() {
  filters.value = { q: '', org: '', specialty: '', work_mode: '', city: '', state: '' };
  await refresh();
}

async function goToDetail(job) {
  if (!job?.id) return;
  await router.push({ name: 'public.jobs.detail', params: { id: job.id } });
}

brand.load();
refresh();
</script>
