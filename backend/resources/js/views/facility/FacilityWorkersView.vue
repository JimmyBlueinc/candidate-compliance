<template>
  <div class="space-y-8">
    <UiPageHeader
      title="Workers"
      subtitle="Active workers assigned to your facility, with upcoming shifts."
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
          <Column field="candidate.name" header="Worker" sortable>
            <template #body="{ data }">
              <div class="flex flex-col">
                <span class="font-semibold text-white">{{ data.candidate?.name || 'Unknown' }}</span>
                <span class="text-xs text-[color:var(--p-text-muted-color)]">{{ data.candidate?.specialty }}</span>
              </div>
            </template>
          </Column>

          <Column field="role" header="Role" sortable>
            <template #body="{ data }">
              <span class="text-slate-300 text-xs">{{ data.role || '—' }}</span>
            </template>
          </Column>

          <Column field="start_date" header="Start" sortable>
            <template #body="{ data }">
              <span class="text-slate-300 text-xs">{{ data.start_date || '—' }}</span>
            </template>
          </Column>

          <Column header="Upcoming Shifts">
            <template #body="{ data }">
              <div class="space-y-1">
                <div
                  v-for="s in (data.upcoming_shifts || []).slice(0, 3)"
                  :key="s.id"
                  class="text-xs text-slate-300"
                >
                  <span class="text-slate-400">{{ fmtDateTime(s.starts_at) }}</span>
                  <span class="text-slate-600">→</span>
                  <span class="text-slate-400">{{ fmtDateTime(s.ends_at) }}</span>
                  <Tag class="ml-2" :value="String(s.status || 'unknown')" severity="info" />
                </div>
                <div v-if="!data.upcoming_shifts || data.upcoming_shifts.length === 0" class="text-xs text-slate-500">—</div>
              </div>
            </template>
          </Column>

          <template #empty>
            <div class="py-6 text-center text-slate-500">No active workers found.</div>
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

async function refresh() {
  loading.value = true;
  try {
    const res = await apiGet('/v1/facility/workers');
    rows.value = normalizeApiList(res);
  } finally {
    loading.value = false;
  }
}

onMounted(refresh);
</script>
