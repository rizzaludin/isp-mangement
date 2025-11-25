<?php

namespace App\Jobs;

use App\Services\BillingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckOverdueInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public function handle(BillingService $billingService): void
    {
        Log::info("Checking for overdue invoices");

        try {
            $result = $billingService->checkOverdueInvoices();

            Log::info("Found {$result['overdue_count']} overdue invoices, suspended {$result['suspended_count']} subscriptions");
        } catch (\Exception $e) {
            Log::error("Overdue check failed: " . $e->getMessage());
            throw $e;
        }
    }
}
