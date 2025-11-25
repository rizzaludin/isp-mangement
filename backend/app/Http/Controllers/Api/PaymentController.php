<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\AuditLog;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(
        private BillingService $billingService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Payment::query()->with(['invoice', 'customer', 'recordedBy']);

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('invoice_id')) {
            $query->where('invoice_id', $request->invoice_id);
        }

        if ($request->has('method')) {
            $query->where('method', $request->method);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('paid_at', [$request->start_date, $request->end_date]);
        }

        $perPage = $request->get('per_page', 15);
        $payments = $query->latest('paid_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0',
            'method' => 'required|in:cash,bank_transfer,credit_card,e_wallet,gateway',
            'reference' => 'nullable|string|max:255',
            'paid_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $invoice = Invoice::with('customer')->findOrFail($validated['invoice_id']);

        if ($invoice->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice is already paid',
            ], 422);
        }

        $validated['customer_id'] = $invoice->customer_id;
        $validated['recorded_by'] = auth()->id();

        $payment = $this->billingService->recordPayment($invoice, $validated);

        AuditLog::log('create', Payment::class, $payment->id, null, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully',
            'data' => $payment->load(['invoice', 'customer']),
        ], 201);
    }

    public function show(Payment $payment): JsonResponse
    {
        $payment->load(['invoice', 'customer', 'recordedBy']);

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

    public function destroy(Payment $payment): JsonResponse
    {
        // Only allow deletion if invoice is not fully paid
        if ($payment->invoice->isPaid() && $payment->invoice->payments()->count() === 1) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete payment from fully paid invoice',
            ], 422);
        }

        AuditLog::log('delete', Payment::class, $payment->id, $payment->toArray());

        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment deleted successfully',
        ]);
    }
}
