<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\AccountingService;

class InvoiceObserver
{
    public function __construct(
        private AccountingService $accountingService
    ) {}

    public function created(Invoice $invoice): void
    {
        // Auto-create journal entry
        try {
            $this->accountingService->createInvoiceJournalEntry($invoice);
        } catch (\Exception $e) {
            \Log::error("Failed to create journal entry for invoice {$invoice->id}: " . $e->getMessage());
        }
    }
}
