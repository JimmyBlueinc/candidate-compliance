<template>
    <div class="billing-tab">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold">Billing Configuration</h3>
                <p class="text-sm text-gray-500">
                    <span v-if="billingSettings?.source === 'contract'" class="flex items-center gap-2">
                        <i class="pi pi-link text-blue-500"></i>
                        Linked to contract: {{ billingSettings?.contract?.file_name || 'Unknown' }}
                        <span class="text-xs text-gray-400">
                            (applied {{ formatDate(billingSettings?.applied_at) }})
                        </span>
                    </span>
                    <span v-else>Manually configured</span>
                </p>
            </div>
            <div class="flex gap-2">
                <Button v-if="approvedContracts.length > 0" 
                    label="Apply Contract" icon="pi pi-link"
                    class="p-button-outlined" @click="showContractDialog = true" />
                <Button label="Edit" icon="pi pi-pencil" 
                    :class="editing ? 'p-button-success' : 'p-button-outlined'"
                    @click="toggleEdit" />
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-8">
            <ProgressSpinner />
        </div>

        <!-- Billing Form -->
        <div v-else class="billing-form">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Payment Terms -->
                <div class="field">
                    <label class="block text-sm font-medium mb-2">Payment Terms (Days)</label>
                    <InputNumber v-model="form.payment_terms_days" :disabled="!editing"
                        :min="0" :max="365" class="w-full" />
                </div>

                <!-- Invoice Frequency -->
                <div class="field">
                    <label class="block text-sm font-medium mb-2">Invoice Frequency</label>
                    <Dropdown v-model="form.invoice_frequency" :disabled="!editing"
                        :options="frequencyOptions" optionLabel="label" optionValue="value"
                        class="w-full" />
                </div>

                <!-- Currency -->
                <div class="field">
                    <label class="block text-sm font-medium mb-2">Currency</label>
                    <Dropdown v-model="form.currency" :disabled="!editing"
                        :options="currencyOptions" optionLabel="label" optionValue="value"
                        class="w-full" />
                </div>

                <!-- Default Bill Rate -->
                <div class="field">
                    <label class="block text-sm font-medium mb-2">Default Bill Rate</label>
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500">$</span>
                        <InputNumber v-model="form.default_bill_rate" :disabled="!editing"
                            :min="0" :minFractionDigits="2" :maxFractionDigits="2"
                            class="w-full" />
                        <span class="text-gray-500">/hr</span>
                    </div>
                </div>

                <!-- Default Pay Rate -->
                <div class="field">
                    <label class="block text-sm font-medium mb-2">Default Pay Rate</label>
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500">$</span>
                        <InputNumber v-model="form.default_pay_rate" :disabled="!editing"
                            :min="0" :minFractionDigits="2" :maxFractionDigits="2"
                            class="w-full" />
                        <span class="text-gray-500">/hr</span>
                    </div>
                </div>

                <!-- Markup Percent -->
                <div class="field">
                    <label class="block text-sm font-medium mb-2">Markup %</label>
                    <div class="flex items-center gap-2">
                        <InputNumber v-model="form.default_markup_percent" :disabled="!editing"
                            :min="0" :max="100" :minFractionDigits="1" :maxFractionDigits="1"
                            class="w-full" />
                        <span class="text-gray-500">%</span>
                    </div>
                </div>

                <!-- Overtime Multiplier -->
                <div class="field">
                    <label class="block text-sm font-medium mb-2">Overtime Multiplier</label>
                    <InputNumber v-model="form.overtime_multiplier" :disabled="!editing"
                        :min="1" :minFractionDigits="1" :maxFractionDigits="2"
                        class="w-full" />
                </div>

                <!-- Holiday Multiplier -->
                <div class="field">
                    <label class="block text-sm font-medium mb-2">Holiday Multiplier</label>
                    <InputNumber v-model="form.holiday_multiplier" :disabled="!editing"
                        :min="1" :minFractionDigits="1" :maxFractionDigits="2"
                        class="w-full" />
                </div>

                <!-- Minimum Bill Hours -->
                <div class="field">
                    <label class="block text-sm font-medium mb-2">Minimum Bill Hours</label>
                    <InputNumber v-model="form.minimum_bill_hours" :disabled="!editing"
                        :min="0" :minFractionDigits="0" :maxFractionDigits="2"
                        class="w-full" />
                </div>

                <!-- Timesheet Required -->
                <div class="field">
                    <label class="block text-sm font-medium mb-2">Timesheet Required</label>
                    <InputSwitch v-model="form.timesheet_required" :disabled="!editing" />
                </div>

                <!-- Expense Allowed -->
                <div class="field">
                    <label class="block text-sm font-medium mb-2">Expense Allowed</label>
                    <InputSwitch v-model="form.expense_allowed" :disabled="!editing" />
                </div>
            </div>

            <!-- Timesheet Calculator Section -->
            <div class="mt-8 pt-6 border-t">
                <h4 class="text-md font-semibold mb-4">Timesheet Calculator</h4>
                <p class="text-sm text-gray-500 mb-4">
                    Preview billing calculations based on current settings.
                </p>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="field">
                        <label class="block text-sm font-medium mb-2">Regular Hours</label>
                        <InputNumber v-model="calculator.regular" :min="0" :minFractionDigits="0" :maxFractionDigits="2" class="w-full" />
                    </div>
                    <div class="field">
                        <label class="block text-sm font-medium mb-2">Overtime Hours</label>
                        <InputNumber v-model="calculator.overtime" :min="0" :minFractionDigits="0" :maxFractionDigits="2" class="w-full" />
                    </div>
                    <div class="field">
                        <label class="block text-sm font-medium mb-2">Holiday Hours</label>
                        <InputNumber v-model="calculator.holiday" :min="0" :minFractionDigits="0" :maxFractionDigits="2" class="w-full" />
                    </div>
                </div>
                
                <div v-if="calculatedResult" class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600">${{ calculatedResult.billTotal }}</div>
                            <div class="text-xs text-gray-500">Total Bill</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">${{ calculatedResult.payTotal }}</div>
                            <div class="text-xs text-gray-500">Total Pay</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600">${{ calculatedResult.margin }}</div>
                            <div class="text-xs text-gray-500">Gross Margin</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold" :class="calculatedResult.marginPercent >= 20 ? 'text-green-600' : 'text-orange-500'">
                                {{ calculatedResult.marginPercent }}%
                            </div>
                            <div class="text-xs text-gray-500">Margin %</div>
                        </div>
                    </div>
                    
                    <div class="mt-4 text-sm text-gray-600">
                        <div class="grid grid-cols-3 gap-2">
                            <div>Regular: {{ calculator.regular || 0 }}h @ ${{ form.default_bill_rate || 0 }}/hr = ${{ calculatedResult.regularBill }}</div>
                            <div>Overtime: {{ calculator.overtime || 0 }}h @ ${{ calculatedResult.overtimeRate }}/hr = ${{ calculatedResult.overtimeBill }}</div>
                            <div>Holiday: {{ calculator.holiday || 0 }}h @ ${{ calculatedResult.holidayRate }}/hr = ${{ calculatedResult.holidayBill }}</div>
                        </div>
                        <div v-if="calculatedResult.minimumAdjustment > 0" class="mt-2 text-orange-600">
                            <i class="pi pi-info-circle"></i>
                            Minimum bill hours ({{ form.minimum_bill_hours }}) applied: +${{ calculatedResult.minimumAdjustment }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div v-if="editing" class="mt-6 flex justify-end gap-2">
                <Button label="Cancel" class="p-button-text" @click="cancelEdit" />
                <Button label="Save Changes" :loading="saving" @click="saveBilling" />
            </div>
        </div>

        <!-- Contract Selection Dialog -->
        <Dialog v-model:visible="showContractDialog" header="Apply Contract to Billing" 
            :modal="true" :style="{ width: '600px' }">
            <div class="space-y-4">
                <p class="text-gray-600">
                    Select an approved contract to apply its terms to this facility's billing settings.
                </p>

                <div v-if="approvedContracts.length === 0" class="text-gray-500 py-4">
                    No approved contracts available. Please upload and approve a contract first.
                </div>

                <div v-else class="space-y-3">
                    <div v-for="contract in approvedContracts" :key="contract.id"
                        class="contract-option p-4 border rounded cursor-pointer hover:bg-gray-50"
                        :class="{ 'border-blue-500 bg-blue-50': selectedContractId === contract.id }"
                        @click="selectContract(contract.id)">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-medium">{{ contract.file_name }}</div>
                                <div class="text-sm text-gray-500">
                                    {{ contract.document_type.toUpperCase() }}
                                    <span v-if="contract.effective_start_date">
                                        • Effective: {{ formatDate(contract.effective_start_date) }}
                                    </span>
                                </div>
                            </div>
                            <Tag value="Approved" severity="success" />
                        </div>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" class="p-button-text" @click="showContractDialog = false" />
                <Button label="Preview" class="p-button-outlined" 
                    :disabled="!selectedContractId" @click="previewContract" />
                <Button label="Apply" :disabled="!selectedContractId" 
                    :loading="applying" @click="applyContract" />
            </template>
        </Dialog>

        <!-- Preview Dialog -->
        <Dialog v-model:visible="showPreviewDialog" header="Preview Contract Application" 
            :modal="true" :style="{ width: '700px' }">
            <div v-if="preview" class="space-y-4">
                <Message severity="info">
                    This will replace the current billing settings with the approved values from the contract.
                </Message>

                <div class="grid grid-cols-2 gap-4">
                    <div class="preview-column">
                        <h4 class="font-semibold mb-3 text-gray-500">Current Settings</h4>
                        <div class="space-y-2">
                            <div class="preview-row">
                                <span class="label">Payment Terms:</span>
                                <span>Net {{ preview.current?.payment_terms_days || '—' }}</span>
                            </div>
                            <div class="preview-row">
                                <span class="label">Frequency:</span>
                                <span>{{ preview.current?.invoice_frequency || '—' }}</span>
                            </div>
                            <div class="preview-row">
                                <span class="label">Bill Rate:</span>
                                <span>${{ preview.current?.default_bill_rate || '—' }}/hr</span>
                            </div>
                            <div class="preview-row">
                                <span class="label">Pay Rate:</span>
                                <span>${{ preview.current?.default_pay_rate || '—' }}/hr</span>
                            </div>
                        </div>
                    </div>
                    <div class="preview-column new">
                        <h4 class="font-semibold mb-3 text-blue-600">From Contract</h4>
                        <div class="space-y-2">
                            <div class="preview-row">
                                <span class="label">Payment Terms:</span>
                                <span>Net {{ preview.preview?.payment_terms_days || '—' }}</span>
                            </div>
                            <div class="preview-row">
                                <span class="label">Frequency:</span>
                                <span>{{ preview.preview?.invoice_frequency || '—' }}</span>
                            </div>
                            <div class="preview-row">
                                <span class="label">Bill Rate:</span>
                                <span>${{ preview.preview?.default_bill_rate || '—' }}/hr</span>
                            </div>
                            <div class="preview-row">
                                <span class="label">Pay Rate:</span>
                                <span>${{ preview.preview?.default_pay_rate || '—' }}/hr</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" class="p-button-text" @click="showPreviewDialog = false" />
                <Button label="Apply These Settings" :loading="applying" @click="confirmApply" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useFacilityStore } from '../../stores/facility';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import InputSwitch from 'primevue/inputswitch';
