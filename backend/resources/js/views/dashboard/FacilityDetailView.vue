<template>
    <div class="facility-detail">
        <!-- Header -->
        <div class="facility-header mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ facility?.name || 'Loading...' }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ facility?.facility_type || 'Facility' }}
                        <span v-if="facility?.city"> • {{ facility.city }}</span>
                        <span v-if="facility?.state">, {{ facility.state }}</span>
                    </p>
                </div>
                <Button label="Back to Facilities" icon="pi pi-arrow-left" 
                    class="p-button-text" @click="goBack" />
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading && !facility" class="flex justify-center py-12">
            <ProgressSpinner />
        </div>

        <!-- Error State -->
        <Message v-else-if="error" severity="error" :closable="false">
            {{ error }}
        </Message>

        <!-- Content -->
        <div v-else-if="facility" class="facility-content">
            <!-- Tabs -->
            <TabView v-model:activeIndex="activeTab" class="facility-tabs">
                <!-- Overview Tab -->
                <TabPanel header="Overview">
                    <OverviewTab :facility="facility" />
                </TabPanel>

                <!-- Contacts Tab -->
                <TabPanel header="Contacts">
                    <ContactsTab :facility="facility" />
                </TabPanel>

                <!-- Jobs Tab -->
                <TabPanel header="Jobs">
                    <JobsTab :facility="facility" />
                </TabPanel>

                <!-- Placements Tab -->
                <TabPanel header="Placements">
                    <PlacementsTab :facility="facility" />
                </TabPanel>

                <!-- Contracts Tab -->
                <TabPanel header="Contracts">
                    <ContractsTab 
                        :facility="facility" 
                        :contracts="contracts"
                        @refresh="refreshContracts"
                    />
                </TabPanel>

                <!-- Billing Tab -->
                <TabPanel header="Billing">
                    <BillingTab 
                        :facility="facility" 
                        :billing-settings="billingSettings"
                        :contracts="approvedContracts"
                        @refresh="refreshBilling"
                    />
                </TabPanel>

                <!-- Activity Tab -->
                <TabPanel header="Activity">
                    <ActivityTab :facility="facility" />
                </TabPanel>
            </TabView>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useFacilityStore } from '../../stores/facility';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Button from 'primevue/button';
import ProgressSpinner from 'primevue/progressspinner';
import Message from 'primevue/message';

// Tab components (will be created next)
import OverviewTab from '../../components/facility/OverviewTab.vue';
import ContactsTab from '../../components/facility/ContactsTab.vue';
import JobsTab from '../../components/facility/JobsTab.vue';
import PlacementsTab from '../../components/facility/PlacementsTab.vue';
import ContractsTab from '../../components/facility/ContractsTab.vue';
import BillingTab from '../../components/facility/BillingTab.vue';
import ActivityTab from '../../components/facility/ActivityTab.vue';

const route = useRoute();
const router = useRouter();
const facilityStore = useFacilityStore();

const activeTab = ref(0);

// Map store state
const facility = computed(() => facilityStore.currentFacility);
const contracts = computed(() => facilityStore.contracts);
const billingSettings = computed(() => facilityStore.billingSettings);
const approvedContracts = computed(() => facilityStore.approvedContracts);
const loading = computed(() => facilityStore.loading);
const error = computed(() => facilityStore.error);

// Load facility on mount
onMounted(async () => {
    const facilityId = route.params.id;
    if (facilityId) {
        await loadFacility(facilityId);
    }
});

// Reload when route param changes
watch(() => route.params.id, async (newId) => {
    if (newId) {
        await loadFacility(newId);
    }
});

async function loadFacility(id) {
    try {
        await facilityStore.fetchFacility(id);
        
        // Set active tab from query param
        const tabParam = route.query.tab;
        if (tabParam) {
            const tabIndex = ['overview', 'contacts', 'jobs', 'placements', 'contracts', 'billing', 'activity']
                .indexOf(tabParam);
            if (tabIndex !== -1) {
                activeTab.value = tabIndex;
            }
        }
    } catch (e) {
        console.error('[FacilityDetailView] Failed to load facility:', e);
    }
}

async function refreshContracts() {
    if (facility.value?.id) {
        await facilityStore.fetchContracts(facility.value.id);
    }
}

async function refreshBilling() {
    if (facility.value?.id) {
        await facilityStore.fetchBilling(facility.value.id);
    }
}

function goBack() {
    router.push({ name: 'dashboard.facilities' });
}
</script>

<style scoped>
.facility-detail {
    padding: 1rem;
}

.facility-header {
    border-bottom: 1px solid var(--surface-border, #e5e7eb);
    padding-bottom: 1rem;
}

.facility-tabs :deep(.p-tabview-nav) {
    border-bottom: 2px solid var(--surface-border, #e5e7eb);
}

.facility-tabs :deep(.p-tabview-panels) {
    padding-top: 1.5rem;
}
</style>
