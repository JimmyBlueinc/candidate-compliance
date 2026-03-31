<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\Org;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
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
            'invoice_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],
        ]);

        $invoice = DB::transaction(function () use ($validated, $orgId) {
            /** @var Invoice $invoice */
            $invoice = Invoice::query()
                ->where('tenant_id', $orgId)
                ->lockForUpdate()
                ->findOrFail($validated['invoice_id']);

            // Load payments inside the lock to get accurate balance
            $invoice->load('payments');

            $amountPaid = (float) $invoice->payments->sum('amount');
            $totalAmount = (float) $invoice->total_amount;
            $balanceDue = round($totalAmount - $amountPaid, 2);
            $paymentAmount = (float) $validated['amount'];

            if ($paymentAmount > $balanceDue) {
                // Return 422 Unprocessable Entity
                abort(response()->json([
                    'message' => "Payment amount (\${$paymentAmount}) exceeds remaining balance (\${$balanceDue}).",
                ], 422));
            }

            Payment::create([
                'tenant_id' => $orgId,
                'invoice_id' => $invoice->id,
                'amount' => $paymentAmount,
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
            ]);

            // Refresh payments for the response
            return $invoice->load('payments');
        });

        $amountPaid = (float) $invoice->payments->sum('amount');
        $totalAmount = (float) $invoice->total_amount;

        return response()->json([
            'data' => [
                'id' => $invoice->id,
                'facility_name' => $invoice->facility_name,
                'week_start_date' => optional($invoice->week_start_date)->toDateString(),
                'week_end_date' => optional($invoice->week_end_date)->toDateString(),
                'total_amount' => round($totalAmount, 2),
                'status' => $invoice->status,
                'payments' => $invoice->payments,
                'amount_paid' => round($amountPaid, 2),
                'balance_due' => round($totalAmount - $amountPaid, 2),
            ],
        ]);
    }
}
