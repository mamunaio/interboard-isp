<?php

declare(strict_types=1);

namespace App\Livewire\Expenses;

use App\Modules\Expense\Models\Expense;
use App\Modules\Expense\Models\ExpenseCategory;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;

class ExpenseManager extends Component
{
    use WithFileUploads;

    #[Validate('required|exists:expense_categories,id')]
    public $expense_category_id = '';

    #[Validate('required|numeric|min:0.01')]
    public $amount = '';

    #[Validate('required|date')]
    public $expense_date;

    #[Validate('nullable|string|max:255')]
    public $reference_no = '';

    #[Validate('nullable|string')]
    public $description = '';

    #[Validate('nullable|file|mimes:jpg,jpeg,png,pdf|max:2048')]
    public $receipt;

    public $categories = [];

    public function mount()
    {
        $this->expense_date = date('Y-m-d');
        // Load all active categories
        $this->categories = ExpenseCategory::where('status', true)->get();
    }

    public function save()
    {
        $this->validate();

        $receiptPath = null;
        if ($this->receipt) {
            $receiptPath = $this->receipt->store('receipts', 'public');
        }

        Expense::create([
            'expense_category_id' => $this->expense_category_id,
            'user_id' => Auth::guard('staff')->id() ?? Auth::id() ?? 1, // Fallback to 1 if testing without auth
            'amount' => $this->amount,
            'expense_date' => $this->expense_date,
            'reference_no' => $this->reference_no,
            'description' => $this->description,
            'receipt_path' => $receiptPath,
        ]);

        $this->reset(['expense_category_id', 'amount', 'reference_no', 'description', 'receipt']);
        $this->expense_date = date('Y-m-d');

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Expense recorded successfully!'
        ]);
    }

    public function render()
    {
        // Fetch expenses eager loading category and user
        $expenses = Expense::with(['category', 'user'])
            ->orderBy('expense_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return view('livewire.expenses.expense-manager', [
            'expenses' => $expenses
        ]);
    }
}
