<template>
  <div class="space-y-6">
    <div class="glass-dark rounded-[32px] p-8 border border-white/5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="font-display text-2xl text-white">Candidate Search</h2>
          <p class="text-sm text-[color:var(--p-text-muted-color)] mt-1">Find and qualify candidates across your pipeline.</p>
        </div>
        <button
          type="button"
          class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
          :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
          @click="refresh"
        >
          Search
        </button>
      </div>

      <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="p-6 rounded-2xl bg-white/[0.03] border border-white/5 lg:col-span-1">
          <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Filters</div>

          <div class="mt-4 space-y-4">
            <div class="space-y-2">
              <label class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Specialty</label>
              <input
                v-model="filters.specialty"
                class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white placeholder:text-slate-500 outline-none focus:border-white/20"
                placeholder="ICU, Med-Surg, ER"
              />
            </div>

            <div class="space-y-2">
              <label class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Compliance Status</label>
              <select
                v-model="filters.compliance_status"
                class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white outline-none focus:border-white/20"
              >
                <option value="">Any</option>
                <option value="ready">Ready-to-Work</option>
                <option value="in_progress">In Progress</option>
                <option value="blocked">Blocked</option>
              </select>
            </div>

            <div class="space-y-2">
              <label class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Zip Code</label>
              <input
                v-model="filters.zip"
                inputmode="numeric"
                class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white placeholder:text-slate-500 outline-none focus:border-white/20"
                placeholder="90210"
              />
            </div>

            <div class="space-y-2">
              <label class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Job Order Match</label>
              <select
                v-model="filters.job_order_id"
                class="w-full px-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white outline-none focus:border-white/20"
              >
                <option value="">No job context</option>
                <option v-for="job in jobOrders" :key="job.id" :value="String(job.id)">
                  {{ job.facility_name || 'Facility' }} - {{ job.title || 'Job' }}
                </option>
              </select>
            </div>

            <div class="flex items-center gap-2">
              <button
                type="button"
                class="px-4 py-3 rounded-2xl text-xs font-black tracking-widest uppercase border transition-colors"
                :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
                :disabled="loading"
                @click="refresh"
              >
                {{ loading ? 'Searching…' : 'Apply' }}
              </button>
              <button
                type="button"
                class="px-4 py-3 rounded-2xl text-xs font-black tracking-widest uppercase border border-white/10 bg-white/5 text-slate-200 hover:bg-white/10"
                :disabled="loading"
                @click="reset"
              >
                Reset
              </button>
            </div>

            <div v-if="error" class="text-sm text-red-400">{{ error }}</div>
          </div>
        </div>

        <div class="p-6 rounded-2xl bg-white/[0.03] border border-white/5 lg:col-span-2">
          <div class="flex items-center justify-between gap-4">
            <div class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Results</div>
            <div class="text-xs text-[color:var(--p-text-muted-color)]">{{ rows.length }} candidates</div>
          </div>

          <div v-if="loading" class="mt-4 text-sm text-[color:var(--p-text-muted-color)]">Loading…</div>

          <div v-else class="mt-4 space-y-3">
            <div v-if="rows.length === 0" class="text-sm text-[color:var(--p-text-muted-color)]">No matches.</div>

            <button
              v-for="c in rows"
              :key="c.id"
              type="button"
              class="w-full text-left p-4 rounded-2xl border border-white/5 bg-white/[0.02] hover:bg-white/[0.04] transition-colors"
              @click="openCandidate(c)"
            >
              <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                  <div class="font-semibold text-white truncate">{{ displayName(c) }}</div>
                  <div class="mt-1 text-xs text-[color:var(--p-text-muted-color)] truncate">{{ c.email || '—' }}</div>
                  <div class="mt-2 text-xs text-slate-300 truncate">
                    {{ c.specialty || '—' }}
                    <span class="opacity-40">•</span>
                    {{ c.zip || c.postal_code || '—' }}
                  </div>
                </div>

                <div class="shrink-0 text-right">
                  <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Match</div>
                  <div class="mt-1 text-xs font-bold text-emerald-300">{{ Number(c.match_score || 0) }}</div>
                  <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Compliance</div>
                  <div class="mt-1 text-xs font-bold" :style="{ color: complianceColor(c.compliance_status) }">
                    {{ (c.compliance_status || 'unknown').replaceAll('_', ' ') }}
                  </div>
                </div>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>

    <Dialog v-model:visible="detailOpen" modal header="Candidate" :style="{ width: 'min(760px, 95vw)' }">
      <div v-if="selected" class="space-y-3">
        <div class="rounded-2xl border border-[color:var(--p-surface-border)] p-3">
          <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Name</div>
          <div class="mt-1 font-semibold break-all">{{ displayName(selected) }}</div>
        </div>
        <div class="rounded-2xl border border-[color:var(--p-surface-border)] p-3">
          <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Email</div>
          <div class="mt-1 font-semibold break-all">{{ selected.email || '—' }}</div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div class="rounded-2xl border border-[color:var(--p-surface-border)] p-3">
            <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Specialty</div>
            <div class="mt-1 font-semibold">{{ selected.specialty || '—' }}</div>
          </div>
          <div class="rounded-2xl border border-[color:var(--p-surface-border)] p-3">
            <div class="text-[10px] uppercase tracking-[0.25em] text-[color:var(--p-text-muted-color)] font-black">Compliance</div>
            <div class="mt-1 font-semibold" :style="{ color: complianceColor(selected.compliance_status) }">{{ (selected.compliance_status || 'unknown').replaceAll('_', ' ') }}</div>
          </div>
        </div>

        <div class="flex justify-end gap-2">
          <Button type="button" label="View Profile" size="small" @click="goProfile" />
          <Button type="button" label="Close" size="small" severity="secondary" outlined @click="detailOpen = false" />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import { apiGet, normalizeApiList } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';

