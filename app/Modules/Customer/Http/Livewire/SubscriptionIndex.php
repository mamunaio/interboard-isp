<?php

declare(strict_types=1);

namespace App\Modules\Customer\Http\Livewire;

use App\Core\Tenant\TenantManager;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use Livewire\Component;
use Livewire\WithPagination;

class SubscriptionIndex extends Component
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

        $subscriptions = CustomerServiceSubscription::where('tenant_id', $tenantId)
            ->when($this->search, function ($query) {
                $query->where(function ($sub) {
                    $sub->where('subscription_code', 'like', '%' . $this->search . '%')
                        ->orWhereHas('customer', function ($cust) {
                            $cust->where('full_name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->with(['customer', 'servicePlan', 'servicePackage'])
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('customer::livewire.subscription-index', [
            'subscriptions' => $subscriptions,
        ]);
    }
}
