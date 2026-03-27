<template>
    <div class="jobs-tab">
        <div v-if="loading" class="flex justify-center py-8">
            <ProgressSpinner />
        </div>
        
        <div v-else-if="jobs.length === 0" class="empty-state">
            <i class="pi pi-briefcase text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-500">No active jobs for this facility.</p>
        </div>
        
        <DataTable v-else :value="jobs" stripedRows responsiveLayout="scroll">
            <Column field="title" header="Job Title" sortable />
            <Column field="status" header="Status" sortable>
                <template #body="{ data }">
                    <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
                </template>
            </Column>
            <Column field="created_at" header="Posted" sortable>
                <template #body="{ data }">
                    {{ formatDate(data.created_at) }}
                </template>
            </Column>
            <Column header="Actions">
                <template #body="{ data }">
                    <Button icon="pi pi-eye" class="p-button-text p-button-sm"
                        @click="viewJob(data.id)" />
                </template>
            </Column>
        </DataTable>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';
import { apiGet } from '../../lib/api';

const props = defineProps({
    facility: {
        type: Object,
        default: () => ({}),
    },
});

const router = useRouter();
const jobs = ref([]);
const loading = ref(false);

onMounted(async () => {
    if (props.facility?.id) {
        await loadJobs();
    }
});

async function loadJobs() {
    loading.value = true;
    try {
        const response = await apiGet(`/jobs?facility_id=${props.facility.id}`);
        jobs.value = response.data?.jobs || [];
    } catch (e) {
        console.error('[JobsTab] Error loading jobs:', e);
    } finally {
        loading.value = false;
    }
}

function viewJob(jobId) {
    router.push({ name: 'dashboard.jobs.detail', params: { id: jobId } });
}

function formatDate(date) {
    if (!date) return '';
    return new Date(date).toLocaleDateString();
}

function getStatusSeverity(status) {
    const map = {
        active: 'success',
        filled: 'info',
        closed: 'secondary',
        draft: 'warning',
    };
    return map[status] || 'secondary';
}
</script>

<style scoped>
.jobs-tab {
    padding: 0.5rem;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
}
</style>
