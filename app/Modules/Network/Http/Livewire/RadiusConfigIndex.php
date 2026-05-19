<?php

declare(strict_types=1);

namespace App\Modules\Network\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Modules\Network\Models\RadReply;
use App\Core\Tenant\TenantManager;

class RadiusConfigIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $tenantId = app(TenantManager::class)->getTenantId();

        $query = RadReply::with('subscription.customer')
            ->where('tenant_id', $tenantId)
            ->orderBy('id', 'desc');

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('username', 'like', "%{$this->search}%")
                  ->orWhere('attribute', 'like', "%{$this->search}%")
                  ->orWhere('value', 'like', "%{$this->search}%");
            });
        }

        return view('network::livewire.radius-config-index', [
            'replies' => $query->paginate(20)
        ]);
    }
}
