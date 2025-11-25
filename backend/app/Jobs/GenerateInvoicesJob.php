<?php

namespace App\Jobs;

use App\Services\BillingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GenerateInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public function __construct(
        public ?Carbon $periodStart = null,
        public ?Carbon $periodEnd = null,
        public int $dueDays = 7
    ) {
        $this->periodStart = $periodStart ?? now()->startOfMonth();
        $this->periodEnd = $periodEnd ?? now()->endOfMonth();
    }

    public function handle(BillingService $billingService): void
    {
        Log::info("Starting invoice generation for period {$this->periodStart->format('Y-m-d')} to {$this->periodEnd->format('Y-m-d')}");

        try {
            $invoices = $billingService->generateInvoices([
                'period_start' => $this->periodStart,
                'period_end' => $this->periodEnd,
                'due_days' => $this->dueDays,
            ]);

            Log::info("Generated " . count($invoices) . " invoices");
        } catch (\Exception $e) {
            Log::error("Invoice generation failed: " . $e->getMessage());
            throw $e;
        }
    }
}
