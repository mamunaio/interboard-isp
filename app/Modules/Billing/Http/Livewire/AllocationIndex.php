<?php

declare(strict_types=1);

namespace App\Modules\Billing\Http\Livewire;

use App\Core\Tenant\TenantManager;
use App\Modules\Billing\Models\PaymentAllocation;
use Livewire\Component;
use Livewire\WithPagination;

class AllocationIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $tenantId = app(TenantManager::class)->getTenantId();

        $allocations = PaymentAllocation::where('tenant_id', $tenantId)
            ->when($this->search, function ($query) {
                $query->where(function ($sub) {
                    $sub->where('notes', 'like', '%' . $this->search . '%')
                        ->orWhereHas('invoice', function ($q) {
                            $q->where('invoice_number', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('receipt', function ($q) {
                            $q->where('receipt_number', 'like', '%' . $this->search . '%')
                                ->orWhereHas('customer', function ($c) {
                                    $c->where('full_name', 'like', '%' . $this->search . '%');
                                });
                        });
                });
            })
            ->with(['invoice', 'wallet.owner', 'receipt'])
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('billing::livewire.allocation-index', [
            'allocations' => $allocations,
        ]);
    }
}
