<?php

declare(strict_types=1);

namespace App\Modules\Billing\Http\Livewire;

use App\Core\Tenant\TenantManager;
use App\Modules\Billing\Models\Wallet;
use App\Modules\Billing\Models\LedgerEntry;
use Livewire\Component;
use Livewire\WithPagination;

class WalletIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $isLedgerModalOpen = false;
    public ?int $selectedWalletId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function showLedger(int $walletId): void
    {
        $this->selectedWalletId = $walletId;
        $this->isLedgerModalOpen = true;
    }

    public function closeLedgerModal(): void
    {
        $this->isLedgerModalOpen = false;
        $this->selectedWalletId = null;
    }

    public function getSelectedWalletProperty(): ?Wallet
    {
        if (!$this->selectedWalletId) {
            return null;
        }

        $tenantId = app(TenantManager::class)->getTenantId();
        return Wallet::where('tenant_id', $tenantId)
            ->with(['owner'])
            ->findOrFail($this->selectedWalletId);
    }

    public function getLedgerEntriesProperty()
    {
        if (!$this->selectedWalletId) {
            return collect();
        }

        $tenantId = app(TenantManager::class)->getTenantId();
        return LedgerEntry::where('tenant_id', $tenantId)
            ->where('wallet_id', $this->selectedWalletId)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function render()
    {
        $tenantId = app(TenantManager::class)->getTenantId();

        $wallets = Wallet::where('tenant_id', $tenantId)
            ->when($this->search, function ($query) {
                $query->where(function ($sub) {
                    $sub->where('currency', 'like', '%' . $this->search . '%')
                        ->orWhereHasMorph('owner', [\App\Modules\Customer\Models\Customer::class], function ($q) {
                            $q->where('full_name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->with(['owner'])
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('billing::livewire.wallet-index', [
            'wallets' => $wallets,
            'selectedWallet' => $this->selectedWallet,
            'ledgerEntries' => $this->ledgerEntries,
        ]);
    }
}
