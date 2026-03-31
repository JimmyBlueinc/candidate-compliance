<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        if (!$orgId) {
            return response()->json([
                'message' => 'Organization context missing.',
            ], 400);
        }

        $validated = $request->validate([
            'facility_name' => ['required', 'string', 'max:255'],
            'week_start_date' => ['required', 'date'],
            'week_end_date' => ['required', 'date', 'after_or_equal:week_start_date'],
            'total_hours' => ['required', 'numeric', 'min:0'],
            'bill_rate' => ['required', 'numeric', 'min:0'],
            'total_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'due_at' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'nullable', 'in:draft,issued,paid'],
        ]);

        $hours = (float) $validated['total_hours'];
        $rate = (float) $validated['bill_rate'];
        $amount = array_key_exists('total_amount', $validated) && $validated['total_amount'] !== null
            ? (float) $validated['total_amount']
            : round($hours * $rate, 2);

        $invoice = Invoice::query()->create([
            'tenant_id' => (int) $orgId,
            'facility_name' => (string) $validated['facility_name'],
            'invoice_number' => 'INV-MAN-' . strtoupper(Str::random(8)),
            'week_start_date' => (string) $validated['week_start_date'],
            'week_end_date' => (string) $validated['week_end_date'],
            'total_hours' => $hours,
            'bill_rate' => $rate,
            'total_amount' => $amount,
            'status' => (string) ($validated['status'] ?? 'draft'),
            'due_at' => $validated['due_at'] ?? null,
            'created_by_user_id' => (int) ($request->user()?->id ?? 0),
        ]);

        return response()->api($invoice, 201, [], 'Invoice created successfully.');
    }

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
