<template>
  <div class="space-y-6">
    <div class="aq-card p-6">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="font-display text-2xl text-[color:var(--p-text-color)]">Placement Pipeline</h2>
          <p class="text-sm text-[color:var(--p-text-muted-color)] mt-1">Track candidates from Applied to Placed. When moved to Placed, an active placement (assignment) is created.</p>
        </div>
        <div class="flex items-center gap-2">
          <Dropdown
            v-model="recruiterFilter"
            :options="recruiters"
            optionLabel="name"
            optionValue="id"
            placeholder="All recruiters"
            showClear
            class="min-w-[220px]"
          />

          <span class="p-input-icon-left">
            <i class="material-symbols-outlined">search</i>
            <InputText v-model="globalQuery" placeholder="Search candidate, facility, job..." />
          </span>

          <Button type="button" label="Refresh" size="small" outlined :loading="loading" @click="refresh" />
        </div>
      </div>

      <div v-if="loading" class="mt-6 text-sm text-[color:var(--p-text-muted-color)]">Loading...</div>

      <div v-else class="mt-6 aq-table-shell">
        <DataTable
          :value="pagedItems"
          class="aq-table"
          dataKey="id"
          :paginator="true"
          :rows="pageSize"
          :rowsPerPageOptions="[10, 25, 50, 100]"
          :first="first"
          @page="onPage"
          responsiveLayout="scroll"
        >
          <Column header="Candidate" style="min-width: 220px">
            <template #body="{ data }">
              <div class="min-w-0">
                <RouterLink
                  :to="{ name: 'dashboard.placement_detail', params: { id: data.id } }"
                  class="font-semibold text-[color:var(--p-text-color)] hover:underline block truncate"
                >
                  {{ candidateName(data) }}
                </RouterLink>
                <div class="text-xs text-[color:var(--p-text-muted-color)] truncate">{{ data.candidate?.email || '—' }}</div>
              </div>
            </template>
          </Column>

          <Column header="Job / Facility" style="min-width: 260px">
            <template #body="{ data }">
              <div class="min-w-0">
                <div class="text-sm font-semibold text-[color:var(--p-text-color)] truncate">{{ data.job_order?.title || 'Job Order' }}</div>
                <div class="text-xs text-[color:var(--p-text-muted-color)] truncate">{{ data.job_order?.facility_name || '—' }}</div>
              </div>
            </template>
          </Column>

          <Column header="Recruiter" style="min-width: 180px">
            <template #body="{ data }">
              <span class="text-sm text-[color:var(--p-text-color)]">{{ data.recruiter?.name || '—' }}</span>
            </template>
          </Column>

          <Column header="Stage" style="min-width: 140px">
            <template #body="{ data }">
              <Tag :value="stageLabel(data.stage)" :severity="stageSeverity(data.stage)" />
            </template>
          </Column>

          <Column header="Margin" style="min-width: 120px">
            <template #body="{ data }">
              <span class="font-bold" :style="{ color: marginColor(data.margin) }">{{ formatMoney(data.margin) }}</span>
            </template>
          </Column>

          <Column header="Actions" style="min-width: 220px">
            <template #body="{ data }">
              <div class="flex items-center gap-2 justify-end">
                <Button
                  type="button"
                  label="Back"
                  size="small"
                  outlined
                  :disabled="actingId === data.id || !prevStage(data.stage)"
                  @click="move(data, prevStage(data.stage))"
                />
                <Button
                  type="button"
                  :label="actingId === data.id ? 'Moving…' : 'Next'"
                  size="small"
                  :disabled="actingId === data.id || !nextStage(data.stage)"
                  :loading="actingId === data.id"
                  @click="move(data, nextStage(data.stage))"
                />
              </div>
            </template>
          </Column>
        </DataTable>

        <div v-if="filteredItems.length === 0" class="p-6 text-sm text-[color:var(--p-text-muted-color)]">
          No placements.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { apiGet, apiPut, normalizeApiList } from '../../lib/api';
import { useBrandStore } from '../../stores/brand';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';

const brand = useBrandStore();

const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const items = ref([]);
const loading = ref(false);
const actingId = ref(null);
const recruiterFilter = ref(null);
const globalQuery = ref('');

const pageSize = ref(25);
const first = ref(0);

const filteredItems = computed(() => {
    let out = items.value;

    if (recruiterFilter.value) {
        out = out.filter((p) => String(p?.recruiter?.id || '') === String(recruiterFilter.value));
    }

    const q = String(globalQuery.value || '').trim().toLowerCase();
    if (q) {
        out = out.filter((p) => {
            const parts = [
                candidateName(p),
                p?.candidate?.email,
                p?.recruiter?.name,
                p?.job_order?.title,
                p?.job_order?.facility_name,
                p?.stage,
            ];
            return parts.filter(Boolean).some((v) => String(v).toLowerCase().includes(q));
        });
    }

    return out;
});

const recruiters = computed(() => {
    const map = new Map();
    for (const p of items.value) {
        const r = p?.recruiter;
        if (r && r.id) map.set(String(r.id), { id: r.id, name: r.name });
    }
    return Array.from(map.values()).sort((a, b) => String(a.name || '').localeCompare(String(b.name || '')));
});

const pagedItems = computed(() => filteredItems.value);

function onPage(e) {
    first.value = e.first;
    pageSize.value = e.rows;
}

function candidateName(p) {
    return (
        p?.candidate?.name ||
        `${p?.candidate?.first_name || ''} ${p?.candidate?.last_name || ''}`.trim() ||
        'Candidate'
    );
}

function formatMoney(v) {
    const n = Number(v || 0);
    const sign = n < 0 ? '-' : '';
    return `${sign}$${Math.abs(n).toFixed(2)}`;
}

function marginColor(v) {
    const n = Number(v || 0);
    if (n < 0) return 'rgb(239, 68, 68)';
    if (n === 0) return 'var(--p-text-muted-color)';
    return 'rgb(34, 197, 94)';
}

function stageLabel(stage) {
    const s = String(stage || '').toLowerCase();
    if (!s) return 'Applied';
    return s.charAt(0).toUpperCase() + s.slice(1);
}

function stageSeverity(stage) {
    const s = String(stage || '').toLowerCase();
    if (s === 'placed') return 'success';
    if (s === 'offered') return 'warning';
    if (s === 'interviewing') return 'info';
    if (s === 'submitted') return 'secondary';
    return 'secondary';
}

const STAGES = ['applied', 'submitted', 'interviewing', 'offered', 'placed', 'active'];

function nextStage(stage) {
    const i = STAGES.indexOf(String(stage || ''));
    if (i < 0) return null;
    if (i >= 4) return null; // stop at placed in UI
    return STAGES[i + 1];
}

function prevStage(stage) {
    const i = STAGES.indexOf(String(stage || ''));
    if (i <= 0) return null;
    if (i > 4) return 'placed';
    return STAGES[i - 1];
}

async function move(p, stage) {
    if (!stage) return;
    actingId.value = p.id;
    try {
        await apiPut(`/v1/placements/${p.id}/stage`, { stage });
        await refresh();
    } finally {
        actingId.value = null;
    }
}

async function refresh() {
    loading.value = true;
    try {
        const res = await apiGet('/v1/placements/board');
        items.value = normalizeApiList(res);
        first.value = 0;
    } finally {
        loading.value = false;
    }
}

refresh();
</script>
