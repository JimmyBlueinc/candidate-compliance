<template>
    <div class="contracts-tab">
        <!-- Upload Section -->
        <div class="upload-section mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Contracts</h3>
                <Button label="Upload Contract" icon="pi pi-upload" @click="showUploadDialog = true" />
            </div>
        </div>

        <!-- Contracts List -->
        <div v-if="loading" class="flex justify-center py-8">
            <ProgressSpinner />
        </div>

        <div v-else-if="contracts.length === 0" class="empty-state">
            <i class="pi pi-file text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-500">No contracts uploaded yet.</p>
            <p class="text-sm text-gray-400 mt-2">Upload an MSA, SOW, or rate card to get started.</p>
        </div>

        <DataTable v-else :value="contracts" stripedRows responsiveLayout="scroll" @row-click="onRowClick">
            <Column field="document_type" header="Type" sortable>
                <template #body="{ data }">
                    <Tag :value="data.document_type.toUpperCase()" 
                        :severity="getTypeSeverity(data.document_type)" />
                </template>
            </Column>
            <Column field="file_name" header="File Name" sortable>
                <template #body="{ data }">
                    <span class="truncate max-w-xs block">{{ data.file_name }}</span>
                </template>
            </Column>
            <Column field="status" header="Status" sortable>
                <template #body="{ data }">
                    <Tag :value="formatStatus(data.status)" 
                        :severity="getStatusSeverity(data.status)" />
                </template>
            </Column>
            <Column field="effective_start_date" header="Effective" sortable>
                <template #body="{ data }">
                    <span v-if="data.effective_start_date">
                        {{ formatDate(data.effective_start_date) }}
                        <span v-if="data.effective_end_date">
                            - {{ formatDate(data.effective_end_date) }}
                        </span>
                    </span>
                    <span v-else class="text-gray-400">Not specified</span>
                </template>
            </Column>
            <Column field="created_at" header="Uploaded" sortable>
                <template #body="{ data }">
                    {{ formatDate(data.created_at) }}
                </template>
            </Column>
            <Column header="Actions">
                <template #body="{ data }">
                    <div class="flex gap-2">
                        <Button v-if="canExtract(data)" icon="pi pi-cog" 
                            class="p-button-text p-button-sm" 
                            @click.stop="extractContract(data)"
                            v-tooltip="'Extract Terms'" />
                        <Button v-if="canReview(data)" icon="pi pi-eye" 
                            class="p-button-text p-button-sm"
                            @click.stop="viewTerms(data)"
                            v-tooltip="'Review Terms'" />
                        <Button icon="pi pi-trash" class="p-button-text p-button-danger p-button-sm"
                            @click.stop="confirmDelete(data)"
                            v-tooltip="'Delete'" />
                    </div>
                </template>
            </Column>
        </DataTable>

        <!-- Upload Dialog -->
        <Dialog v-model:visible="showUploadDialog" header="Upload Contract" 
            :modal="true" :style="{ width: '500px' }">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Document Type</label>
                    <Dropdown v-model="uploadForm.document_type" :options="documentTypes" 
                        optionLabel="label" optionValue="value" placeholder="Select type" 
                        class="w-full" />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">File (PDF, DOC, DOCX)</label>
                    <FileUpload mode="basic" accept=".pdf,.doc,.docx" 
                        :maxFileSize="10000000" chooseLabel="Select File"
                        @select="onFileSelect" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Effective Start</label>
                        <Calendar v-model="uploadForm.effective_start_date" 
                            placeholder="Select date" class="w-full" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Effective End</label>
                        <Calendar v-model="uploadForm.effective_end_date" 
                            placeholder="Select date" class="w-full" />
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" class="p-button-text" @click="showUploadDialog = false" />
                <Button label="Upload" :loading="uploading" @click="uploadContract" />
            </template>
        </Dialog>

        <!-- Terms Review Dialog -->
        <Dialog v-model:visible="showTermsDialog" header="Review Extracted Terms" 
            :modal="true" :style="{ width: '800px' }">
            <div v-if="selectedContract" class="space-y-6">
                <div class="flex items-center justify-between bg-gray-50 p-4 rounded">
                    <div>
                        <div class="font-semibold">{{ selectedContract.file_name }}</div>
                        <div class="text-sm text-gray-500">
                            Status: {{ formatStatus(selectedContract.status) }}
                        </div>
                    </div>
                    <Tag :value="selectedContract.document_type.toUpperCase()" />
                </div>

                <div v-if="selectedContract.terms">
                    <h4 class="font-semibold mb-4">Extracted Terms</h4>
                    <div class="terms-grid">
                        <div v-for="field in termFields" :key="field.key" class="term-item">
                            <div class="term-header">
                                <span class="term-label">{{ field.label }}</span>
                                <span v-if="getConfidence(field.key)" 
                                    class="confidence-badge"
                                    :class="getConfidenceClass(field.key)">
                                    {{ (getConfidence(field.key) * 100).toFixed(0) }}% confidence
                                </span>
                            </div>
                            <div class="term-value">
                                <InputText v-if="editing" v-model="editedTerms[field.key]" 
                                    class="w-full" />
                                <span v-else>{{ getTermValue(field.key) || 'Not extracted' }}</span>
                            </div>
                            <div v-if="getSourceSpan(field.key)" class="source-span">
                                <small class="text-gray-500">
                                    Source: "{{ getSourceSpan(field.key)?.text }}"
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="selectedContract.rate_lines?.length">
                    <h4 class="font-semibold mb-4">Rate Lines</h4>
                    <DataTable :value="selectedContract.rate_lines" size="small">
                        <Column field="role_title" header="Role" />
                        <Column field="bill_rate" header="Bill Rate">
                            <template #body="{ data }">
                                ${{ data.bill_rate || '—' }}
                            </template>
                        </Column>
                        <Column field="pay_rate" header="Pay Rate">
                            <template #body="{ data }">
                                ${{ data.pay_rate || '—' }}
                            </template>
                        </Column>
                        <Column field="confidence_score" header="Confidence">
                            <template #body="{ data }">
                                <span v-if="data.confidence_score">
                                    {{ (data.confidence_score * 100).toFixed(0) }}%
                                </span>
                                <span v-else>—</span>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" class="p-button-text" @click="showTermsDialog = false" />
                <Button v-if="canApprove" label="Approve" icon="pi pi-check" 
                    class="p-button-success" @click="approveTerms" />
                <Button v-if="editing" label="Save Changes" @click="saveEditedTerms" />
                <Button v-else label="Edit" icon="pi pi-pencil" @click="startEditing" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useFacilityStore } from '../../stores/facility';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import FileUpload from 'primevue/fileupload';
