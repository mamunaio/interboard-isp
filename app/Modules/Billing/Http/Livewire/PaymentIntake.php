<?php

declare(strict_types=1);

namespace App\Modules\Billing\Http\Livewire;

use App\Core\Tenant\TenantManager;
use App\Modules\Customer\Models\Customer;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\Wallet;
use App\Modules\Billing\Enums\PaymentMethod;
use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Billing\Services\PaymentReceiptService;
use App\Modules\Billing\Services\WalletService;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PaymentIntake extends Component
{
    public ?int $selectedCustomerId = null;
    public string $amount = '';
    public string $paymentMethod = 'cash';
    public string $reference = '';
    public string $idempotencyKey = '';
    public string $notes = '';
    public bool $autoAllocate = true;

    protected function rules(): array
    {
        return [
            'selectedCustomerId' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0.01',
            'paymentMethod' => 'required|string',
            'reference' => 'nullable|string|max:100',
            'idempotencyKey' => 'required|string|max:100',
            'notes' => 'nullable|string|max:500',
            'autoAllocate' => 'boolean',
        ];
    }

    public function mount(): void
    {
        $this->regenerateIdempotencyKey();
    }

    public function regenerateIdempotencyKey(): void
    {
        $this->idempotencyKey = (string)Str::uuid();
    }

    public function getCustomersProperty()
    {
        $tenantId = app(TenantManager::class)->getTenantId();
        return Customer::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderBy('full_name', 'asc')
            ->get();
    }

    public function getOutstandingInvoicesProperty()
    {
        if (!$this->selectedCustomerId) {
            return collect();
        }

        $tenantId = app(TenantManager::class)->getTenantId();
        return Invoice::where('tenant_id', $tenantId)
            ->where('customer_id', $this->selectedCustomerId)
            ->whereIn('status', [InvoiceStatus::ISSUED->value, InvoiceStatus::PARTIALLY_PAID->value])
            ->orderBy('id', 'asc')
            ->get();
    }

    public function submitPayment(): void
    {
        $this->validate();

        $tenantId = app(TenantManager::class)->getTenantId();

        try {
            DB::transaction(function () use ($tenantId) {
                // Ensure wallet exists for this customer/currency (BDT)
                $walletExists = Wallet::withoutGlobalScopes()
                    ->where('tenant_id', $tenantId)
                    ->where('owner_type', Customer::class)
                    ->where('owner_id', $this->selectedCustomerId)
                    ->where('currency', 'BDT')
                    ->exists();

                if (!$walletExists) {
                    $walletService = app(WalletService::class);
                    $walletService->createWallet($tenantId, Customer::class, (int)$this->selectedCustomerId, 'BDT');
                }

                $receiptService = app(PaymentReceiptService::class);
                $receiptService->createReceipt($tenantId, [
                    'customer_id' => $this->selectedCustomerId,
                    'amount' => $this->amount,
                    'currency' => 'BDT',
                    'payment_method' => PaymentMethod::from($this->paymentMethod),
                    'reference' => $this->reference ?: $this->idempotencyKey,
                    'notes' => $this->notes ?: null,
                    'auto_allocate' => $this->autoAllocate,
                    'idempotency_key' => $this->idempotencyKey,
                ]);
            });

            session()->flash('message', 'Payment receipt processed and posted successfully.');
            $this->reset(['selectedCustomerId', 'amount', 'reference', 'notes']);
            $this->regenerateIdempotencyKey();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('billing::livewire.payment-intake', [
            'customers' => $this->customers,
            'outstandingInvoices' => $this->outstandingInvoices,
        ]);
    }
}
