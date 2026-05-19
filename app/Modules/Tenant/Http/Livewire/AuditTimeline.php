<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Http\Livewire;

use App\Modules\Tenant\Models\AuditLog;
use Livewire\Component;
use Livewire\WithPagination;

class AuditTimeline extends Component
{
    use WithPagination;

    public ?string $search = '';
    public ?string $modelFilter = '';
    public ?string $eventFilter = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'modelFilter' => ['except' => ''],
        'eventFilter' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingModelFilter(): void
    {
        $this->resetPage();
    }

    public function updatingEventFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = AuditLog::orderBy('created_at', 'desc');

        if (!empty($this->modelFilter)) {
            $query->where('auditable_type', 'like', '%' . $this->modelFilter . '%');
        }

        if (!empty($this->eventFilter)) {
            $query->where('event', $this->eventFilter);
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('auditable_id', 'like', '%' . $this->search . '%')
                  ->orWhere('ip_address', 'like', '%' . $this->search . '%')
                  ->orWhere('user_agent', 'like', '%' . $this->search . '%');
            });
        }

        $logs = $query->paginate(15);

        return view('tenant::livewire.audit-timeline', [
            'logs' => $logs,
        ]);
    }
}
