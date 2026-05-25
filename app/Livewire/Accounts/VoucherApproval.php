<?php

declare(strict_types=1);

namespace App\Livewire\Accounts;

use App\Modules\Expense\Models\Expense;
use Livewire\Component;

class VoucherApproval extends Component
{
    /**
     * Approve a pending voucher/expense.
     */
    public function approve(int $expenseId): void
    {
        $expense = Expense::find($expenseId);

        if ($expense && $expense->status === 'pending') {
            $expense->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Voucher for BDT " . number_format((float)$expense->amount, 2) . " has been approved."
            ]);
        }
    }

    /**
     * Reject a pending voucher/expense.
     */
    public function reject(int $expenseId): void
    {
        $expense = Expense::find($expenseId);

        if ($expense && $expense->status === 'pending') {
            $expense->update([
                'status' => 'rejected',
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "Voucher for BDT " . number_format((float)$expense->amount, 2) . " has been rejected."
            ]);
        }
    }

    /**
     * Render the component.
     */
    public function render()
    {
        // Fetch expenses where status is 'pending', eager loading relationships
        $pendingVouchers = Expense::with(['category', 'user'])
            ->where('status', 'pending')
            ->orderBy('expense_date', 'asc')
            ->get();

        return view('livewire.accounts.voucher-approval', [
            'pendingVouchers' => $pendingVouchers,
        ]);
    }
}
