<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BillingService
{
    public function __construct(
        private AccountingService $accountingService
    ) {}

    public function createInvoice(Subscription $subscription, array $data): Invoice
    {
        $periodStart = Carbon::parse($data['period_start']);
        $periodEnd = Carbon::parse($data['period_end']);
        $dueDate = Carbon::parse($data['due_date']);

        $subtotal = $this->calculateProrate(
            $subscription->service->price,
            $periodStart,
            $periodEnd,
            $subscription->service->billing_cycle
        );

        $tax = $subtotal * 0.11; // 11% PPN
        $discount = $data['discount'] ?? 0;
        $total = $subtotal + $tax - $discount;

        return Invoice::create([
            'customer_id' => $subscription->customer_id,
            'subscription_id' => $subscription->id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $total,
            'status' => 'unpaid',
            'due_date' => $dueDate,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function generateInvoices(array $data): array
    {
        $periodStart = Carbon::parse($data['period_start']);
        $periodEnd = Carbon::parse($data['period_end']);
        $dueDays = $data['due_days'];
        $dueDate = $periodEnd->copy()->addDays($dueDays);

        $query = Subscription::query()
            ->where('status', 'active')
            ->with(['customer', 'service']);

        if (isset($data['subscription_ids'])) {
            $query->whereIn('id', $data['subscription_ids']);
        }

        $subscriptions = $query->get();
        $invoices = [];

        foreach ($subscriptions as $subscription) {
            // Check if invoice already exists for this period
            $exists = Invoice::where('subscription_id', $subscription->id)
                ->where('period_start', $periodStart)
                ->where('period_end', $periodEnd)
                ->exists();

            if (!$exists) {
                $invoices[] = $this->createInvoice($subscription, [
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'due_date' => $dueDate,
                ]);
            }
        }

        return $invoices;
    }

    public function recordPayment(Invoice $invoice, array $data): Payment
    {
        return DB::transaction(function () use ($invoice, $data) {
            $payment = Payment::create($data);

            // Update invoice status if fully paid
            if ($invoice->total_paid + $payment->amount >= $invoice->total) {
                $invoice->markAsPaid();
                
                // Unsuspend subscription if it was suspended due to non-payment
                if ($invoice->subscription && $invoice->subscription->isSuspended()) {
                    $invoice->subscription->update(['status' => 'active']);
                    $invoice->subscription->radiusUser->update(['status' => 'active']);
                }
            }

            // Create journal entry
            $this->accountingService->createPaymentJournalEntry($payment);

            return $payment;
        });
    }

    public function calculateProrate(
        float $fullPrice,
        Carbon $periodStart,
        Carbon $periodEnd,
        string $billingCycle = 'monthly'
    ): float {
        $daysInPeriod = $periodStart->diffInDays($periodEnd) + 1;

        return match($billingCycle) {
            'daily' => $fullPrice * $daysInPeriod,
            'weekly' => ($fullPrice / 7) * $daysInPeriod,
            'monthly' => ($fullPrice / 30) * $daysInPeriod,
            'yearly' => ($fullPrice / 365) * $daysInPeriod,
            default => $fullPrice,
        };
    }

    public function checkOverdueInvoices(): array
    {
        $overdueInvoices = Invoice::where('status', 'unpaid')
            ->where('due_date', '<', now())
            ->with(['subscription'])
            ->get();

        $suspendedCount = 0;

        foreach ($overdueInvoices as $invoice) {
            $invoice->markAsOverdue();

            // Auto-suspend subscription if configured
            if ($invoice->subscription && $invoice->subscription->isActive()) {
                $invoice->subscription->update(['status' => 'suspended']);
                $invoice->subscription->radiusUser->update(['status' => 'suspended']);
                $suspendedCount++;
            }
        }

        return [
            'overdue_count' => $overdueInvoices->count(),
            'suspended_count' => $suspendedCount,
        ];
    }
}
