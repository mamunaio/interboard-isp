<?php

declare(strict_types=1);

namespace App\Modules\Billing\Http\Livewire;

use App\Core\Tenant\TenantManager;
use App\Modules\Billing\Services\InvoiceService;
use App\Modules\Customer\Models\Customer;
use App\Modules\Billing\Enums\InvoiceType;
use Livewire\Component;
use Illuminate\Validation\ValidationException;

class InvoiceCreate extends Component
{
    public $customerId = '';
    public string $issueDate = '';
    public string $dueDate = '';
    public string $taxTotal = '0.00';
    public string $discountTotal = '0.00';
    public ?string $notes = null;
    
    public array $lineItems = [];

    protected $rules = [
        'customerId' => 'required|integer',
        'issueDate' => 'required|date',
        'dueDate' => 'required|date|after_or_equal:issueDate',
        'taxTotal' => 'required|numeric|min:0',
        'discountTotal' => 'required|numeric|min:0',
        'notes' => 'nullable|string|max:1000',
        'lineItems' => 'required|array|min:1',
        'lineItems.*.description' => 'required|string|max:255',
        'lineItems.*.quantity' => 'required|numeric|min:0.01',
        'lineItems.*.unit_price' => 'required|numeric|min:0',
    ];

    public function mount(): void
    {
        $this->issueDate = now()->format('Y-m-d');
        $this->dueDate = now()->addDays(7)->format('Y-m-d');
        $this->addLineItem();
    }

    public function addLineItem(): void
    {
        $this->lineItems[] = [
            'description' => '',
            'quantity' => '1',
            'unit_price' => '0.00',
            'line_type' => 'service',
        ];
    }

    public function removeLineItem(int $index): void
    {
        unset($this->lineItems[$index]);
        $this->lineItems = array_values($this->lineItems); // Re-index array
        
        if (empty($this->lineItems)) {
            $this->addLineItem();
        }
    }

    public function getSubtotalProperty(): string
    {
        $subtotal = 0;
        foreach ($this->lineItems as $item) {
            $qty = (float)($item['quantity'] ?? 0);
            $price = (float)($item['unit_price'] ?? 0);
            $subtotal += ($qty * $price);
        }
        return number_format($subtotal, 2, '.', '');
    }

    public function getGrandTotalProperty(): string
    {
        $subtotal = (float)$this->subtotal;
        $tax = (float)($this->taxTotal ?: 0);
        $discount = (float)($this->discountTotal ?: 0);
        
        $grandTotal = $subtotal + $tax - $discount;
        return number_format($grandTotal, 2, '.', '');
    }

    public function save(InvoiceService $invoiceService)
    {
        $this->validate();

        $tenantId = app(TenantManager::class)->getTenantId();

        try {
            $data = [
                'issue_date' => $this->issueDate,
                'due_date' => $this->dueDate,
                'tax_total' => $this->taxTotal,
                'discount_total' => $this->discountTotal,
                'notes' => $this->notes,
                'invoice_type' => InvoiceType::OTHER->value, 
            ];

            $invoiceService->createInvoice(
                $tenantId,
                (int)$this->customerId,
                $data,
                $this->lineItems
            );

            session()->flash('success', 'Draft invoice created successfully.');
            return redirect()->route('tenant.invoices.index');
        } catch (ValidationException $e) {
            throw $e; // Re-throw Livewire/Laravel validation exceptions to show in UI
        } catch (\Exception $e) {
            $this->addError('general', 'Failed to create invoice: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $tenantId = app(TenantManager::class)->getTenantId();
        
        $customers = Customer::where('tenant_id', $tenantId)
            ->orderBy('first_name')
            ->get();

        return view('billing::livewire.invoice-create', [
            'customers' => $customers,
        ]);
    }
}