const router = useRouter();
const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const filters = reactive({
    specialty: '',
    compliance_status: '',
    zip: '',
    job_order_id: '',
});

const loading = ref(false);
const error = ref('');
const rows = ref([]);
const jobOrders = ref([]);

const selected = ref(null);
const detailOpen = ref(false);

async function refresh() {
    loading.value = true;
    error.value = '';
    try {
        const params = new URLSearchParams();
        if (filters.specialty) params.set('specialty', filters.specialty);
        if (filters.compliance_status) params.set('compliance_status', filters.compliance_status);
        if (filters.zip) params.set('zip', filters.zip);
        if (filters.job_order_id) params.set('job_order_id', filters.job_order_id);
        params.set('sort_match', '1');

        const res = await apiGet(`/v1/candidates/search?${params.toString()}`);
        rows.value = normalizeApiList(res);
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Search failed.';
        rows.value = [];
    } finally {
        loading.value = false;
    }
}

function reset() {
    filters.specialty = '';
    filters.compliance_status = '';
    filters.zip = '';
    filters.job_order_id = '';
    rows.value = [];
    error.value = '';
}

async function loadJobOrders() {
    try {
        const res = await apiGet('/v1/job-orders');
        jobOrders.value = normalizeApiList(res).slice(0, 200);
    } catch {
        jobOrders.value = [];
    }
}

function displayName(c) {
    return c?.name || `${c?.first_name || ''} ${c?.last_name || ''}`.trim() || 'Candidate';
}

function complianceColor(status) {
    const s = String(status || '').toLowerCase();
    if (s === 'ready' || s === 'ready-to-work' || s === 'ready_to_work') return 'rgb(34, 197, 94)';
    if (s === 'blocked' || s === 'rejected') return 'rgb(239, 68, 68)';
    if (s === 'in_progress' || s === 'pending') return primaryColor.value;
    return 'var(--p-text-muted-color)';
}

function openCandidate(c) {
    selected.value = c;
    detailOpen.value = true;
}

function goProfile() {
    if (!selected.value?.id) return;
    detailOpen.value = false;
    router.push({ name: 'dashboard.candidate_profile', params: { id: selected.value.id } });
}

loadJobOrders();
</script>
