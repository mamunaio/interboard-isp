<?php

declare(strict_types=1);

namespace App\Modules\CRM\Http\Livewire;

use App\Core\Tenant\TenantManager;
use App\Modules\CRM\Enums\LeadStatus;
use App\Modules\CRM\Models\Lead;
use App\Modules\CRM\Services\LeadService;
use App\Modules\Customer\Models\ServicePlan;
use Livewire\Component;
use Livewire\WithPagination;

class LeadIndex extends Component
{
    use WithPagination;

    public ?int $tenantId = null;

    public string $search = '';
    public string $filterStatus = '';

    // Create Modal
    public bool $isCreateModalOpen = false;
    public string $firstName = '';
    public string $lastName = '';
    public string $phone = '';
    public string $email = '';
    public string $address = '';
    public ?int $expectedPlanId = null;
    public string $notes = '';

    public function mount(): void
    {
        $this->tenantId = app(TenantManager::class)->getTenantId();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->firstName = '';
        $this->lastName = '';
        $this->phone = '';
        $this->email = '';
        $this->address = '';
        $this->expectedPlanId = null;
        $this->notes = '';
        $this->isCreateModalOpen = true;
    }

    public function closeCreateModal(): void
    {
        $this->isCreateModalOpen = false;
    }

    public function createLead(LeadService $leadService): void
    {
        $this->validate([
            'firstName' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'expectedPlanId' => 'nullable|exists:service_plans,id',
        ]);

        $leadService->createLead([
            'tenant_id' => $this->tenantId,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'expected_plan_id' => $this->expectedPlanId,
            'notes' => $this->notes,
            'status' => LeadStatus::NEW,
            'assigned_to_id' => auth()->id() ?? auth('staff')->id(),
        ]);

        $this->closeCreateModal();
        session()->flash('message', 'New lead created successfully.');
    }

    public function render()
    {
        $query = Lead::with(['assignee', 'expectedPlan'])->where('tenant_id', $this->tenantId);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('first_name', 'like', '%' . $this->search . '%')
                  ->orWhere('last_name', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $leads = $query->orderBy('created_at', 'desc')->paginate(15);
        $plans = ServicePlan::where('tenant_id', $this->tenantId)->where('is_active', true)->get();

        return view('c_r_m::livewire.lead-index', [
            'leads' => $leads,
            'plans' => $plans,
            'statuses' => LeadStatus::cases(),
        ]);
    }
}
