<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillingSettings;
use App\Models\Contract;
use App\Models\Facility;
use App\Services\ContractExtractionService;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    public function __construct(
        protected ContractExtractionService $extractionService
    ) {}

    /**
     * Get billing settings for a facility.
     */
    public function show(Request $request, int $facilityId): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $facility = Facility::findOrFail($facilityId);
        if ($facility->organization_id !== $orgId) {
            return response()->json(['message' => 'Facility not found.'], 404);
        }

        $settings = BillingSettings::getOrCreateForFacility($facilityId, $orgId);

        return response()->api([
            'billing_settings' => $this->formatBillingSettings($settings),
        ]);
    }

    /**
     * Update billing settings manually.
     */
    public function update(Request $request, int $facilityId): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $facility = Facility::findOrFail($facilityId);
        if ($facility->organization_id !== $orgId) {
            return response()->json(['message' => 'Facility not found.'], 404);
        }

        $validated = $request->validate([
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'invoice_frequency' => ['nullable', 'in:weekly,biweekly,monthly'],
            'currency' => ['nullable', 'string', 'size:3'],
            'default_bill_rate' => ['nullable', 'numeric', 'min:0'],
            'default_pay_rate' => ['nullable', 'numeric', 'min:0'],
            'default_markup_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'overtime_multiplier' => ['nullable', 'numeric', 'min:1'],
            'holiday_multiplier' => ['nullable', 'numeric', 'min:1'],
            'timesheet_required' => ['nullable', 'boolean'],
            'expense_allowed' => ['nullable', 'boolean'],
            'minimum_bill_hours' => ['nullable', 'numeric', 'min:0'],
        ]);

        $settings = BillingSettings::getOrCreateForFacility($facilityId, $orgId);

        // If updating manually, set source to manual
        $updateData = array_filter($validated, fn ($v) => $v !== null);
        $updateData['source'] = BillingSettings::SOURCE_MANUAL;
        $updateData['contract_id'] = null; // Clear contract reference
        $updateData['applied_at'] = null;

        $settings->update($updateData);

        Log::info('[BILLING] Settings updated manually', [
            'facility_id' => $facilityId,
            'settings_id' => $settings->id,
        ]);

        return response()->api([
            'billing_settings' => $this->formatBillingSettings($settings),
        ], 200, [], 'Billing settings updated.');
    }

    /**
     * Apply contract terms to billing settings.
     */
    public function applyContract(Request $request, int $facilityId, int $contractId): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $facility = Facility::findOrFail($facilityId);
        if ($facility->organization_id !== $orgId) {
            return response()->json(['message' => 'Facility not found.'], 404);
        }

        $contract = Contract::with(['terms'])
            ->where('facility_id', $facilityId)
            ->where('organization_id', $orgId)
            ->findOrFail($contractId);

        if (!$contract->isApproved()) {
            return response()->json([
                'message' => 'Contract must be approved before applying to billing.',
            ], 400);
        }

        if (!$contract->terms || !$contract->terms->isApproved()) {
            return response()->json([
                'message' => 'Contract terms must be reviewed and approved first.',
            ], 400);
        }

        $settings = BillingSettings::getOrCreateForFacility($facilityId, $orgId);

        // Map contract terms to billing settings
        $billingData = $this->extractionService->mapTermsToBilling($contract->terms);
        $billingData['source'] = BillingSettings::SOURCE_CONTRACT;
        $billingData['contract_id'] = $contract->id;
        $billingData['applied_at'] = now();

        $settings->update($billingData);

        Log::info('[BILLING] Contract applied to billing', [
            'facility_id' => $facilityId,
            'contract_id' => $contractId,
            'settings_id' => $settings->id,
        ]);

        return response()->api([
            'billing_settings' => $this->formatBillingSettings($settings),
        ], 200, [], 'Contract terms applied to billing settings.');
    }

    /**
     * Preview what applying a contract would look like.
     */
    public function previewContract(Request $request, int $facilityId, int $contractId): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $facility = Facility::findOrFail($facilityId);
        if ($facility->organization_id !== $orgId) {
            return response()->json(['message' => 'Facility not found.'], 404);
        }

        $contract = Contract::with(['terms'])
            ->where('facility_id', $facilityId)
            ->where('organization_id', $orgId)
            ->findOrFail($contractId);

        if (!$contract->terms) {
            return response()->json([
                'message' => 'Contract has no extracted terms.',
            ], 404);
        }

        $previewData = $this->extractionService->mapTermsToBilling($contract->terms);

        $currentSettings = BillingSettings::where('facility_id', $facilityId)->first();
        $currentData = $currentSettings ? $this->formatBillingSettings($currentSettings) : null;

        return response()->api([
            'preview' => $previewData,
            'current' => $currentData,
            'contract' => [
                'id' => $contract->id,
                'document_type' => $contract->document_type,
                'file_name' => $contract->file_name,
                'terms_approved' => $contract->terms->isApproved(),
            ],
        ]);
    }

    /**
     * Format billing settings for API response.
     */
    protected function formatBillingSettings(BillingSettings $settings): array
    {
        return [
            'id' => $settings->id,
            'facility_id' => $settings->facility_id,
            'payment_terms_days' => $settings->payment_terms_days,
            'invoice_frequency' => $settings->invoice_frequency,
            'currency' => $settings->currency,
            'default_bill_rate' => $settings->default_bill_rate,
            'default_pay_rate' => $settings->default_pay_rate,
            'default_markup_percent' => $settings->default_markup_percent,
            'overtime_multiplier' => $settings->overtime_multiplier,
            'holiday_multiplier' => $settings->holiday_multiplier,
            'timesheet_required' => $settings->timesheet_required,
            'expense_allowed' => $settings->expense_allowed,
            'minimum_bill_hours' => $settings->minimum_bill_hours,
            'source' => $settings->source,
            'contract_id' => $settings->contract_id,
            'applied_at' => $settings->applied_at?->format('Y-m-d H:i:s'),
            'contract' => $settings->contract ? [
                'id' => $settings->contract->id,
                'document_type' => $settings->contract->document_type,
                'file_name' => $settings->contract->file_name,
            ] : null,
        ];
    }
}