import ProgressSpinner from 'primevue/progressspinner';
import Tag from 'primevue/tag';
import Message from 'primevue/message';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    facility: {
        type: Object,
        default: () => ({}),
    },
    billingSettings: {
        type: Object,
        default: null,
    },
    contracts: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['refresh']);

const toast = useToast();
const facilityStore = useFacilityStore();

const loading = computed(() => facilityStore.loading);
const editing = ref(false);
const saving = ref(false);
const applying = ref(false);

const approvedContracts = computed(() => props.contracts.filter(c => c.status === 'approved'));

// Form state
const form = ref({
    payment_terms_days: 30,
    invoice_frequency: 'weekly',
    currency: 'USD',
    default_bill_rate: null,
    default_pay_rate: null,
    default_markup_percent: null,
    overtime_multiplier: 1.5,
    holiday_multiplier: 2.0,
    timesheet_required: true,
    expense_allowed: false,
    minimum_bill_hours: 0,
});

// Contract selection
const showContractDialog = ref(false);
const selectedContractId = ref(null);
const showPreviewDialog = ref(false);
const preview = ref(null);

// Timesheet calculator
const calculator = ref({
    regular: 40,
    overtime: 0,
    holiday: 0,
});

// Computed calculation result
const calculatedResult = computed(() => {
    const billRate = form.value.default_bill_rate || 0;
    const payRate = form.value.default_pay_rate || 0;
    const otMultiplier = form.value.overtime_multiplier || 1.5;
    const holidayMultiplier = form.value.holiday_multiplier || 2.0;
    const minHours = form.value.minimum_bill_hours || 0;
    
    const regular = calculator.value.regular || 0;
    const overtime = calculator.value.overtime || 0;
    const holiday = calculator.value.holiday || 0;
    
    const overtimeRate = billRate * otMultiplier;
    const holidayRate = billRate * holidayMultiplier;
    
    const regularBill = (regular * billRate).toFixed(2);
    const overtimeBill = (overtime * overtimeRate).toFixed(2);
    const holidayBill = (holiday * holidayRate).toFixed(2);
    
    const totalHours = regular + overtime + holiday;
    const subtotal = parseFloat(regularBill) + parseFloat(overtimeBill) + parseFloat(holidayBill);
    
    // Minimum hours adjustment
    const shortfall = Math.max(0, minHours - totalHours);
    const minimumAdjustment = (shortfall * billRate).toFixed(2);
    const billTotal = (subtotal + parseFloat(minimumAdjustment)).toFixed(2);
    
    // Pay calculation
    const payOvertimeRate = payRate * otMultiplier;
    const payHolidayRate = payRate * holidayMultiplier;
    const payTotal = (regular * payRate + overtime * payOvertimeRate + holiday * payHolidayRate).toFixed(2);
    
    const margin = (billTotal - payTotal).toFixed(2);
    const marginPercent = billTotal > 0 ? ((margin / billTotal) * 100).toFixed(1) : 0;
    
    return {
        regularBill,
        overtimeBill,
        holidayBill,
        overtimeRate: overtimeRate.toFixed(2),
        holidayRate: holidayRate.toFixed(2),
        billTotal,
        payTotal,
        margin,
        marginPercent,
        minimumAdjustment,
        totalHours,
    };
});

