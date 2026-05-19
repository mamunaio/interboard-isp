<?php

declare(strict_types=1);

namespace App\Modules\Billing\Http\Livewire;

use App\Core\Tenant\TenantManager;
use App\Modules\Billing\Models\GatewayPaymentAttempt;
use Livewire\Component;
use Livewire\WithPagination;

class GatewayAttemptsIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $isPayloadModalOpen = false;
    public ?int $selectedAttemptId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function showPayload(int $attemptId): void
    {
        $this->selectedAttemptId = $attemptId;
        $this->isPayloadModalOpen = true;
    }

    public function closePayloadModal(): void
    {
        $this->isPayloadModalOpen = false;
        $this->selectedAttemptId = null;
    }

    public function getSelectedAttemptProperty(): ?GatewayPaymentAttempt
    {
        if (!$this->selectedAttemptId) {
            return null;
        }

        $tenantId = app(TenantManager::class)->getTenantId();
        return GatewayPaymentAttempt::where('tenant_id', $tenantId)
            ->with(['customer'])
            ->findOrFail($this->selectedAttemptId);
    }

    public function render()
    {
        $tenantId = app(TenantManager::class)->getTenantId();

        $attempts = GatewayPaymentAttempt::where('tenant_id', $tenantId)
            ->when($this->search, function ($query) {
                $query->where(function ($sub) {
                    $sub->where('gateway', 'like', '%' . $this->search . '%')
                        ->orWhere('attempt_reference', 'like', '%' . $this->search . '%')
                        ->orWhere('gateway_transaction_id', 'like', '%' . $this->search . '%')
                        ->orWhereHas('customer', function ($q) {
                            $q->where('full_name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->with(['customer'])
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('billing::livewire.gateway-attempts-index', [
            'attempts' => $attempts,
            'selectedAttempt' => $this->selectedAttempt,
        ]);
    }
}
