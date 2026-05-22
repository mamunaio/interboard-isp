<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Livewire;

use App\Core\Tenant\TenantManager;
use App\Models\User;
use App\Modules\Support\Enums\WorkOrderPriority;
use App\Modules\Support\Enums\WorkOrderStatus;
use App\Modules\Support\Enums\WorkOrderType;
use App\Modules\Support\Models\WorkOrder;
use App\Modules\Support\Services\WorkOrderService;
use Livewire\Component;
use Livewire\WithPagination;

class WorkOrderIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';
    public string $filterType = '';

    public ?int $tenantId = null;

    // Modal States
    public bool $isEditModalOpen = false;
    public ?int $editingWorkOrderId = null;

    // Edit Form
    public string $status = '';
    public ?int $assigned_to = null;
    public string $resolution_notes = '';

    public function mount(): void
    {
        $this->tenantId = app(TenantManager::class)->getTenantId();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function editWorkOrder(int $id): void
    {
        $workOrder = WorkOrder::where('tenant_id', $this->tenantId)->findOrFail($id);

        $this->editingWorkOrderId = $workOrder->id;
        $this->status = $workOrder->status->value;
        $this->assigned_to = $workOrder->assigned_to;
        $this->resolution_notes = $workOrder->resolution_notes ?? '';

        $this->isEditModalOpen = true;
    }

    public function closeEditModal(): void
    {
        $this->isEditModalOpen = false;
        $this->reset(['editingWorkOrderId', 'status', 'assigned_to', 'resolution_notes']);
    }

    public function updateWorkOrder(): void
    {
        $this->validate([
            'status' => 'required|string',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'resolution_notes' => 'nullable|string',
        ]);

        $workOrder = WorkOrder::where('tenant_id', $this->tenantId)->findOrFail($this->editingWorkOrderId);

        // Update basic fields
        $workOrder->assigned_to = $this->assigned_to ?: null;
        $workOrder->save();

        // Use service to update status securely
        $newStatus = WorkOrderStatus::from($this->status);
        app(WorkOrderService::class)->updateStatus($workOrder, $newStatus, $this->resolution_notes);

        $this->closeEditModal();
        session()->flash('message', 'Work Order updated successfully.');
    }

    public function render()
    {
        $query = WorkOrder::with(['customer', 'assignee'])
            ->where('tenant_id', $this->tenantId);

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('customer', function ($cq) {
                    $cq->where('full_name', 'like', '%' . $this->search . '%')
                      ->orWhere('customer_code', 'like', '%' . $this->search . '%');
                });
            });
        }

        $workOrders = $query->orderBy('created_at', 'desc')->paginate(15);
        $technicians = User::all(); // Assuming all users can be technicians for now. Later can filter by role.

        return view('support::livewire.work-order-index', [
            'workOrders' => $workOrders,
            'technicians' => $technicians,
            'statuses' => WorkOrderStatus::cases(),
            'types' => WorkOrderType::cases(),
            'priorities' => WorkOrderPriority::cases(),
        ]);
    }
}
