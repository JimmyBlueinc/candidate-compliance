<template>
  <div class="space-y-8">
    <UiPageHeader
      title="Shifts"
      subtitle="Read-only visibility into shifts scheduled for your facility."
    >
      <template #actions>
        <button
          type="button"
          class="px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
          :style="{ backgroundColor: primarySoftBg, borderColor: primarySoftBorder, color: primaryColor }"
          @click="refresh"
        >
          Refresh
        </button>
      </template>
    </UiPageHeader>

    <UiCard
      v-motion
      :initial="{ opacity: 0, y: 10 }"
      :enter="{ opacity: 1, y: 0, transition: { duration: 0.35 } }"
      class="p-6"
    >
      <DataTable
        :value="rows"
        :loading="loading"
        dataKey="id"
        class="p-datatable-sm"
        responsiveLayout="stack"
        breakpoint="960px"
      >
          <Column field="starts_at" header="Start" sortable>
            <template #body="{ data }">
              <span class="text-slate-300 text-xs">{{ fmtDateTime(data.starts_at) }}</span>
            </template>
          </Column>
          <Column field="ends_at" header="End" sortable>
            <template #body="{ data }">
              <span class="text-slate-300 text-xs">{{ fmtDateTime(data.ends_at) }}</span>
            </template>
          </Column>
          <Column field="candidate.name" header="Worker" sortable>
            <template #body="{ data }">
              <div class="flex flex-col">
                <span class="font-semibold text-white">{{ data.candidate?.name || 'Unassigned' }}</span>
                <span class="text-xs text-[color:var(--p-text-muted-color)]">{{ data.candidate?.specialty }}</span>
              </div>
            </template>
          </Column>
          <Column field="status" header="Status" sortable>
            <template #body="{ data }">
              <Tag :value="String(data.status || 'unknown')" :severity="statusSeverity(data.status)" />
            </template>
          </Column>

          <template #empty>
            <div class="py-6 text-center text-slate-500">No shifts found.</div>
          </template>
      </DataTable>
    </UiCard>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { apiGet, normalizeApiList } from '../../lib/api';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import UiCard from '../../components/ui/UiCard.vue';
import UiPageHeader from '../../components/ui/UiPageHeader.vue';
import { useBrandStore } from '../../stores/brand';

const brand = useBrandStore();
const primaryColor = computed(() => brand.primaryColor || 'var(--brand-primary, var(--p-primary-color))');
const primarySoftBg = computed(() => `color-mix(in srgb, ${primaryColor.value} 14%, transparent)`);
const primarySoftBorder = computed(() => `color-mix(in srgb, ${primaryColor.value} 28%, transparent)`);

const rows = ref([]);
const loading = ref(false);

function fmtDateTime(v) {
  if (!v) return '—';
  try {
    const d = new Date(String(v));
    if (Number.isNaN(d.getTime())) return String(v);
    return d.toLocaleString();
  } catch {
    return String(v);
  }
}

function statusSeverity(status) {
  const s = String(status || '').toLowerCase();
  if (s === 'completed') return 'success';
  if (s === 'cancelled' || s === 'canceled') return 'danger';
  if (s === 'open') return 'warning';
  return 'info';
}

async function refresh() {
  loading.value = true;
  try {
    const res = await apiGet('/v1/facility/shifts');
    rows.value = normalizeApiList(res);
  } finally {
    loading.value = false;
  }
}

onMounted(refresh);
</script>
