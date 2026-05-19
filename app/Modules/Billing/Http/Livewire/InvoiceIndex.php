<?php

declare(strict_types=1);

namespace App\Modules\Billing\Http\Livewire;

use App\Core\Tenant\TenantManager;
use App\Modules\Billing\Models\Invoice;
use Livewire\Component;
use Livewire\WithPagination;

class InvoiceIndex extends Component
{
    use WithPagination;

    public string $search = '';
    
    // Detailed modal state
    public bool $isDetailModalOpen = false;
    public ?int $selectedInvoiceId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function showInvoice(int $id): void
    {
        $this->selectedInvoiceId = $id;
        $this->isDetailModalOpen = true;
    }

    public function closeDetailModal(): void
    {
        $this->isDetailModalOpen = false;
        $this->selectedInvoiceId = null;
    }

    public function getSelectedInvoiceProperty(): ?Invoice
    {
        if (!$this->selectedInvoiceId) {
            return null;
        }

        $tenantId = app(TenantManager::class)->getTenantId();
        return Invoice::where('tenant_id', $tenantId)
            ->with(['customer', 'lineItems', 'allocations.receipt'])
            ->findOrFail($this->selectedInvoiceId);
    }

    public function render()
    {
        $tenantId = app(TenantManager::class)->getTenantId();

        $invoices = Invoice::where('tenant_id', $tenantId)
            ->when($this->search, function ($query) {
                $query->where(function ($sub) {
                    $sub->where('invoice_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('customer', function ($cust) {
                            $cust->where('full_name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->with('customer')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('billing::livewire.invoice-index', [
            'invoices' => $invoices,
            'selectedInvoice' => $this->selectedInvoice,
        ]);
    }
}
