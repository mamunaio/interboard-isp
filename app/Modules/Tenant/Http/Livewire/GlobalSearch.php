<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Http\Livewire;

use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\PaymentReceipt;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $query = '';
    public array $customers = [];
    public array $invoices = [];
    public array $subscriptions = [];
    public array $receipts = [];

    protected $queryString = [
        'query' => ['except' => ''],
    ];

    public function mount(): void
    {
        if ($this->query !== '') {
            $this->performSearch();
        }
    }

    public function updatedQuery(): void
    {
        $this->performSearch();
    }

    public function performSearch(): void
    {
        $term = trim($this->query);
        if (strlen($term) < 2) {
            $this->customers = [];
            $this->invoices = [];
            $this->subscriptions = [];
            $this->receipts = [];
            return;
        }

        // Search Customers
        $this->customers = Customer::where('full_name', 'like', '%' . $term . '%')
            ->orWhere('customer_code', 'like', '%' . $term . '%')
            ->limit(10)
            ->get()
            ->toArray();

        // Search Invoices
        $this->invoices = Invoice::where('invoice_number', 'like', '%' . $term . '%')
            ->limit(10)
            ->get()
            ->toArray();

        // Search Subscriptions
        $this->subscriptions = CustomerServiceSubscription::with('customer')
            ->where('subscription_code', 'like', '%' . $term . '%')
            ->limit(10)
            ->get()
            ->toArray();

        // Search Payment Receipts
        $this->receipts = PaymentReceipt::with('customer')
            ->where('receipt_number', 'like', '%' . $term . '%')
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function render()
    {
        return view('tenant::livewire.global-search');
    }
}