const frequencyOptions = [
    { label: 'Weekly', value: 'weekly' },
    { label: 'Biweekly', value: 'biweekly' },
    { label: 'Monthly', value: 'monthly' },
];

const currencyOptions = [
    { label: 'USD - US Dollar', value: 'USD' },
    { label: 'EUR - Euro', value: 'EUR' },
    { label: 'GBP - British Pound', value: 'GBP' },
    { label: 'CAD - Canadian Dollar', value: 'CAD' },
];

// Initialize form from billing settings
watch(() => props.billingSettings, (settings) => {
    if (settings) {
        form.value = {
            payment_terms_days: settings.payment_terms_days ?? 30,
            invoice_frequency: settings.invoice_frequency ?? 'weekly',
            currency: settings.currency ?? 'USD',
            default_bill_rate: settings.default_bill_rate,
            default_pay_rate: settings.default_pay_rate,
            default_markup_percent: settings.default_markup_percent,
            overtime_multiplier: settings.overtime_multiplier ?? 1.5,
            holiday_multiplier: settings.holiday_multiplier ?? 2.0,
            timesheet_required: settings.timesheet_required ?? true,
            expense_allowed: settings.expense_allowed ?? false,
            minimum_bill_hours: settings.minimum_bill_hours ?? 0,
        };
    }
}, { immediate: true });

