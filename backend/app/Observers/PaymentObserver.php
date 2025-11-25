<?php

namespace App\Observers;

use App\Models\Payment;
use App\Jobs\UnsuspendSubscriptionJob;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        $invoice = $payment->invoice;

        // Check if invoice is now fully paid
        if ($invoice->total_paid >= $invoice->total) {
            $invoice->markAsPaid();

            // Auto-unsuspend subscription if it was suspended
            if ($invoice->subscription && $invoice->subscription->isSuspended()) {
                UnsuspendSubscriptionJob::dispatch($invoice->subscription);
            }
        }
    }
}
