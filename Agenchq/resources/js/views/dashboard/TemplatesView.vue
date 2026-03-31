<template>
  <div class="space-y-6">
    <Card>
      <template #content>
        <div class="flex items-center justify-between gap-4">
          <div>
            <h2 class="font-display text-2xl">Document Templates</h2>
            <p class="text-sm text-[color:var(--p-text-muted-color)]">Manage standardized credentialing document templates.</p>
          </div>
          <Button label="New Template" icon="pi pi-plus" disabled severity="secondary" />
        </div>
      </template>
    </Card>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <Card>
      <template #content>
        <div v-if="loading" class="py-6 text-[color:var(--p-text-muted-color)]">Loading templates...</div>

        <DataView v-else :value="rows" layout="grid">
          <template #empty>
            <div class="py-6 text-[color:var(--p-text-muted-color)]">No templates found.</div>
          </template>

          <template #grid="slotProps">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <div v-for="t in slotProps.items" :key="String(t.id)">
                <Card class="h-full">
                  <template #content>
                    <div class="flex items-start justify-between gap-3">
                      <div class="flex items-start gap-3">
                        <div class="p-2 rounded-xl bg-[color:var(--p-primary-50)] text-[color:var(--p-primary-color)]">
                          <i class="pi pi-file" />
                        </div>
                        <div>
                          <div class="font-semibold text-lg leading-tight">{{ t.name }}</div>
                          <div class="text-xs uppercase font-bold tracking-widest text-[color:var(--p-text-muted-color)]">{{ t.type }}</div>
                        </div>
                      </div>
                      <Button icon="pi pi-trash" severity="danger" text rounded @click="handleDelete(t.id)" />
                    </div>

                    <div class="mt-4 text-sm text-[color:var(--p-text-muted-color)] italic line-clamp-3">
                      {{ t.content || 'No preview available' }}
                    </div>

                    <Divider class="my-4" />

                    <div class="flex items-center justify-between gap-3">
                      <span class="text-xs text-[color:var(--p-text-muted-color)]">Created {{ formatDate(t.created_at) }}</span>
                      <Button label="Edit Template" size="small" text disabled />
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
import Divider from 'primevue/divider';
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

async function fetchTemplates() {
    try {
        loading.value = true;
        error.value = '';
        const res = await apiGet('/templates');
        rows.value = normalizeApiList(res);
    } catch (e) {
        rows.value = [];
        error.value = e?.response?.data?.message || e?.message || 'Failed to load templates';
    } finally {
        loading.value = false;
    }
}

async function handleDelete(id) {
    if (!window.confirm('Are you sure you want to delete this template?')) return;

    try {
        await apiDelete(`/templates/${encodeURIComponent(String(id))}`);
        await fetchTemplates();
    } catch (e) {
        window.alert(e?.response?.data?.message || e?.message || 'Failed to delete template');
    }
}

onMounted(fetchTemplates);
</script>
