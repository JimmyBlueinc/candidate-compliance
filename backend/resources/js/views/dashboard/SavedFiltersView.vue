<template>
  <div class="space-y-6">
    <Card>
      <template #content>
        <div class="flex items-center justify-between gap-4">
          <div>
            <h2 class="font-display text-2xl">Saved Filters</h2>
            <p class="text-sm text-[color:var(--p-text-muted-color)]">Reusable search criteria for recruiting and pipeline workflows.</p>
          </div>
          <Button label="New Filter" icon="pi pi-plus" severity="secondary" @click="newOpen = true" />
        </div>
      </template>
    </Card>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <Card>
      <template #content>
        <div v-if="loading" class="py-6 text-[color:var(--p-text-muted-color)]">Loading filters...</div>

        <DataView v-else :value="rows" layout="grid">
          <template #empty>
            <div class="py-6 text-[color:var(--p-text-muted-color)]">No saved filters found.</div>
          </template>

          <template #grid="slotProps">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div v-for="f in slotProps.items" :key="String(f.id)">
                <Card>
                  <template #content>
                    <div class="flex items-start justify-between gap-4">
                      <div class="flex items-start gap-3">
                        <div class="p-2 rounded-xl bg-[color:var(--p-primary-50)] text-[color:var(--p-primary-color)]">
                          <i class="pi pi-filter" />
                        </div>
                        <div>
                          <div class="font-semibold">{{ f.name }}</div>
                          <div class="text-xs font-bold uppercase tracking-widest text-[color:var(--p-text-muted-color)] mt-1">
                            Saved {{ formatDate(f.created_at) }}
                          </div>
                        </div>
                      </div>

                      <div class="flex items-center gap-2">
                        <Button label="Apply" size="small" severity="secondary" outlined @click="applyFilter(f)" />
                        <Button icon="pi pi-trash" severity="danger" text rounded @click="handleDelete(f.id)" />
                      </div>
                    </div>
                  </template>
                </Card>
              </div>
            </div>
          </template>
        </DataView>
      </template>
    </Card>

    <Dialog v-model:visible="newOpen" modal header="Save Filter" :style="{ width: 'min(620px, 96vw)' }">
      <div class="space-y-4">
        <div class="space-y-2">
          <label class="text-xs font-semibold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Name</label>
          <InputText v-model="newName" class="w-full" placeholder="e.g. ICU candidates this week" />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-semibold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Context</label>
          <Dropdown v-model="newContext" :options="contextOptions" optionLabel="label" optionValue="value" class="w-full" />
        </div>
        <div class="space-y-2">
          <label class="text-xs font-semibold uppercase tracking-widest text-[color:var(--p-text-muted-color)]">Filters (JSON)</label>
          <Textarea v-model="newFiltersJson" rows="6" class="w-full" />
        </div>
        <Message v-if="saveError" severity="error" :closable="false">{{ saveError }}</Message>
      </div>
      <template #footer>
        <div class="flex items-center gap-2 justify-end">
          <Button label="Cancel" severity="secondary" outlined @click="newOpen = false" />
          <Button label="Save Filter" :loading="saving" @click="saveFilter" />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { apiDelete, apiGet, apiPost, normalizeApiList } from '../../lib/api';
import Button from 'primevue/button';
import Card from 'primevue/card';
import DataView from 'primevue/dataview';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Textarea from 'primevue/textarea';

const router = useRouter();
const rows = ref([]);
const loading = ref(true);
const error = ref('');
const newOpen = ref(false);
const newName = ref('');
const newContext = ref('candidates.list');
const newFiltersJson = ref(JSON.stringify({ query: '' }, null, 2));
const saveError = ref('');
const saving = ref(false);
const contextOptions = [
    { label: 'Candidates List', value: 'candidates.list' },
    { label: 'Pipeline', value: 'pipeline' },
    { label: 'Jobs', value: 'jobs' },
];

function formatDate(value) {
    if (!value) return '—';
    try {
        return new Date(value).toLocaleDateString();
    } catch {
        return String(value);
    }
}

async function fetchFilters() {
    try {
        loading.value = true;
        error.value = '';
        const res = await apiGet('/filters');
        rows.value = normalizeApiList(res);
    } catch (e) {
        rows.value = [];
        error.value = e?.response?.data?.message || e?.message || 'Failed to load filters';
    } finally {
        loading.value = false;
    }
}

async function handleDelete(id) {
    if (!window.confirm('Are you sure you want to delete this filter?')) return;

    try {
        await apiDelete(`/filters/${encodeURIComponent(String(id))}`);
        await fetchFilters();
    } catch (e) {
        window.alert(e?.response?.data?.message || e?.message || 'Failed to delete filter');
    }
}

async function saveFilter() {
    saveError.value = '';
    let parsed;
    try {
        parsed = JSON.parse(newFiltersJson.value || '{}');
    } catch {
        saveError.value = 'Invalid JSON format.';
        return;
    }

    if (!newName.value.trim()) {
        saveError.value = 'Filter name is required.';
        return;
    }

    try {
        saving.value = true;
        await apiPost('/filters', {
            name: newName.value.trim(),
            filters: {
                context: newContext.value,
                ...parsed,
            },
        });
        newOpen.value = false;
        newName.value = '';
        newFiltersJson.value = JSON.stringify({ query: '' }, null, 2);
        await fetchFilters();
    } catch (e) {
        saveError.value = e?.response?.data?.message || e?.message || 'Failed to save filter.';
    } finally {
        saving.value = false;
    }
}

function applyFilter(filter) {
    const context = String(filter?.filters?.context || '');
    if (context === 'candidates.list') {
        router.push({ name: 'dashboard.candidates', query: { saved_filter_id: String(filter.id) } });
        return;
    }
    if (context === 'pipeline') {
        router.push({ name: 'dashboard.pipeline', query: { saved_filter_id: String(filter.id) } });
        return;
    }
    router.push({ name: 'dashboard.candidates', query: { saved_filter_id: String(filter.id) } });
}

onMounted(fetchFilters);
</script>
