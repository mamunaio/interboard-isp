<?php

declare(strict_types=1);

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Models\ChartOfAccount;
use App\Modules\Billing\Models\JournalLine;
use Illuminate\Support\Collection;

class FinancialStatementService
{
    /**
     * Generate a Profit and Loss (Income) Statement for a specific period.
     */
    public function generateProfitAndLoss(int $tenantId, string $startDate, string $endDate): array
    {
        // Revenue Accounts: Credit is positive, Debit is negative
        $revenues = $this->getAccountBalances($tenantId, 'revenue', $startDate, $endDate);
        
        // Expense Accounts: Debit is positive, Credit is negative
        $expenses = $this->getAccountBalances($tenantId, 'expense', $startDate, $endDate);

        $totalRevenue = $revenues->sum('balance');
        $totalExpense = $expenses->sum('balance');
        
        return [
            'period' => compact('startDate', 'endDate'),
            'revenues' => $revenues,
            'total_revenue' => $totalRevenue,
            'expenses' => $expenses,
            'total_expense' => $totalExpense,
            'net_income' => $totalRevenue - $totalExpense,
        ];
    }

    /**
     * Generate a Balance Sheet as of a specific date.
     */
    public function generateBalanceSheet(int $tenantId, string $asOfDate): array
    {
        // For balance sheet, we take all history up to $asOfDate
        $assets = $this->getAccountBalances($tenantId, 'asset', null, $asOfDate);
        $liabilities = $this->getAccountBalances($tenantId, 'liability', null, $asOfDate);
        $equity = $this->getAccountBalances($tenantId, 'equity', null, $asOfDate);

        // Assets: Debit - Credit
        $totalAssets = $assets->sum('balance');
        
        // Liabilities & Equity: Credit - Debit
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity = $equity->sum('balance');

        return [
            'as_of_date' => $asOfDate,
            'assets' => $assets,
            'total_assets' => $totalAssets,
            'liabilities' => $liabilities,
            'total_liabilities' => $totalLiabilities,
            'equity' => $equity,
            'total_equity' => $totalEquity,
            'total_liabilities_and_equity' => $totalLiabilities + $totalEquity,
        ];
    }

    /**
     * Helper to calculate balance per account type.
     */
    protected function getAccountBalances(int $tenantId, string $accountType, ?string $startDate, string $endDate): Collection
    {
        $query = JournalLine::join('chart_of_accounts', 'journal_lines.chart_of_account_id', '=', 'chart_of_accounts.id')
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('chart_of_accounts.tenant_id', $tenantId)
            ->where('chart_of_accounts.account_type', $accountType)
            ->where('journal_entries.status', 'posted')
            ->where('journal_entries.entry_date', '<=', $endDate);

        if ($startDate) {
            $query->where('journal_entries.entry_date', '>=', $startDate);
        }

        $lines = $query->selectRaw('chart_of_accounts.account_name, chart_of_accounts.account_code, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.account_name', 'chart_of_accounts.account_code')
            ->get();

        return $lines->map(function ($line) use ($accountType) {
            // Normal balance rules:
            // Assets & Expenses: Normal balance is Debit (Debit - Credit)
            // Liabilities, Equity, Revenue: Normal balance is Credit (Credit - Debit)
            if (in_array($accountType, ['asset', 'expense'])) {
                $balance = $line->total_debit - $line->total_credit;
            } else {
                $balance = $line->total_credit - $line->total_debit;
            }

            return [
                'account_code' => $line->account_code,
                'account_name' => $line->account_name,
                'balance' => (float)$balance,
            ];
        });
    }
}