function toggleEdit() {
    if (editing.value) {
        saveBilling();
    } else {
        editing.value = true;
    }
}

function cancelEdit() {
    editing.value = false;
    // Reset form to original values
    if (props.billingSettings) {
        form.value = { ...props.billingSettings };
    }
}

async function saveBilling() {
    saving.value = true;
    try {
        await facilityStore.updateBilling(props.facility.id, form.value);
        toast.add({ severity: 'success', summary: 'Success', detail: 'Billing settings updated' });
        editing.value = false;
        emit('refresh');
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.message || 'Failed to update billing' });
    } finally {
        saving.value = false;
    }
}

function selectContract(contractId) {
    selectedContractId.value = contractId;
}

async function previewContract() {
    if (!selectedContractId.value) return;

    try {
        preview.value = await facilityStore.previewContractBilling(
            props.facility.id,
            selectedContractId.value
        );
        showPreviewDialog.value = true;
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.message || 'Failed to preview contract' });
    }
}

async function applyContract() {
    if (!selectedContractId.value) return;

    // Show preview first
    await previewContract();
}

async function confirmApply() {
    applying.value = true;
    try {
        await facilityStore.applyContractToBilling(props.facility.id, selectedContractId.value);
        toast.add({ severity: 'success', summary: 'Success', detail: 'Contract applied to billing' });
        showPreviewDialog.value = false;
        showContractDialog.value = false;
        selectedContractId.value = null;
        emit('refresh');
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.message || 'Failed to apply contract' });
    } finally {
        applying.value = false;
    }
}

function formatDate(date) {
    if (!date) return '';
    return new Date(date).toLocaleDateString();
}
</script>

<style scoped>
.billing-tab {
    padding: 0.5rem;
}

.field {
    margin-bottom: 1rem;
}

.preview-column {
    padding: 1rem;
    background: var(--surface-ground, #f9fafb);
    border-radius: 0.5rem;
}

.preview-column.new {
    background: #eff6ff;
}

.preview-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--surface-border, #e5e7eb);
}

.preview-row:last-child {
    border-bottom: none;
}

.preview-row .label {
    color: var(--text-color-secondary, #6b7280);
}

.contract-option {
    transition: all 0.2s;
}
</style>