import Calendar from 'primevue/calendar';
import InputText from 'primevue/inputtext';
import ProgressSpinner from 'primevue/progressspinner';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    facility: {
        type: Object,
        default: () => ({}),
    },
    contracts: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['refresh']);

const route = useRoute();
const toast = useToast();
const facilityStore = useFacilityStore();

const loading = computed(() => facilityStore.loading);

// Upload dialog
const showUploadDialog = ref(false);
const uploading = ref(false);
const uploadForm = ref({
    document_type: 'msa',
    effective_start_date: null,
    effective_end_date: null,
    file: null,
});

const documentTypes = [
    { label: 'Master Service Agreement (MSA)', value: 'msa' },
    { label: 'Statement of Work (SOW)', value: 'sow' },
    { label: 'Amendment', value: 'amendment' },
    { label: 'Rate Card', value: 'rate_card' },
];

// Terms review dialog
const showTermsDialog = ref(false);
const selectedContract = ref(null);
const editing = ref(false);
const editedTerms = ref({});

const termFields = [
    { key: 'payment_terms_days', label: 'Payment Terms (Days)' },
    { key: 'invoice_frequency', label: 'Invoice Frequency' },
    { key: 'currency', label: 'Currency' },
    { key: 'bill_rate_amount', label: 'Bill Rate' },
    { key: 'pay_rate_amount', label: 'Pay Rate' },
    { key: 'markup_percent', label: 'Markup %' },
    { key: 'overtime_multiplier', label: 'Overtime Multiplier' },
    { key: 'holiday_multiplier', label: 'Holiday Multiplier' },
    { key: 'timesheet_required', label: 'Timesheet Required' },
    { key: 'expense_allowed', label: 'Expense Allowed' },
];

// Actions
function onFileSelect(event) {
    uploadForm.value.file = event.files[0];
}

async function uploadContract() {
    if (!uploadForm.value.file) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Please select a file' });
        return;
    }

    uploading.value = true;

    const formData = new FormData();
    formData.append('file', uploadForm.value.file);
    formData.append('document_type', uploadForm.value.document_type);
    if (uploadForm.value.effective_start_date) {
        formData.append('effective_start_date', uploadForm.value.effective_start_date.toISOString().split('T')[0]);
    }
    if (uploadForm.value.effective_end_date) {
        formData.append('effective_end_date', uploadForm.value.effective_end_date.toISOString().split('T')[0]);
    }

    try {
        await facilityStore.uploadContract(props.facility.id, formData);
        toast.add({ severity: 'success', summary: 'Success', detail: 'Contract uploaded successfully' });
        showUploadDialog.value = false;
        resetUploadForm();
        emit('refresh');
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.message || 'Failed to upload contract' });
    } finally {
        uploading.value = false;
    }
}

function resetUploadForm() {
    uploadForm.value = {
        document_type: 'msa',
        effective_start_date: null,
        effective_end_date: null,
        file: null,
    };
}

async function extractContract(contract) {
    try {
        await facilityStore.extractContract(props.facility.id, contract.id);
        toast.add({ severity: 'success', summary: 'Success', detail: 'Contract extraction started' });
        emit('refresh');
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.message || 'Failed to extract contract' });
    }
}

function viewTerms(contract) {
    selectedContract.value = contract;
    editing.value = false;
    showTermsDialog.value = true;
}

