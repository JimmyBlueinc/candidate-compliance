<template>
    <div class="overview-tab">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Facility Info Card -->
            <Card class="info-card">
                <template #title>Facility Information</template>
                <template #content>
                    <div class="space-y-3">
                        <div class="info-row">
                            <span class="label">Type:</span>
                            <span class="value">{{ facility?.facility_type || 'Not specified' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Timezone:</span>
                            <span class="value">{{ facility?.timezone || 'Not specified' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Address:</span>
                            <span class="value">{{ fullAddress }}</span>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Stats Card -->
            <Card class="stats-card">
                <template #title>Quick Stats</template>
                <template #content>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="stat-item">
                            <div class="stat-value">{{ facility?.metadata?.active_jobs_count || 0 }}</div>
                            <div class="stat-label">Active Jobs</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">{{ facility?.metadata?.active_assignments_count || 0 }}</div>
                            <div class="stat-label">Active Placements</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">{{ facility?.metadata?.contracts_count || 0 }}</div>
                            <div class="stat-label">Contracts</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">{{ billingSource }}</div>
                            <div class="stat-label">Billing Source</div>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Billing Summary Card -->
            <Card class="billing-summary-card">
                <template #title>Billing Summary</template>
                <template #content>
                    <div v-if="billingSettings" class="space-y-3">
                        <div class="info-row">
                            <span class="label">Payment Terms:</span>
                            <span class="value">Net {{ billingSettings.payment_terms_days }}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Invoice Frequency:</span>
                            <span class="value capitalize">{{ billingSettings.invoice_frequency }}</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Currency:</span>
                            <span class="value">{{ billingSettings.currency }}</span>
                        </div>
                        <div class="info-row" v-if="billingSettings.default_bill_rate">
                            <span class="label">Default Bill Rate:</span>
                            <span class="value">${{ billingSettings.default_bill_rate }}/hr</span>
                        </div>
                    </div>
                    <div v-else class="text-gray-500">
                        No billing settings configured.
                    </div>
                </template>
            </Card>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import Card from 'primevue/card';

const props = defineProps({
    facility: {
        type: Object,
        default: () => ({}),
    },
    billingSettings: {
        type: Object,
        default: null,
    },
});

const fullAddress = computed(() => {
    const parts = [
        props.facility?.address,
        props.facility?.city,
        props.facility?.state,
        props.facility?.postal_code,
        props.facility?.country,
    ].filter(Boolean);
    return parts.length > 0 ? parts.join(', ') : 'Not specified';
});

const billingSource = computed(() => {
    if (!props.billingSettings) return 'None';
    return props.billingSettings.source === 'contract' ? 'Contract' : 'Manual';
});
</script>

<style scoped>
.overview-tab {
    padding: 0.5rem;
}

.info-card, .stats-card, .billing-summary-card {
    height: 100%;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--surface-border, #e5e7eb);
}

.info-row:last-child {
    border-bottom: none;
}

.label {
    color: var(--text-color-secondary, #6b7280);
    font-size: 0.875rem;
}

.value {
    font-weight: 500;
    color: var(--text-color, #111827);
}

.stat-item {
    text-align: center;
    padding: 1rem;
    background: var(--surface-ground, #f9fafb);
    border-radius: 0.5rem;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-color, #3b82f6);
}

.stat-label {
    font-size: 0.75rem;
    color: var(--text-color-secondary, #6b7280);
    margin-top: 0.25rem;
}
</style>
