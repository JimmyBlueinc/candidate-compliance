<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractTerm;
use App\Models\ContractRateLine;
use App\Models\Facility;
use App\Services\ContractExtractionService;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ContractController extends Controller
{
    public function __construct(
        protected ContractExtractionService $extractionService
    ) {}

    /**
     * List contracts for a facility.
     */
    public function index(Request $request, int $facilityId): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $facility = Facility::findOrFail($facilityId);
        if ($facility->organization_id !== $orgId) {
            return response()->json(['message' => 'Facility not found.'], 404);
        }

        $contracts = Contract::with(['terms', 'rateLines'])
            ->where('facility_id', $facilityId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->api([
            'contracts' => $contracts->map(fn ($c) => $this->formatContract($c)),
        ]);
    }

    /**
     * Upload a new contract.
     */
    public function store(Request $request, int $facilityId): JsonResponse
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
            'document_type' => ['required', 'in:msa,sow,amendment,rate_card'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'], // 10MB
            'effective_start_date' => ['nullable', 'date'],
            'effective_end_date' => ['nullable', 'date'],
        ]);

        $file = $request->file('file');
        $filename = 'contract_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = 'contracts/facility-' . $facilityId . '/' . $filename;

        // Store in private_assets disk (S3)
        Storage::disk('private_assets')->put($path, file_get_contents($file));

        $contract = Contract::create([
            'facility_id' => $facilityId,
            'organization_id' => $orgId,
            'document_type' => $validated['document_type'],
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'version' => 1,
            'status' => Contract::STATUS_UPLOADED,
            'effective_start_date' => $validated['effective_start_date'] ?? null,
            'effective_end_date' => $validated['effective_end_date'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        Log::info('[CONTRACT] Uploaded', [
            'contract_id' => $contract->id,
            'facility_id' => $facilityId,
            'document_type' => $contract->document_type,
        ]);

        return response()->api([
            'contract' => $this->formatContract($contract),
        ], 201, [], 'Contract uploaded successfully.');
    }

    /**
     * Get a specific contract.
     */
    public function show(Request $request, int $facilityId, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $contract = Contract::with(['terms', 'rateLines'])
            ->where('facility_id', $facilityId)
            ->where('organization_id', $orgId)
            ->findOrFail($id);

        return response()->api([
            'contract' => $this->formatContract($contract),
        ]);
    }

    /**
     * Process contract extraction.
     */
    public function extract(Request $request, int $facilityId, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $contract = Contract::where('facility_id', $facilityId)
            ->where('organization_id', $orgId)
            ->findOrFail($id);

        if (!in_array($contract->status, [Contract::STATUS_UPLOADED, Contract::STATUS_PROCESSED])) {
            return response()->json([
                'message' => 'Contract cannot be extracted in current status.',
            ], 400);
        }

        try {
            $result = $this->extractionService->extract($contract);

            $contract->refresh();

            return response()->api([
                'contract' => $this->formatContract($contract),
                'extraction' => [
                    'terms' => $result['terms'],
                    'rate_lines_count' => count($result['rate_lines']),
                ],
            ], 200, [], 'Contract extraction completed.');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Extraction failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get extracted terms for review.
     */
    public function extractedTerms(Request $request, int $facilityId, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $contract = Contract::with(['terms', 'rateLines'])
            ->where('facility_id', $facilityId)
            ->where('organization_id', $orgId)
            ->findOrFail($id);

        if (!$contract->terms) {
            return response()->json([
                'message' => 'No extracted terms found. Run extraction first.',
            ], 404);
        }

        return response()->api([
            'terms' => $this->formatContractTerms($contract->terms),
            'rate_lines' => $contract->rateLines->map(fn ($rl) => $this->formatRateLine($rl)),
        ]);
    }

    /**
     * Review and approve/reject contract terms.
     */
    public function review(Request $request, int $facilityId, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $contract = Contract::with(['terms', 'rateLines'])
            ->where('facility_id', $facilityId)
            ->where('organization_id', $orgId)
            ->findOrFail($id);

        if (!$contract->isReadyForReview()) {
            return response()->json([
                'message' => 'Contract is not ready for review.',
            ], 400);
        }

        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject,modify'],
            'terms' => ['nullable', 'array'],
            'terms.payment_terms_days' => ['nullable', 'integer'],
            'terms.invoice_frequency' => ['nullable', 'string'],
            'terms.bill_rate_amount' => ['nullable', 'numeric'],
            'terms.pay_rate_amount' => ['nullable', 'numeric'],
            'terms.markup_percent' => ['nullable', 'numeric'],
            'terms.overtime_multiplier' => ['nullable', 'numeric'],
            'terms.holiday_multiplier' => ['nullable', 'numeric'],
            'terms.timesheet_required' => ['nullable', 'boolean'],
            'terms.expense_allowed' => ['nullable', 'boolean'],
            'terms.minimum_bill_hours' => ['nullable', 'numeric'],
            'rate_lines' => ['nullable', 'array'],
            'rate_lines.*.id' => ['required', 'integer', 'exists:contract_rate_lines,id'],
            'rate_lines.*.bill_rate' => ['nullable', 'numeric'],
            'rate_lines.*.pay_rate' => ['nullable', 'numeric'],
            'rate_lines.*.overtime_rate' => ['nullable', 'numeric'],
            'rate_lines.*.holiday_rate' => ['nullable', 'numeric'],
        ]);

        $action = $validated['action'];

        // Update terms
        if ($contract->terms) {
            $termsData = [
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_status' => match ($action) {
                    'approve' => ContractTerm::REVIEW_APPROVED,
                    'reject' => ContractTerm::REVIEW_REJECTED,
                    'modify' => ContractTerm::REVIEW_MODIFIED,
                },
            ];

            // Store approved values if modifying or approving
            if (in_array($action, ['approve', 'modify']) && isset($validated['terms'])) {
                foreach ($validated['terms'] as $field => $value) {
                    $approvedField = 'approved_' . $field;
                    if (in_array($approvedField, (new ContractTerm())->getFillable())) {
                        $termsData[$approvedField] = $value;
                    }
                }
            }

            $contract->terms->update($termsData);
        }

        // Update rate lines
        if (isset($validated['rate_lines'])) {
            foreach ($validated['rate_lines'] as $rateLineData) {
                $rateLine = ContractRateLine::where('contract_id', $contract->id)
                    ->where('id', $rateLineData['id'])
                    ->first();

                if ($rateLine) {
                    $rateLine->update([
                        'review_status' => in_array($action, ['approve', 'modify']) 
                            ? ContractRateLine::REVIEW_APPROVED 
                            : ContractRateLine::REVIEW_REJECTED,
                        'approved_bill_rate' => $rateLineData['bill_rate'] ?? null,
                        'approved_pay_rate' => $rateLineData['pay_rate'] ?? null,
                        'approved_overtime_rate' => $rateLineData['overtime_rate'] ?? null,
                        'approved_holiday_rate' => $rateLineData['holiday_rate'] ?? null,
                    ]);
                }
            }
        }

        // Update contract status
        $contract->update([
            'status' => $action === 'reject' ? Contract::STATUS_REVIEWED : Contract::STATUS_APPROVED,
        ]);

        Log::info('[CONTRACT] Review completed', [
            'contract_id' => $contract->id,
            'action' => $action,
            'reviewed_by' => $request->user()->id,
        ]);

        $contract->refresh();

        return response()->api([
            'contract' => $this->formatContract($contract),
        ], 200, [], 'Contract review completed.');
    }

    /**
     * Delete a contract.
     */
    public function destroy(Request $request, int $facilityId, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json(['message' => 'Organization context missing.'], 400);
        }

        $contract = Contract::where('facility_id', $facilityId)
            ->where('organization_id', $orgId)
            ->findOrFail($id);

        // Delete file from S3
        if ($contract->file_path) {
            Storage::disk('private_assets')->delete($contract->file_path);
        }

        $contract->delete();

        return response()->api([], 200, [], 'Contract deleted.');
    }

    /**
     * Format contract for API response.
     */
    protected function formatContract(Contract $contract): array
    {
        return [
            'id' => $contract->id,
            'facility_id' => $contract->facility_id,
            'document_type' => $contract->document_type,
            'file_name' => $contract->file_name,
            'version' => $contract->version,
            'status' => $contract->status,
            'effective_start_date' => $contract->effective_start_date?->format('Y-m-d'),
            'effective_end_date' => $contract->effective_end_date?->format('Y-m-d'),
            'created_at' => $contract->created_at->format('Y-m-d H:i:s'),
            'created_by' => $contract->created_by,
            'terms' => $contract->terms ? $this->formatContractTerms($contract->terms) : null,
            'rate_lines' => $contract->rateLines->map(fn ($rl) => $this->formatRateLine($rl)),
        ];
    }

    /**
     * Format contract terms for API response.
     */
    protected function formatContractTerms(ContractTerm $terms): array
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
            'reviewed_at' => $terms->reviewed_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Format rate line for API response.
     */
    protected function formatRateLine(ContractRateLine $rateLine): array
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
}
