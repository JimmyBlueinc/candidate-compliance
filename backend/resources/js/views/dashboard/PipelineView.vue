<template>
  <div class="space-y-6">
    <div class="glass-dark rounded-[32px] p-8 border border-white/5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="font-display text-2xl text-white">Candidate Pipeline</h2>
          <p class="text-sm text-[color:var(--p-text-muted-color)] mt-1">Track and manage candidate screening workflow.</p>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
            :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
            @click="refresh"
          >
            Refresh
          </button>
        </div>
      </div>

      <div v-if="loading" class="mt-6 text-sm text-[color:var(--p-text-muted-color)] flex items-center gap-2">
        <i class="pi pi-spin pi-spinner"></i>
        Loading...
      </div>

      <div v-else class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
          <div class="text-[10px] uppercase tracking-widest font-black text-[color:var(--p-text-muted-color)]">Tracked Candidates</div>
          <div class="mt-2 text-2xl font-semibold text-white">{{ funnel.total_candidates }}</div>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
          <div class="text-[10px] uppercase tracking-widest font-black text-[color:var(--p-text-muted-color)]">Transitions ({{ funnel.days }}d)</div>
          <div class="mt-2 text-2xl font-semibold text-indigo-300">{{ funnel.total_transitions }}</div>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
          <div class="text-[10px] uppercase tracking-widest font-black text-[color:var(--p-text-muted-color)]">Top Conversion</div>
          <div class="mt-2 text-sm font-semibold text-emerald-300">
            {{ topTransitionLabel }}
          </div>
        </div>
      </div>

      <PipelineBoard v-if="!loading" class="mt-6">
        <PipelineColumn
          v-for="col in columns"
          :key="col.id"
          :id="col.id"
          :label="col.label"
          :count="grouped[col.id]?.length || 0"
          :active="col.id === 'screening'"
          :primary-color="primaryColor"
          :primary-soft-bg="primarySoftBg"
          :primary-soft-border="primarySoftBorder"
        >
          <PipelineCard
            v-for="p in grouped[col.id]"
            :key="p.id"
            :candidate="p.candidate"
            :primary-color="primaryColor"
            :primary-soft-bg="primarySoftBg"
            :primary-soft-border="primarySoftBorder"
            :has-prev="!!prevStage(col.id)"
            :has-next="!!nextStage(col.id)"
            @move="(dir) => handleMove(p, dir)"
            @action="(type) => handleAction(p, type)"
          />
        </PipelineColumn>
      </PipelineBoard>
    </div>
  </div>

  <Dialog v-model:visible="noteModalOpen" modal header="Add Note" :style="{ width: '400px' }">
    <div class="space-y-4 pt-2">
      <Textarea v-model="newNote" rows="4" class="w-full bg-white/5 border-white/10" placeholder="Type note here..." />
      <div class="flex justify-end gap-2">
        <button class="px-3 py-1.5 rounded-lg border border-white/10 text-xs" @click="noteModalOpen = false">Cancel</button>
        <button 
          class="px-3 py-1.5 rounded-lg text-xs font-bold text-white" 
          :style="{ backgroundColor: primaryColor }"
          :disabled="acting"
          @click="submitNote"
        >
          Save Note
        </button>
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import { apiGet, apiPut, apiPost, normalizeApiList } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';
import PipelineBoard from '../../components/pipeline/PipelineBoard.vue';
import PipelineColumn from '../../components/pipeline/PipelineColumn.vue';
import PipelineCard from '../../components/pipeline/PipelineCard.vue';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';

const brand = useBrandStore();
const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const STAGES = ['new', 'screening', 'credential_pending', 'credential_complete', 'ready_to_submit'];
const columns = [
  { id: 'new', label: 'New' },
  { id: 'screening', label: 'Screening' },
  { id: 'credential_pending', label: 'Credential Pending' },
  { id: 'credential_complete', label: 'Credential Complete' },
  { id: 'ready_to_submit', label: 'Ready to Submit' },
];

const items = ref([]);
const loading = ref(false);
const acting = ref(false);
const noteModalOpen = ref(false);
const newNote = ref('');
const selectedItem = ref(null);
const funnel = ref({
  days: 30,
  total_candidates: 0,
  total_transitions: 0,
  transitions: [],
});

const grouped = computed(() => {
  const out = {};
  STAGES.forEach(s => out[s] = []);
  items.value.forEach(item => {
    const stage = item.stage || 'new';
    if (out[stage]) out[stage].push(item);
  });
  return out;
});

function nextStage(stage) {
  const i = STAGES.indexOf(stage);
  return i >= 0 && i < STAGES.length - 1 ? STAGES[i + 1] : null;
}

function prevStage(stage) {
  const i = STAGES.indexOf(stage);
  return i > 0 ? STAGES[i - 1] : null;
}

async function handleMove(item, dir) {
  const targetStage = dir === 'next' ? nextStage(item.stage) : prevStage(item.stage);
  if (!targetStage) return;
  
  try {
    acting.value = true;
    await apiPut(`/v1/candidate-pipeline/${item.candidate_id}/stage`, { stage: targetStage });
    await refresh();
  } finally {
    acting.value = false;
  }
}

function handleAction(item, type) {
  if (type === 'menu') {
    selectedItem.value = item;
    noteModalOpen.value = true;
    newNote.value = '';
  }
}

async function submitNote() {
  if (!selectedItem.value || !newNote.value.trim()) return;
  try {
    acting.value = true;
    await apiPost(`/v1/candidate-pipeline/${selectedItem.value.candidate_id}/notes`, { note: newNote.value });
    noteModalOpen.value = false;
    await refresh();
  } finally {
    acting.value = false;
  }
}

async function refresh() {
  loading.value = true;
  try {
    const [pipelineRes, funnelRes] = await Promise.all([
      apiGet('/v1/candidate-pipeline'),
      apiGet('/analytics/pipeline-funnel?days=30'),
    ]);
    items.value = normalizeApiList(pipelineRes);
    const data = funnelRes?.data || {};
    const transitions = Array.isArray(data.transitions) ? data.transitions : [];
    const stageCounts = Array.isArray(data.stage_counts) ? data.stage_counts : [];
    funnel.value = {
      days: Number(data.days || 30),
      total_candidates: stageCounts.reduce((sum, row) => sum + Number(row.count || 0), 0),
      total_transitions: transitions.reduce((sum, row) => sum + Number(row.count || 0), 0),
      transitions,
    };
  } finally {
    loading.value = false;
  }
}

const topTransitionLabel = computed(() => {
  const t = [...(funnel.value.transitions || [])]
    .sort((a, b) => Number(b.count || 0) - Number(a.count || 0))[0];
  if (!t) return 'No recent transitions';
  return `${String(t.from_stage || 'n/a')} -> ${String(t.to_stage || 'n/a')} (${Number(t.conversion_pct || 0)}%)`;
});

onMounted(refresh);
</script>
