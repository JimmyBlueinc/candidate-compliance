<template>
  <div class="space-y-6">
    <div class="aq-card p-6">
      <div class="flex items-start justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <RouterLink :to="{ name: 'dashboard.placements' }" class="text-xs font-black tracking-widest uppercase text-primary hover:underline">
              Placements
            </RouterLink>
            <span class="text-slate-600 text-xs">/</span>
            <span class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">
              Placement Details
            </span>
          </div>
          <h2 class="font-display text-2xl text-white">Placement Pipeline Details</h2>
        </div>
        <div class="flex items-center gap-2">
          <Button 
            type="button" 
            label="Refresh" 
            size="small" 
            outlined 
            :loading="loading" 
            @click="load" 
          />
        </div>
      </div>

      <div v-if="error" class="mt-6 text-sm text-red-400">{{ error }}</div>

      <div v-if="loading && !placement" class="mt-8 text-slate-400">Loading placement details...</div>

      <div v-else-if="placement" class="mt-8 space-y-8">
        <!-- Status & Stage Banner -->
        <div class="aq-card p-6 flex flex-wrap items-center gap-4">
          <div class="flex-1 min-w-[200px]">
            <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)]">Current Stage</div>
            <div class="mt-1 flex items-center gap-2">
              <span class="text-xl font-display text-white capitalize">{{ placement.stage }}</span>
              <Tag :value="placement.stage === 'placed' ? 'Ready' : 'In Progress'" :severity="placement.stage === 'placed' ? 'success' : 'info'" />
            </div>
          </div>
          <div class="shrink-0">
            <div class="text-[10px] font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)] text-right">Start Date</div>
            <div class="mt-1 text-lg font-bold text-white text-right">{{ fmtDate(placement.start_date) }}</div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Candidate & Job Info -->
          <div class="space-y-6">
            <div class="aq-card p-6 h-full">
              <h3 class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)] mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">person</span>
                Candidate & Job
              </h3>
              <div class="space-y-4">
                <div v-if="placement.candidate">
                  <div class="text-[10px] text-slate-500 uppercase font-black">Candidate</div>
                  <RouterLink :to="{ name: 'dashboard.candidate_profile', params: { id: placement.candidate.id } }" class="text-primary hover:underline font-bold text-lg">
                    {{ placement.candidate.name }}
                  </RouterLink>
                  <div class="text-xs text-slate-400 mt-1">{{ placement.candidate.email }}</div>
                </div>
                <div v-if="placement.job_order">
                  <div class="text-[10px] text-slate-500 uppercase font-black mt-4">Job Order</div>
                  <div class="text-white font-bold">{{ placement.job_order.title }}</div>
                  <div class="text-xs text-slate-400 mt-1">{{ placement.job_order.facility_name }}</div>
                </div>
                <div>
                  <div class="text-[10px] text-slate-500 uppercase font-black mt-4">Recruiter</div>
                  <div class="text-white">{{ placement.recruiter?.name || 'Unassigned' }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Assignment & Logistics -->
          <div class="space-y-6">
            <div class="aq-card p-6 h-full">
              <h3 class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)] mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">assignment</span>
                Active Placement (Assignment)
              </h3>
              <div v-if="placement.assignment" class="space-y-4">
                <div class="flex justify-between items-center border-b border-white/5 pb-2">
                  <span class="text-xs text-slate-400">Assignment ID</span>
                  <span class="text-xs font-mono text-white">#{{ placement.assignment.id }}</span>
                </div>
                <div class="flex justify-between items-center border-b border-white/5 pb-2">
                  <span class="text-xs text-slate-400">Status</span>
                  <Tag :value="placement.assignment.status" :severity="assignmentStatusSeverity(placement.assignment.status)" />
                </div>
                <div class="flex justify-between items-center border-b border-white/5 pb-2">
                  <span class="text-xs text-slate-400">Bill Rate</span>
                  <span class="text-sm font-bold text-white">{{ money(placement.assignment.bill_rate) }} / hr</span>
                </div>
                <div class="flex justify-between items-center border-b border-white/5 pb-2">
                  <span class="text-xs text-slate-400">Pay Rate</span>
                  <span class="text-sm font-bold text-white">{{ money(placement.assignment.pay_rate) }} / hr</span>
                </div>
                <div class="pt-2">
                  <RouterLink 
                    :to="{ name: 'dashboard.logistics_detail', params: { id: placement.id } }"
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-primary/10 border border-primary/20 text-primary text-xs font-bold hover:bg-primary/20 transition-colors"
                  >
                    <span class="material-symbols-outlined text-sm">local_shipping</span>
                    Manage Logistics
                  </RouterLink>
                </div>
              </div>
              <div v-else class="h-full flex flex-col items-center justify-center py-8 text-center">
                <span class="material-symbols-outlined text-slate-600 text-4xl mb-2">pending_actions</span>
                <p class="text-sm text-slate-500">Active placement (assignment) will be created once moved to 'Placed' stage.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Submission History -->
        <div v-if="placement.submission" class="aq-card p-6">
          <h3 class="text-xs font-black tracking-widest uppercase text-[color:var(--p-text-muted-color)] mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">description</span>
            Originating Submission
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
              <div class="text-[10px] text-slate-500 uppercase font-black">Submission Date</div>
              <div class="text-white">{{ fmtDate(placement.submission.created_at) }}</div>
            </div>
            <div>
              <div class="text-[10px] text-slate-500 uppercase font-black">Initial Status</div>
              <div class="text-white capitalize">{{ placement.submission.status }}</div>
            </div>
            <div>
              <div class="text-[10px] text-slate-500 uppercase font-black">Submission ID</div>
              <div class="text-white font-mono text-xs">#{{ placement.submission.id }}</div>
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
import { useBrandStore } from '../../stores/brand';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const route = useRoute();
const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');

const placement = ref(null);
const loading = ref(false);
const error = ref('');

function money(v) {
  const n = Number(v || 0);
  return `$${n.toFixed(2)}`;
}

function fmtDate(v) {
  if (!v) return '—';
  try {
    const d = new Date(String(v));
    if (isNaN(d.getTime())) return String(v);
    return d.toLocaleDateString();
  } catch {
    return String(v);
  }
}

function assignmentStatusSeverity(status) {
  const s = String(status || '').toLowerCase();
  if (s === 'active') return 'success';
  if (s === 'pending') return 'warning';
  if (s === 'completed') return 'info';
  if (s === 'cancelled') return 'danger';
  return 'secondary';
}

async function load() {
  const id = route.params.id;
  if (!id) return;

  loading.value = true;
  error.value = '';
  try {
    const res = await apiGet(`/v1/placements/${id}`);
    placement.value = res?.data || res;
  } catch (e) {
    error.value = e?.message || 'Failed to load placement details';
  } finally {
    loading.value = false;
  }
}

onMounted(load);
</script>
