<template>
    <div class="placements-tab">
        <div v-if="loading" class="flex justify-center py-8">
            <ProgressSpinner />
        </div>
        
        <div v-else-if="placements.length === 0" class="empty-state">
            <i class="pi pi-users text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-500">No active placements for this facility.</p>
        </div>
        
        <DataTable v-else :value="placements" stripedRows responsiveLayout="scroll">
            <Column field="candidate_name" header="Candidate" sortable />
            <Column field="job_title" header="Position" sortable />
            <Column field="start_date" header="Start Date" sortable>
                <template #body="{ data }">
                    {{ formatDate(data.start_date) }}
                </template>
            </Column>
            <Column field="status" header="Status" sortable>
                <template #body="{ data }">
                    <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
                </template>
            </Column>
            <Column header="Actions">
                <template #body="{ data }">
                    <Button icon="pi pi-eye" class="p-button-text p-button-sm"
                        @click="viewPlacement(data.id)" />
                </template>
            </Column>
        </DataTable>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useFacilityStore } from '../../stores/facility';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';

const props = defineProps({
    facility: {
        type: Object,
        default: () => ({}),
    },
});

const router = useRouter();
const facilityStore = useFacilityStore();

// Use assignments from store instead of separate API call
const placements = computed(() => facilityStore.assignments);
const loading = computed(() => facilityStore.loading);

function viewPlacement(placementId) {
    router.push({ name: 'dashboard.assignments.detail', params: { id: placementId } });
}

function formatDate(date) {
    if (!date) return '';
    return new Date(date).toLocaleDateString();
}

function getStatusSeverity(status) {
    const map = {
        active: 'success',
        completed: 'info',
        terminated: 'danger',
        pending: 'warning',
    };
    return map[status] || 'secondary';
}
</script>

<style scoped>
.placements-tab {
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
