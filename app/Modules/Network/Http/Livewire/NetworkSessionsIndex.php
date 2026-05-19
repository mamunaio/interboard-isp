<?php

declare(strict_types=1);

namespace App\Modules\Network\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Modules\Network\Models\RadAcct;
use App\Core\Tenant\TenantManager;

class NetworkSessionsIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = ''; // 'online' or 'offline'

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $tenantId = app(TenantManager::class)->getTenantId();

        $query = RadAcct::with('subscription.customer')
            ->where('tenant_id', $tenantId)
            ->orderBy('acctstarttime', 'desc');

        if ($this->statusFilter === 'online') {
            $query->whereNull('acctstoptime');
        } elseif ($this->statusFilter === 'offline') {
            $query->whereNotNull('acctstoptime');
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('username', 'like', "%{$this->search}%")
                  ->orWhere('nasipaddress', 'like', "%{$this->search}%")
                  ->orWhere('framedipaddress', 'like', "%{$this->search}%")
                  ->orWhere('callingstationid', 'like', "%{$this->search}%")
                  ->orWhereHas('subscription.customer', function ($cq) {
                      $cq->where('full_name', 'like', "%{$this->search}%");
                  });
            });
        }

        return view('network::livewire.network-sessions-index', [
            'sessions' => $query->paginate(20)
        ]);
    }
}
