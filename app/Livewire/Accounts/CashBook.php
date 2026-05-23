<?php

declare(strict_types=1);

namespace App\Livewire\Accounts;

use App\Modules\Billing\Models\PaymentReceipt;
use App\Modules\Expense\Models\Expense;
use Carbon\Carbon;
use Livewire\Component;

class CashBook extends Component
{
    public $fromDate;
    public $toDate;

    public function mount()
    {
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
    }

    public function render()
    {
        // Add basic validation for dates
        $this->validate([
            'fromDate' => 'required|date',
            'toDate' => 'required|date|after_or_equal:fromDate',
        ]);

        $startDate = Carbon::parse($this->fromDate)->startOfDay();
        $endDate = Carbon::parse($this->toDate)->endOfDay();

        // Query Income (Payment Receipts)
        $incomes = PaymentReceipt::with('customer')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalIncome = $incomes->sum('amount');

        // Query Expenses
        $expenses = Expense::with('category')
            ->whereBetween('expense_date', [$this->fromDate, $this->toDate])
            ->orderBy('expense_date', 'desc')
            ->get();

        $totalExpense = $expenses->sum('amount');

        $cashOnHand = $totalIncome - $totalExpense;

        return view('livewire.accounts.cash-book', [
            'incomes' => $incomes,
            'expenses' => $expenses,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'cashOnHand' => $cashOnHand,
        ]);
    }
}
