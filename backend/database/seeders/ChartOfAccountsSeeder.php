<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // Assets
            ['code' => '1000', 'name' => 'ASSETS', 'type' => 'asset', 'parent_id' => null],
            ['code' => '1100', 'name' => 'Current Assets', 'type' => 'asset', 'parent_id' => null],
            ['code' => '1110', 'name' => 'Cash', 'type' => 'asset', 'parent_id' => null],
            ['code' => '1120', 'name' => 'Bank Account', 'type' => 'asset', 'parent_id' => null],
            ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'parent_id' => null],
            
            // Liabilities
            ['code' => '2000', 'name' => 'LIABILITIES', 'type' => 'liability', 'parent_id' => null],
            ['code' => '2100', 'name' => 'Current Liabilities', 'type' => 'liability', 'parent_id' => null],
            ['code' => '2110', 'name' => 'Accounts Payable', 'type' => 'liability', 'parent_id' => null],
            ['code' => '2120', 'name' => 'Tax Payable', 'type' => 'liability', 'parent_id' => null],
            
            // Equity
            ['code' => '3000', 'name' => 'EQUITY', 'type' => 'equity', 'parent_id' => null],
            ['code' => '3100', 'name' => 'Owner\'s Equity', 'type' => 'equity', 'parent_id' => null],
            ['code' => '3200', 'name' => 'Retained Earnings', 'type' => 'equity', 'parent_id' => null],
            
            // Income
            ['code' => '4000', 'name' => 'REVENUE', 'type' => 'income', 'parent_id' => null],
            ['code' => '4100', 'name' => 'Service Revenue', 'type' => 'income', 'parent_id' => null],
            ['code' => '4110', 'name' => 'Internet Service Revenue', 'type' => 'income', 'parent_id' => null],
            ['code' => '4120', 'name' => 'Installation Revenue', 'type' => 'income', 'parent_id' => null],
            
            // Expenses
            ['code' => '5000', 'name' => 'EXPENSES', 'type' => 'expense', 'parent_id' => null],
            ['code' => '5100', 'name' => 'Operating Expenses', 'type' => 'expense', 'parent_id' => null],
            ['code' => '5110', 'name' => 'Bandwidth Cost', 'type' => 'expense', 'parent_id' => null],
            ['code' => '5120', 'name' => 'Maintenance Cost', 'type' => 'expense', 'parent_id' => null],
            ['code' => '5130', 'name' => 'Employee Salary', 'type' => 'expense', 'parent_id' => null],
            ['code' => '5140', 'name' => 'Utility Cost', 'type' => 'expense', 'parent_id' => null],
        ];

        foreach ($accounts as $account) {
            DB::table('chart_of_accounts')->insert([
                'code' => $account['code'],
                'name' => $account['name'],
                'type' => $account['type'],
                'parent_id' => $account['parent_id'],
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
