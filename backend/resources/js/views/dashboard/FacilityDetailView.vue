<template>
    <div class="facility-page">
        <div class="bg-orb bg-orb-cyan" />
        <div class="bg-orb bg-orb-violet" />

        <div class="facility-shell">
            <!-- Top Hero -->
            <section class="facility-hero">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="eyebrow">Facility Command Center</div>
                        <h1 class="facility-title">
                            {{ facility?.name || 'Loading Facility...' }}
                        </h1>
                        <p class="facility-subtitle">
                            {{ facility?.facility_type || 'Facility' }}
                            <span v-if="facility?.city"> • {{ facility.city }}</span>
                            <span v-if="facility?.state">, {{ facility.state }}</span>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <Button
                            label="Back to Facilities"
                            icon="pi pi-arrow-left"
                            severity="secondary"
                            outlined
                            @click="goBack"
                        />
                        <Button
                            label="Open Contracts"
                            icon="pi pi-file"
                            @click="setActiveTabByName('contracts')"
                        />
                        <Button
                            label="Billing Control"
                            icon="pi pi-wallet"
                            severity="help"
                            @click="setActiveTabByName('billing')"
                        />
                    </div>
                </div>
            </section>

            <!-- Loading State -->
            <div v-if="loading && !facility" class="facility-loading">
                <ProgressSpinner />
            </div>

            <!-- Error State -->
            <Message v-else-if="error" severity="error" :closable="false">
                {{ error }}
            </Message>

            <!-- Content -->
            <div v-else-if="facility" class="space-y-5">
                <section class="kpi-grid">
                    <article class="kpi-card">
                        <div class="kpi-label">Total Contracts</div>
                        <div class="kpi-value">{{ totalContracts }}</div>
                        <div class="kpi-meta">MSA, SOW, amendments</div>
                    </article>

                    <article class="kpi-card">
                        <div class="kpi-label">Approved Contracts</div>
                        <div class="kpi-value">{{ approvedCount }}</div>
                        <div class="kpi-meta">Ready for billing rules</div>
                    </article>

                    <article class="kpi-card">
                        <div class="kpi-label">Pending Review</div>
                        <div class="kpi-value">{{ pendingCount }}</div>
                        <div class="kpi-meta">Needs extraction/approval</div>
                    </article>

                    <article class="kpi-card">
                        <div class="kpi-label">Billing Status</div>
                        <div class="kpi-value text-2xl">{{ billingStatusLabel }}</div>
                        <div class="kpi-meta">Contract-driven billing state</div>
                    </article>
                </section>

                <section class="facility-panel">
                    <TabView v-model:activeIndex="activeTab" class="facility-tabs">
                        <TabPanel header="Overview">
                            <OverviewTab :facility="facility" />
                        </TabPanel>

                        <TabPanel header="Contacts">
                            <ContactsTab :facility="facility" />
                        </TabPanel>

                        <TabPanel header="Jobs">
                            <JobsTab :facility="facility" />
                        </TabPanel>

                        <TabPanel header="Placements">
                            <PlacementsTab :facility="facility" />
                        </TabPanel>

                        <TabPanel header="Contracts">
                            <ContractsTab
                                :facility="facility"
                                :contracts="contracts"
                                @refresh="refreshContracts"
                            />
                        </TabPanel>

                        <TabPanel header="Billing">
                            <BillingTab
                                :facility="facility"
                                :billing-settings="billingSettings"
                                :contracts="approvedContracts"
                                @refresh="refreshBilling"
                            />
                        </TabPanel>

                        <TabPanel header="Activity">
                            <ActivityTab :facility="facility" />
                        </TabPanel>
                    </TabView>
                </section>
            </div>
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
const tabOrder = ['overview', 'contacts', 'jobs', 'placements', 'contracts', 'billing', 'activity'];

// Map store state
const facility = computed(() => facilityStore.currentFacility);
const contracts = computed(() => facilityStore.contracts);
const billingSettings = computed(() => facilityStore.billingSettings);
const approvedContracts = computed(() => facilityStore.approvedContracts);
const loading = computed(() => facilityStore.loading);
const error = computed(() => facilityStore.error);
const totalContracts = computed(() => contracts.value?.length || 0);
const approvedCount = computed(() => approvedContracts.value?.length || 0);
const pendingCount = computed(() => {
    const list = contracts.value || [];
    return list.filter((c) => ['uploaded', 'processing', 'processed', 'reviewed'].includes(c.status)).length;
});
const billingStatusLabel = computed(() => {
    if (!billingSettings.value) return 'Unconfigured';
    if (approvedCount.value > 0) return 'Optimized';
    return 'Manual';
});

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

watch(() => route.query.tab, (tabParam) => {
    if (!tabParam) return;
    setActiveTabByName(String(tabParam));
});

async function loadFacility(id) {
    try {
        await facilityStore.fetchFacility(id);
        const tabParam = route.query.tab;
        if (tabParam) setActiveTabByName(String(tabParam));
    } catch (e) {
        console.error('[FacilityDetailView] Failed to load facility:', e);
    }
}

