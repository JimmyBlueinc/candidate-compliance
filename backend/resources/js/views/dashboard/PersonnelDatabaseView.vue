<template>
  <div class="space-y-6">
    <Card>
      <template #content>
        <div class="flex items-start justify-between gap-6">
          <div>
            <h2 class="font-display text-2xl">Personnel Database</h2>
            <p class="text-sm text-[color:var(--p-text-muted-color)]">Organization-wide credential roster.</p>
          </div>
          <Button label="Refresh" icon="pi pi-refresh" severity="secondary" outlined @click="load" />
        </div>
      </template>
    </Card>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <Card>
      <template #content>
        <DataTable :value="rows" :loading="loading" stripedRows responsiveLayout="scroll">
          <Column field="candidate_name" header="Name">
            <template #body="{ data }">
              <span class="font-medium">{{ data.candidate_name || '—' }}</span>
            </template>
          </Column>
          <Column field="position" header="Position">
            <template #body="{ data }">{{ data.position || '—' }}</template>
          </Column>
          <Column field="credential_type" header="Credential">
            <template #body="{ data }">{{ data.credential_type || '—' }}</template>
          </Column>
          <Column header="Status" style="width: 1%; white-space: nowrap">
            <template #body="{ data }">
              <Tag :severity="String(data.status || '').toLowerCase() === 'active' ? 'success' : 'warning'" :value="data.status || 'unknown'" />
            </template>
          </Column>
          <Column header="Actions" style="width: 1%; white-space: nowrap">
            <template #body="{ data }">
              <Button label="View Details" size="small" text @click="goToCredential(data)" />
            </template>
          </Column>

          <template #empty>
            <div class="py-6 text-[color:var(--p-text-muted-color)]">No records found</div>
          </template>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { apiGet } from '../../lib/api';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Message from 'primevue/message';
import Tag from 'primevue/tag';

const router = useRouter();

const rows = ref([]);
const loading = ref(true);
const error = ref('');

async function load() {
    try {
        loading.value = true;
        error.value = '';
        const response = await apiGet('/credentials', { params: { per_page: 50 } });
        rows.value = response?.data || [];
    } catch {
        rows.value = [];
        error.value = 'Failed to load credentials';
    } finally {
        loading.value = false;
    }
}

function goToCredential(row) {
    router.push({ name: 'dashboard.credentials', query: { id: String(row.id) } });
}

onMounted(load);
</script>
