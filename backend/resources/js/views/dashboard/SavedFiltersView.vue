<template>
  <div class="space-y-6">
    <Card>
      <template #content>
        <div class="flex items-center justify-between gap-4">
          <div>
            <h2 class="font-display text-2xl">Saved Filters</h2>
            <p class="text-sm text-[color:var(--p-text-muted-color)]">Reusable search criteria for advanced credentialing reports.</p>
          </div>
          <Button label="New Filter" icon="pi pi-plus" disabled severity="secondary" />
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
                        <Button label="Apply" size="small" severity="secondary" outlined disabled />
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
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { apiDelete, apiGet, normalizeApiList } from '../../lib/api';
import Button from 'primevue/button';
import Card from 'primevue/card';
import DataView from 'primevue/dataview';
import Message from 'primevue/message';

const rows = ref([]);
const loading = ref(true);
const error = ref('');

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

onMounted(fetchFilters);
</script>
