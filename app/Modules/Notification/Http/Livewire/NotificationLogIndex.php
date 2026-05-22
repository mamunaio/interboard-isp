<?php

declare(strict_types=1);

namespace App\Modules\Notification\Http\Livewire;

use App\Core\Tenant\TenantManager;
use App\Modules\Notification\Models\NotificationLog;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationLogIndex extends Component
{
    use WithPagination;

    public ?int $tenantId = null;
    public string $search = '';
    public string $filterChannel = '';
    public string $filterStatus = '';

    public function mount(): void
    {
        $this->tenantId = app(TenantManager::class)->getTenantId();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = NotificationLog::with('customer')->where('tenant_id', $this->tenantId);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('recipient', 'like', "%{$this->search}%")
                  ->orWhereHas('customer', function($c) {
                      $c->where('full_name', 'like', "%{$this->search}%");
                  });
            });
        }

        if ($this->filterChannel) {
            $query->where('channel', $this->filterChannel);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('notification::livewire.log-index', [
            'logs' => $logs,
        ]);
    }
}
