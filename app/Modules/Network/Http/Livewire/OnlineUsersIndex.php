<?php

declare(strict_types=1);

namespace App\Modules\Network\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Modules\Network\Models\RadAcct;
use App\Modules\Network\Services\DisconnectActionService;
use App\Core\Tenant\TenantManager;

class OnlineUsersIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function disconnectUser(string $username): void
    {
        $tenantId = app(TenantManager::class)->getTenantId();
        
        $success = app(DisconnectActionService::class)->disconnectUser($username, $tenantId);

        if ($success) {
            session()->flash('message', "User {$username} disconnected successfully.");
        } else {
            session()->flash('error', "Failed to disconnect user {$username}.");
        }
    }

    public function render()
    {
        $tenantId = app(TenantManager::class)->getTenantId();

        $query = RadAcct::with('subscription.customer')
            ->where('tenant_id', $tenantId)
            ->whereNull('acctstoptime')
            ->orderBy('acctstarttime', 'desc');

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('username', 'like', "%{$this->search}%")
                  ->orWhere('nasipaddress', 'like', "%{$this->search}%")
                  ->orWhere('framedipaddress', 'like', "%{$this->search}%")
                  ->orWhereHas('subscription.customer', function ($cq) {
                      $cq->where('full_name', 'like', "%{$this->search}%");
                  });
            });
        }

        return view('network::livewire.online-users-index', [
            'sessions' => $query->paginate(15)
        ]);
    }
}
