<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\JournalEntry;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AccountingService
{
    public function createInvoiceJournalEntry(Invoice $invoice): JournalEntry
    {
        // Get account IDs
        $arAccount = ChartOfAccount::where('code', '1200')->first(); // Accounts Receivable
        $revenueAccount = ChartOfAccount::where('code', '4110')->first(); // Service Revenue
        $taxPayableAccount = ChartOfAccount::where('code', '2120')->first(); // Tax Payable

        return JournalEntry::createEntry(
            date: $invoice->created_at->format('Y-m-d'),
            description: "Invoice {$invoice->invoice_number} - {$invoice->customer->name}",
            lines: [
                [
                    'account_id' => $arAccount->id,
                    'debit' => $invoice->total,
                    'credit' => 0,
                ],
                [
                    'account_id' => $revenueAccount->id,
                    'debit' => 0,
                    'credit' => $invoice->subtotal,
                ],
                [
                    'account_id' => $taxPayableAccount->id,
                    'debit' => 0,
                    'credit' => $invoice->tax,
                ],
            ],
            referenceType: 'invoice',
            referenceId: $invoice->id,
            createdBy: auth()->id()
        );
    }

    public function createPaymentJournalEntry(Payment $payment): JournalEntry
    {
        // Get account IDs
        $arAccount = ChartOfAccount::where('code', '1200')->first(); // Accounts Receivable
        $cashAccount = ChartOfAccount::where('code', '1110')->first(); // Cash
        $bankAccount = ChartOfAccount::where('code', '1120')->first(); // Bank

        $debitAccount = match($payment->method) {
            'cash' => $cashAccount,
            'bank_transfer', 'credit_card', 'e_wallet', 'gateway' => $bankAccount,
            default => $cashAccount,
        };

        return JournalEntry::createEntry(
            date: $payment->paid_at->format('Y-m-d'),
            description: "Payment for Invoice {$payment->invoice->invoice_number} - {$payment->customer->name}",
            lines: [
                [
                    'account_id' => $debitAccount->id,
                    'debit' => $payment->amount,
                    'credit' => 0,
                ],
                [
                    'account_id' => $arAccount->id,
                    'debit' => 0,
                    'credit' => $payment->amount,
                ],
            ],
            referenceType: 'payment',
            referenceId: $payment->id,
            createdBy: $payment->recorded_by
        );
    }

    public function getIncomeStatement(Carbon $startDate, Carbon $endDate): array
    {
        $revenueAccounts = ChartOfAccount::where('type', 'income')
            ->where('status', 'active')
            ->get();

        $expenseAccounts = ChartOfAccount::where('type', 'expense')
            ->where('status', 'active')
            ->get();

        $revenues = [];
        $totalRevenue = 0;

        foreach ($revenueAccounts as $account) {
            $balance = $this->getAccountBalance($account->id, $startDate, $endDate);
            if ($balance != 0) {
                $revenues[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $balance,
                ];
                $totalRevenue += $balance;
            }
        }

        $expenses = [];
        $totalExpense = 0;

        foreach ($expenseAccounts as $account) {
            $balance = $this->getAccountBalance($account->id, $startDate, $endDate);
            if ($balance != 0) {
                $expenses[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $balance,
                ];
                $totalExpense += $balance;
            }
        }

        return [
            'period_start' => $startDate->format('Y-m-d'),
            'period_end' => $endDate->format('Y-m-d'),
            'revenues' => $revenues,
            'total_revenue' => $totalRevenue,
            'expenses' => $expenses,
            'total_expense' => $totalExpense,
            'net_income' => $totalRevenue - $totalExpense,
        ];
    }

    public function getBalanceSheet(Carbon $date): array
    {
        $assets = $this->getAccountsByType('asset', null, $date);
        $liabilities = $this->getAccountsByType('liability', null, $date);
        $equity = $this->getAccountsByType('equity', null, $date);

        return [
            'date' => $date->format('Y-m-d'),
            'assets' => $assets['accounts'],
            'total_assets' => $assets['total'],
            'liabilities' => $liabilities['accounts'],
            'total_liabilities' => $liabilities['total'],
            'equity' => $equity['accounts'],
            'total_equity' => $equity['total'],
            'total_liabilities_equity' => $liabilities['total'] + $equity['total'],
        ];
    }

    private function getAccountBalance(int $accountId, ?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $query = DB::table('journal_lines')
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_lines.account_id', $accountId);

        if ($startDate) {
            $query->where('journal_entries.date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('journal_entries.date', '<=', $endDate);
        }

        $result = $query->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')->first();

        $debit = $result->total_debit ?? 0;
        $credit = $result->total_credit ?? 0;

        $account = ChartOfAccount::find($accountId);

        if (in_array($account->type, ['asset', 'expense'])) {
            return $debit - $credit;
        } else {
            return $credit - $debit;
        }
    }

    private function getAccountsByType(string $type, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $accounts = ChartOfAccount::where('type', $type)
            ->where('status', 'active')
            ->get();

        $result = [];
        $total = 0;

        foreach ($accounts as $account) {
            $balance = $this->getAccountBalance($account->id, $startDate, $endDate);
            if ($balance != 0) {
                $result[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $balance,
                ];
                $total += $balance;
            }
        }

        return [
            'accounts' => $result,
            'total' => $total,
        ];
    }
}
