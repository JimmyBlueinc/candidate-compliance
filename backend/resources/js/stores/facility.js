import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { apiGet, apiPost, apiPut, apiDelete } from '../lib/api';

export const useFacilityStore = defineStore('facility', () => {
    // State
    const currentFacility = ref(null);
    const contracts = ref([]);
    const contractTerms = ref(null);
    const billingSettings = ref(null);
    const assignments = ref([]);
    const loading = ref(false);
    const error = ref(null);

    // Computed
    const facilityId = computed(() => currentFacility.value?.id ?? null);
    const facilityName = computed(() => currentFacility.value?.name ?? '');
    const hasContracts = computed(() => contracts.value.length > 0);
    const approvedContracts = computed(() => 
        contracts.value.filter(c => c.status === 'approved')
    );
    const pendingReviewContracts = computed(() => 
        contracts.value.filter(c => ['processed', 'reviewed'].includes(c.status))
    );

    // Actions

    /**
     * Fetch facility detail with all related data
     */
    async function fetchFacility(id) {
        loading.value = true;
        error.value = null;

        try {
            const response = await apiGet(`/v1/facilities/${id}`);
            currentFacility.value = response.data;
            contracts.value = response.data.contracts || [];
            billingSettings.value = response.data.billing_settings || null;
            assignments.value = response.data.assignments || [];
            
            console.log('[FACILITY STORE] Loaded facility:', id);
            return response.data;
        } catch (e) {
            error.value = e.message || 'Failed to load facility';
            console.error('[FACILITY STORE] Error:', e);
            throw e;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Fetch contracts for a facility
     */
    async function fetchContracts(facilityId) {
        loading.value = true;
        error.value = null;

        try {
            const response = await apiGet(`/v1/facilities/${facilityId}/contracts`);
            contracts.value = response.data.contracts || [];
            return contracts.value;
        } catch (e) {
            error.value = e.message || 'Failed to load contracts';
            console.error('[FACILITY STORE] Error fetching contracts:', e);
            throw e;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Upload a new contract
     */
    async function uploadContract(facilityId, formData) {
        loading.value = true;
        error.value = null;

        try {
            const response = await apiPost(`/v1/facilities/${facilityId}/contracts`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            
            const newContract = response.data.contract;
            contracts.value.unshift(newContract);
            
            console.log('[FACILITY STORE] Contract uploaded:', newContract.id);
            return newContract;
        } catch (e) {
            error.value = e.message || 'Failed to upload contract';
            console.error('[FACILITY STORE] Error uploading contract:', e);
            throw e;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Extract terms from a contract
     */
    async function extractContract(facilityId, contractId) {
        loading.value = true;
        error.value = null;

        try {
            const response = await apiPost(`/v1/facilities/${facilityId}/contracts/${contractId}/extract`);
            
            // Update contract in list
            const idx = contracts.value.findIndex(c => c.id === contractId);
            if (idx !== -1) {
                contracts.value[idx] = response.data.contract;
            }
            
            console.log('[FACILITY STORE] Contract extracted:', contractId);
            return response.data;
        } catch (e) {
            error.value = e.message || 'Failed to extract contract';
            console.error('[FACILITY STORE] Error extracting contract:', e);
            throw e;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Get extracted terms for review
     */
    async function getExtractedTerms(facilityId, contractId) {
        loading.value = true;
        error.value = null;

        try {
            const response = await apiGet(`/v1/facilities/${facilityId}/contracts/${contractId}/extracted-terms`);
            contractTerms.value = response.data.terms;
            return response.data;
        } catch (e) {
            error.value = e.message || 'Failed to get extracted terms';
            console.error('[FACILITY STORE] Error getting terms:', e);
            throw e;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Review and approve/reject contract terms
     */
    async function reviewContract(facilityId, contractId, reviewData) {
        loading.value = true;
        error.value = null;

        try {
            const response = await apiPost(
                `/v1/facilities/${facilityId}/contracts/${contractId}/review`,
                reviewData
            );
            
            // Update contract in list
            const idx = contracts.value.findIndex(c => c.id === contractId);
            if (idx !== -1) {
                contracts.value[idx] = response.data.contract;
            }
            
            console.log('[FACILITY STORE] Contract reviewed:', contractId);
            return response.data;
        } catch (e) {
            error.value = e.message || 'Failed to review contract';
            console.error('[FACILITY STORE] Error reviewing contract:', e);
            throw e;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Delete a contract
     */
    async function deleteContract(facilityId, contractId) {
        loading.value = true;
        error.value = null;

        try {
            await apiDelete(`/v1/facilities/${facilityId}/contracts/${contractId}`);
            
            // Remove from list
            contracts.value = contracts.value.filter(c => c.id !== contractId);
            
            console.log('[FACILITY STORE] Contract deleted:', contractId);
        } catch (e) {
            error.value = e.message || 'Failed to delete contract';
            console.error('[FACILITY STORE] Error deleting contract:', e);
            throw e;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Fetch billing settings
     */
    async function fetchBilling(facilityId) {
        loading.value = true;
        error.value = null;

        try {
            const response = await apiGet(`/v1/facilities/${facilityId}/billing`);
            billingSettings.value = response.data.billing_settings;
            return billingSettings.value;
        } catch (e) {
            error.value = e.message || 'Failed to load billing settings';
            console.error('[FACILITY STORE] Error fetching billing:', e);
            throw e;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Update billing settings manually
     */
    async function updateBilling(facilityId, data) {
        loading.value = true;
        error.value = null;

        try {
            const response = await apiPut(`/v1/facilities/${facilityId}/billing`, data);
            billingSettings.value = response.data.billing_settings;
            
            console.log('[FACILITY STORE] Billing settings updated');
            return billingSettings.value;
        } catch (e) {
            error.value = e.message || 'Failed to update billing settings';
            console.error('[FACILITY STORE] Error updating billing:', e);
            throw e;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Apply contract terms to billing
     */
    async function applyContractToBilling(facilityId, contractId) {
        loading.value = true;
        error.value = null;

        try {
            const response = await apiPost(
                `/v1/facilities/${facilityId}/billing/apply-contract/${contractId}`
            );
            billingSettings.value = response.data.billing_settings;
            
            console.log('[FACILITY STORE] Contract applied to billing:', contractId);
            return billingSettings.value;
        } catch (e) {
            error.value = e.message || 'Failed to apply contract to billing';
            console.error('[FACILITY STORE] Error applying contract:', e);
            throw e;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Preview contract application to billing
     */
    async function previewContractBilling(facilityId, contractId) {
        loading.value = true;
        error.value = null;

        try {
            const response = await apiGet(
                `/v1/facilities/${facilityId}/billing/preview-contract/${contractId}`
            );
            return response.data;
        } catch (e) {
            error.value = e.message || 'Failed to preview contract';
            console.error('[FACILITY STORE] Error previewing contract:', e);
            throw e;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Clear facility data
     */
    function clearFacility() {
        currentFacility.value = null;
        contracts.value = [];
        contractTerms.value = null;
        billingSettings.value = null;
        assignments.value = [];
        error.value = null;
        loading.value = false;
    }

    return {
        // State
        currentFacility,
        contracts,
        contractTerms,
        billingSettings,
        assignments,
        loading,
        error,

        // Computed
        facilityId,
        facilityName,
        hasContracts,
        approvedContracts,
        pendingReviewContracts,

        // Actions
        fetchFacility,
        fetchContracts,
        uploadContract,
        extractContract,
        getExtractedTerms,
        reviewContract,
        deleteContract,
        fetchBilling,
        updateBilling,
        applyContractToBilling,
        previewContractBilling,
        clearFacility,
    };
});
