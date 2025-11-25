<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\AuditLog;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    public function __construct(
        private BillingService $billingService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Invoice::query()->with(['customer', 'subscription', 'payments']);

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        $perPage = $request->get('per_page', 15);
        $invoices = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $invoices,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'due_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $subscription = Subscription::with(['customer', 'service'])->findOrFail($validated['subscription_id']);

        $invoice = $this->billingService->createInvoice($subscription, $validated);

        AuditLog::log('create', Invoice::class, $invoice->id, null, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Invoice created successfully',
            'data' => $invoice->load(['customer', 'subscription']),
        ], 201);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load(['customer', 'subscription', 'payments']);

        return response()->json([
            'success' => true,
            'data' => $invoice,
        ]);
    }

    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update paid invoice',
            ], 422);
        }

        $validated = $request->validate([
            'due_date' => 'sometimes|date',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:unpaid,overdue,cancelled',
        ]);

        $oldValues = $invoice->only(array_keys($validated));
        $invoice->update($validated);

        AuditLog::log('update', Invoice::class, $invoice->id, $oldValues, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Invoice updated successfully',
            'data' => $invoice,
        ]);
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subscription_ids' => 'sometimes|array',
            'subscription_ids.*' => 'exists:subscriptions,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'due_days' => 'required|integer|min:1',
        ]);

        $invoices = $this->billingService->generateInvoices($validated);

        AuditLog::log('generate_invoices', null, null, null, ['count' => count($invoices)]);

        return response()->json([
            'success' => true,
            'message' => count($invoices) . ' invoice(s) generated successfully',
            'data' => $invoices,
        ]);
    }

    public function cancel(Invoice $invoice): JsonResponse
    {
        if ($invoice->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel paid invoice',
            ], 422);
        }

        $invoice->update(['status' => 'cancelled']);

        AuditLog::log('cancel', Invoice::class, $invoice->id);

        return response()->json([
            'success' => true,
            'message' => 'Invoice cancelled successfully',
        ]);
    }

    public function downloadPdf(Invoice $invoice): JsonResponse
    {
        // TODO: Implement PDF generation
        return response()->json([
            'success' => false,
            'message' => 'PDF generation not implemented yet',
        ], 501);
    }
}
