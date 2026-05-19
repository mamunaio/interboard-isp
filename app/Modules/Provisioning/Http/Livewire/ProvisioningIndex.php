<?php

declare(strict_types=1);

namespace App\Modules\Provisioning\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use App\Modules\Provisioning\Models\ServiceProvisioningEvent;
use App\Modules\Provisioning\Enums\ProvisioningStatus;
use App\Modules\Provisioning\Jobs\ProcessProvisioningJob;
use App\Core\Tenant\TenantManager;

class ProvisioningIndex extends Component
{
    use WithPagination;

    public string $statusFilter = '';
    public string $search = '';

    public ?ServiceProvisioningEvent $selectedEvent = null;
    public bool $isModalOpen = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    #[On('echo:tenant.{tenantId}.provisioning,provisioning.status.updated')]
    public function onProvisioningUpdate(): void
    {
        // Re-render the component automatically when Reverb broadcasts an update
        $this->resetPage();
    }

    public function viewDetails(int $eventId): void
    {
        $this->selectedEvent = ServiceProvisioningEvent::with(['customer', 'subscription'])->findOrFail($eventId);
        $this->isModalOpen = true;
    }

    public function retryAction(int $eventId): void
    {
        $event = ServiceProvisioningEvent::findOrFail($eventId);

        if ($event->status === ProvisioningStatus::FAILED || (is_string($event->status) && $event->status === 'failed')) {
            $event->update(['status' => ProvisioningStatus::PENDING]);
            ProcessProvisioningJob::dispatch($event->id);
            
            session()->flash('message', "Retry dispatched for Event #{$eventId}.");
        }
    }

    public function getTenantIdProperty(): int
    {
        return app(TenantManager::class)->getTenantId();
    }

    public function render()
    {
        $query = ServiceProvisioningEvent::with(['customer', 'subscription.servicePlan'])
            ->orderBy('created_at', 'desc');

        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        if (!empty($this->search)) {
            $query->whereHas('customer', function ($q) {
                $q->where('full_name', 'like', "%{$this->search}%")
                  ->orWhere('customer_code', 'like', "%{$this->search}%");
            })->orWhereHas('subscription', function ($q) {
                $q->where('subscription_code', 'like', "%{$this->search}%");
            });
        }

        return view('provisioning::livewire.provisioning-index', [
            'events' => $query->paginate(15)
        ]);
    }
}