function setActiveTabByName(tabName) {
    const tabIndex = tabOrder.indexOf(tabName);
    if (tabIndex !== -1) {
        activeTab.value = tabIndex;
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
.facility-page {
    position: relative;
    min-height: 100%;
    padding: 1rem;
    background:
        radial-gradient(1200px 600px at 0% 0%, rgba(34, 211, 238, 0.12), transparent 50%),
        radial-gradient(1000px 560px at 100% 0%, rgba(139, 92, 246, 0.12), transparent 50%),
        var(--aq-bg, #0b1020);
}

.facility-shell {
    position: relative;
    z-index: 2;
    max-width: 1400px;
    margin: 0 auto;
}

.facility-hero {
    position: relative;
    overflow: hidden;
    border: 1px solid color-mix(in srgb, var(--aq-border, #334155) 60%, transparent);
    border-radius: 1.25rem;
    background: linear-gradient(
        135deg,
        color-mix(in srgb, var(--aq-surface-2, #111827) 88%, #22d3ee 12%),
        color-mix(in srgb, var(--aq-surface-2, #111827) 88%, #8b5cf6 12%)
    );
    padding: 1.15rem 1.2rem;
    margin-bottom: 1rem;
    box-shadow:
        0 14px 35px rgba(2, 6, 23, 0.35),
        inset 0 1px 0 rgba(255, 255, 255, 0.08);
}

.facility-hero::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(120deg, transparent 20%, rgba(255, 255, 255, 0.08) 50%, transparent 80%);
    transform: translateX(-100%);
    animation: scanline 7s ease-in-out infinite;
    pointer-events: none;
}

.eyebrow {
    font-size: 0.72rem;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    font-weight: 800;
    color: color-mix(in srgb, var(--aq-fg, #e2e8f0) 78%, #22d3ee 22%);
}

.facility-title {
    margin-top: 0.2rem;
    font-size: clamp(1.35rem, 2.5vw, 2rem);
    font-weight: 800;
    line-height: 1.1;
    color: var(--aq-fg, #f8fafc);
}

.facility-subtitle {
    margin-top: 0.2rem;
    color: var(--aq-muted, #94a3b8);
    font-size: 0.9rem;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.85rem;
}

.kpi-card {
    position: relative;
    overflow: hidden;
    border: 1px solid color-mix(in srgb, var(--aq-border, #334155) 75%, transparent);
    border-radius: 1rem;
    background: color-mix(in srgb, var(--aq-surface-2, #111827) 88%, transparent);
    backdrop-filter: blur(10px);
    padding: 0.9rem 1rem;
    transition: transform 180ms ease, border-color 180ms ease;
}

.kpi-card:hover {
    transform: translateY(-2px);
    border-color: color-mix(in srgb, var(--aq-primary, #8b5cf6) 38%, var(--aq-border, #334155));
}

.kpi-label {
    color: var(--aq-muted, #94a3b8);
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.kpi-value {
    margin-top: 0.3rem;
    color: var(--aq-fg, #f8fafc);
    font-size: 1.9rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.kpi-meta {
    margin-top: 0.18rem;
    color: color-mix(in srgb, var(--aq-muted, #94a3b8) 90%, white 10%);
    font-size: 0.78rem;
}

.facility-panel {
    border: 1px solid color-mix(in srgb, var(--aq-border, #334155) 75%, transparent);
    border-radius: 1rem;
    background: color-mix(in srgb, var(--aq-surface-2, #111827) 92%, transparent);
    padding: 0.65rem;
}

.facility-loading {
    min-height: 220px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.facility-tabs :deep(.p-tabview-nav) {
    border-bottom: 1px solid color-mix(in srgb, var(--aq-border, #334155) 80%, transparent);
    background: transparent;
}

.facility-tabs :deep(.p-tabview-nav li .p-tabview-nav-link) {
    border: 0;
    border-radius: 999px;
    margin: 0.2rem 0.25rem 0.6rem 0;
    background: color-mix(in srgb, var(--aq-surface-1, #1f2937) 80%, transparent);
    color: var(--aq-muted, #94a3b8);
    font-weight: 700;
    padding: 0.55rem 0.95rem;
}

.facility-tabs :deep(.p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    background: color-mix(in srgb, var(--aq-primary, #8b5cf6) 18%, var(--aq-surface-1, #1f2937));
    color: color-mix(in srgb, var(--aq-fg, #f8fafc) 92%, var(--aq-primary, #8b5cf6));
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--aq-primary, #8b5cf6) 35%, transparent);
}

.facility-tabs :deep(.p-tabview-panels) {
    border-radius: 0.8rem;
    padding: 0.85rem;
    background: color-mix(in srgb, var(--aq-surface-1, #111827) 90%, transparent);
}

.bg-orb {
    position: absolute;
    width: 22rem;
    height: 22rem;
    border-radius: 50%;
    filter: blur(60px);
    opacity: 0.24;
    z-index: 1;
    pointer-events: none;
    animation: drift 18s ease-in-out infinite;
}

.bg-orb-cyan {
    top: -7rem;
    left: -7rem;
    background: #22d3ee;
}

.bg-orb-violet {
    top: -8rem;
    right: -6rem;
    background: #8b5cf6;
    animation-delay: 2.2s;
}

@keyframes drift {
    0%,
    100% {
        transform: translate(0, 0) scale(1);
    }
    50% {
        transform: translate(0.5rem, -0.7rem) scale(1.04);
    }
}

@keyframes scanline {
    0%,
    100% {
        transform: translateX(-100%);
        opacity: 0;
    }
    15% {
        opacity: 1;
    }
    50% {
        transform: translateX(100%);
        opacity: 0.55;
    }
    65% {
        opacity: 0;
    }
}
</style>
