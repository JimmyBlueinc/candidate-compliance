<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $invoices = Invoice::query()
            ->with(['payments'])
            ->where('tenant_id', $orgId)
            ->orderByDesc('week_start_date')
            ->get()
            ->map(function (Invoice $invoice) {
                $amountPaid = (float) $invoice->payments->sum('amount');
                $totalAmount = (float) $invoice->total_amount;

                return [
                    'id' => $invoice->id,
                    'facility_name' => $invoice->facility_name,
                    'week_start_date' => optional($invoice->week_start_date)->toDateString(),
                    'week_end_date' => optional($invoice->week_end_date)->toDateString(),
                    'total_amount' => round($totalAmount, 2),
                    'status' => $invoice->status,
                    'payments' => $invoice->payments,
                    'amount_paid' => round($amountPaid, 2),
                    'balance_due' => round($totalAmount - $amountPaid, 2),
                ];
            })
            ->values();

        return response()->api($invoices);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $invoice = Invoice::query()
            ->with([
                'payments',
                'lineItems',
                'assignment',
            ])
            ->where('tenant_id', $orgId)
            ->findOrFail($id);

        return response()->api($invoice);
    }

    public function issue(Request $request, int $id, InvoiceService $service): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $validated = $request->validate([
            'due_at' => ['sometimes', 'nullable', 'date'],
        ]);

        $dueAt = null;
        if (array_key_exists('due_at', $validated) && $validated['due_at']) {
            $dueAt = new \DateTimeImmutable((string) $validated['due_at']);
        }

        $invoice = $service->issueInvoice(
            tenantId: (int) $orgId,
            invoiceId: $id,
            actor: $request->user(),
            dueAt: $dueAt,
        );

        return response()->api($invoice);
    }

    public function markPaid(Request $request, int $id, InvoiceService $service): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $invoice = $service->markInvoicePaid(
            tenantId: (int) $orgId,
            invoiceId: $id,
            actor: $request->user(),
        );

        return response()->api($invoice);
    }
}