function startEditing() {
    if (selectedContract.value?.terms) {
        editedTerms.value = {
            payment_terms_days: selectedContract.value.terms.approved_payment_terms_days || selectedContract.value.terms.payment_terms_days,
            invoice_frequency: selectedContract.value.terms.approved_invoice_frequency || selectedContract.value.terms.invoice_frequency,
            bill_rate_amount: selectedContract.value.terms.approved_bill_rate_amount || selectedContract.value.terms.bill_rate_amount,
            pay_rate_amount: selectedContract.value.terms.approved_pay_rate_amount || selectedContract.value.terms.pay_rate_amount,
            markup_percent: selectedContract.value.terms.approved_markup_percent || selectedContract.value.terms.markup_percent,
            overtime_multiplier: selectedContract.value.terms.approved_overtime_multiplier || selectedContract.value.terms.overtime_multiplier,
            holiday_multiplier: selectedContract.value.terms.approved_holiday_multiplier || selectedContract.value.terms.holiday_multiplier,
            timesheet_required: selectedContract.value.terms.approved_timesheet_required ?? selectedContract.value.terms.timesheet_required,
            expense_allowed: selectedContract.value.terms.approved_expense_allowed ?? selectedContract.value.terms.expense_allowed,
        };
        editing.value = true;
    }
}

async function saveEditedTerms() {
    try {
        await facilityStore.reviewContract(props.facility.id, selectedContract.value.id, {
            action: 'modify',
            terms: editedTerms.value,
        });
        toast.add({ severity: 'success', summary: 'Success', detail: 'Terms updated' });
        editing.value = false;
        showTermsDialog.value = false;
        emit('refresh');
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.message || 'Failed to save terms' });
    }
}

async function approveTerms() {
    try {
        await facilityStore.reviewContract(props.facility.id, selectedContract.value.id, {
            action: 'approve',
        });
        toast.add({ severity: 'success', summary: 'Success', detail: 'Contract terms approved' });
        showTermsDialog.value = false;
        emit('refresh');
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.message || 'Failed to approve terms' });
    }
}

async function confirmDelete(contract) {
    // TODO: Add confirmation dialog
    try {
        await facilityStore.deleteContract(props.facility.id, contract.id);
        toast.add({ severity: 'success', summary: 'Success', detail: 'Contract deleted' });
        emit('refresh');
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.message || 'Failed to delete contract' });
    }
}

function onRowClick(event) {
    viewTerms(event.data);
}

// Helpers
function canExtract(contract) {
    return contract.status === 'uploaded' || contract.status === 'processed';
}

function canReview(contract) {
    return ['processed', 'reviewed', 'approved'].includes(contract.status);
}

const canApprove = computed(() => {
    return selectedContract.value?.status === 'processed' || selectedContract.value?.status === 'reviewed';
});

function getTermValue(field) {
    const terms = selectedContract.value?.terms;
    if (!terms) return null;
    return terms[`approved_${field}`] ?? terms[field];
}

function getConfidence(field) {
    return selectedContract.value?.terms?.confidence?.[field];
}

function getSourceSpan(field) {
    return selectedContract.value?.terms?.source_spans?.[field];
}

function getConfidenceClass(field) {
    const conf = getConfidence(field);
    if (!conf) return '';
    if (conf >= 0.8) return 'high';
    if (conf >= 0.5) return 'medium';
    return 'low';
}

function formatDate(date) {
    if (!date) return '';
    return new Date(date).toLocaleDateString();
}

function formatStatus(status) {
    const map = {
        uploaded: 'Uploaded',
        processing: 'Processing',
        processed: 'Processed',
        reviewed: 'Reviewed',
        approved: 'Approved',
    };
    return map[status] || status;
}

function getStatusSeverity(status) {
    const map = {
        uploaded: 'secondary',
        processing: 'info',
        processed: 'warning',
        reviewed: 'info',
        approved: 'success',
    };
    return map[status] || 'secondary';
}

function getTypeSeverity(type) {
    const map = {
        msa: 'info',
        sow: 'success',
        amendment: 'warning',
        rate_card: 'secondary',
    };
    return map[type] || 'secondary';
}
</script>

<style scoped>
.contracts-tab {
    padding: 0.5rem;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
}

.terms-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.term-item {
    padding: 1rem;
    background: var(--surface-ground, #f9fafb);
    border-radius: 0.5rem;
}

.term-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.term-label {
    font-size: 0.875rem;
    color: var(--text-color-secondary, #6b7280);
}

.term-value {
    font-weight: 500;
}

.confidence-badge {
    font-size: 0.75rem;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
}

.confidence-badge.high {
    background: #dcfce7;
    color: #166534;
}

.confidence-badge.medium {
    background: #fef3c7;
    color: #92400e;
}

.confidence-badge.low {
    background: #fee2e2;
    color: #991b1b;
}

.source-span {
    margin-top: 0.5rem;
    padding: 0.5rem;
    background: var(--surface-a, #f3f4f6);
    border-radius: 0.25rem;
    font-style: italic;
}
</style>
