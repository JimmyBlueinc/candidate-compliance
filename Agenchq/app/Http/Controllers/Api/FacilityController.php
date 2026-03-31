<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Contract;
use App\Models\BillingSettings;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    /**
     * Get facility detail with related data.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $facility = Facility::with([
            'contracts' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
            'contracts.terms',
            'contracts.rateLines',
            'billingSettings',
            'jobOrders' => function ($query) {
                $query->where('status', 'active')->limit(10);
            },
            'assignments' => function ($query) {
                $query->where('status', 'active')->limit(10);
            },
            'assignments.candidate',
            'assignments.jobOrder',
        ])->findOrFail($id);

        // Ensure facility belongs to organization
        if ($facility->organization_id !== $orgId) {
            return response()->json([
                'message' => 'Facility not found.',
            ], 404);
        }

        return response()->api([
            'id' => $facility->id,
            'name' => $facility->name,
            'organization_id' => $facility->organization_id,
            'address' => $facility->address,
            'city' => $facility->city,
            'state' => $facility->state,
            'country' => $facility->country,
            'postal_code' => $facility->postal_code,
            'timezone' => $facility->timezone,
            'facility_type' => $facility->facility_type,
            'facility_type_other' => $facility->facility_type_other,
            'contact_person_name' => $facility->contact_person_name,
            'contact_email' => $facility->contact_email,
            'contact_phone' => $facility->contact_phone,
            'contacts' => $this->formatContacts($facility),
            'contracts' => $facility->contracts->map(fn ($c) => $this->formatContract($c)),
            'billing_settings' => $facility->billingSettings ? $this->formatBillingSettings($facility->billingSettings) : null,
            'assignments' => $facility->assignments->map(fn ($a) => $this->formatAssignment($a)),
            'metadata' => [
                'active_jobs_count' => $facility->jobOrders->count(),
                'active_assignments_count' => $facility->assignments->count(),
                'contracts_count' => $facility->contracts->count(),
            ],
        ]);
    }

    /**
     * Get facility contracts.
     */
    public function contracts(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $facility = Facility::findOrFail($id);

        if ($facility->organization_id !== $orgId) {
            return response()->json([
                'message' => 'Facility not found.',
            ], 404);
        }

        $contracts = Contract::with(['terms', 'rateLines'])
            ->where('facility_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->api([
            'contracts' => $contracts->map(fn ($c) => $this->formatContract($c)),
        ]);
    }

    /**
     * Get facility billing settings.
     */
    public function billing(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $facility = Facility::findOrFail($id);

        if ($facility->organization_id !== $orgId) {
            return response()->json([
                'message' => 'Facility not found.',
            ], 404);
        }

        $settings = BillingSettings::getOrCreateForFacility($id, $orgId);

        return response()->api([
            'billing_settings' => $this->formatBillingSettings($settings),
        ]);
    }

    /**
     * Format contacts for API response.
     */
    protected function formatContacts(Facility $facility): array
    {
        $contacts = [];

        if ($facility->contact_person_name || $facility->contact_email || $facility->contact_phone) {
            $contacts[] = [
                'name' => $facility->contact_person_name,
                'email' => $facility->contact_email,
                'phone' => $facility->contact_phone,
                'type' => 'primary',
            ];
        }

        return $contacts;
    }

    /**
     * Format contract for API response.
     */
    protected function formatContract(Contract $contract): array
    {
        return [
            'id' => $contract->id,
            'document_type' => $contract->document_type,
            'file_name' => $contract->file_name,
            'version' => $contract->version,
            'status' => $contract->status,
            'effective_start_date' => $contract->effective_start_date?->format('Y-m-d'),
            'effective_end_date' => $contract->effective_end_date?->format('Y-m-d'),
            'created_at' => $contract->created_at->format('Y-m-d H:i:s'),
            'terms' => $contract->terms ? $this->formatContractTerms($contract->terms) : null,
            'rate_lines' => $contract->rateLines->map(fn ($rl) => $this->formatRateLine($rl)),
        ];
    }

    /**
     * Format contract terms for API response.
     */
    protected function formatContractTerms($terms): array
    {
        return [
            'id' => $terms->id,
            'payment_terms_days' => $terms->payment_terms_days,
            'invoice_frequency' => $terms->invoice_frequency,
            'currency' => $terms->currency,
            'bill_rate_type' => $terms->bill_rate_type,
            'bill_rate_amount' => $terms->bill_rate_amount,
            'pay_rate_amount' => $terms->pay_rate_amount,
            'markup_percent' => $terms->markup_percent,
            'overtime_multiplier' => $terms->overtime_multiplier,
            'holiday_multiplier' => $terms->holiday_multiplier,
            'timesheet_required' => $terms->timesheet_required,
            'expense_allowed' => $terms->expense_allowed,
            'minimum_bill_hours' => $terms->minimum_bill_hours,
            'confidence' => $terms->confidence_json,
            'source_spans' => $terms->source_spans_json,
            'review_status' => $terms->review_status,
            // Approved values
            'approved_payment_terms_days' => $terms->approved_payment_terms_days,
            'approved_invoice_frequency' => $terms->approved_invoice_frequency,
            'approved_bill_rate_amount' => $terms->approved_bill_rate_amount,
            'approved_pay_rate_amount' => $terms->approved_pay_rate_amount,
            'approved_markup_percent' => $terms->approved_markup_percent,
            'approved_overtime_multiplier' => $terms->approved_overtime_multiplier,
            'approved_holiday_multiplier' => $terms->approved_holiday_multiplier,
            'approved_timesheet_required' => $terms->approved_timesheet_required,
            'approved_expense_allowed' => $terms->approved_expense_allowed,
            'approved_minimum_bill_hours' => $terms->approved_minimum_bill_hours,
        ];
    }

    /**
     * Format rate line for API response.
     */
    protected function formatRateLine($rateLine): array
    {
        return [
            'id' => $rateLine->id,
            'role_title' => $rateLine->role_title,
            'bill_rate' => $rateLine->bill_rate,
            'pay_rate' => $rateLine->pay_rate,
            'overtime_rate' => $rateLine->overtime_rate,
            'holiday_rate' => $rateLine->holiday_rate,
            'currency' => $rateLine->currency,
            'shift_type' => $rateLine->shift_type,
            'unit' => $rateLine->unit,
            'effective_start_date' => $rateLine->effective_start_date?->format('Y-m-d'),
            'effective_end_date' => $rateLine->effective_end_date?->format('Y-m-d'),
            'confidence_score' => $rateLine->confidence_score,
            'source_span' => $rateLine->source_span,
            'review_status' => $rateLine->review_status,
            'approved_bill_rate' => $rateLine->approved_bill_rate,
            'approved_pay_rate' => $rateLine->approved_pay_rate,
            'approved_overtime_rate' => $rateLine->approved_overtime_rate,
            'approved_holiday_rate' => $rateLine->approved_holiday_rate,
        ];
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

    /**
     * Format assignment for API response.
     */
    protected function formatAssignment($assignment): array
    {
        return [
            'id' => $assignment->id,
            'candidate_id' => $assignment->candidate_id,
            'candidate_name' => $assignment->candidate?->full_name ?? $assignment->facility_name ?? 'Unknown',
            'job_order_id' => $assignment->job_order_id,
            'job_title' => $assignment->jobOrder?->title ?? 'Unknown Position',
            'start_date' => $assignment->start_date?->format('Y-m-d'),
            'end_date' => $assignment->end_date?->format('Y-m-d'),
            'pay_rate' => $assignment->pay_rate,
            'bill_rate' => $assignment->bill_rate,
            'status' => $assignment->status,
        ];
    }
}
