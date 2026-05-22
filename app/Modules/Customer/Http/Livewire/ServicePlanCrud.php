<?php

declare(strict_types=1);

namespace App\Modules\Customer\Http\Livewire;

use App\Core\Tenant\TenantManager;
use App\Modules\Customer\Models\ServicePlan;
use App\Modules\Billing\Models\TaxRule;
use Livewire\Component;
use Livewire\WithPagination;

class ServicePlanCrud extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $tenantId = null;

    public function mount(): void
    {
        $this->tenantId = app(TenantManager::class)->getTenantId();
    }

    // Modal & Form States
    public bool $isCreateModalOpen = false;
    public bool $isEditModalOpen = false;
    public ?int $editingPlanId = null;

    // Form fields
    public string $plan_code = '';
    public string $name = '';
    public string $monthly_price = '0.0000';
    public string $billing_cycle = 'monthly';
    public string $download_speed = '';
    public string $upload_speed = '';
    public ?int $tax_rule_id = null;
    public bool $is_active = true;

    protected function rules(): array
    {
        $tenantId = $this->tenantId;
        $codeRule = 'required|string|max:50';

        if ($this->editingPlanId) {
            $codeRule .= '|unique:service_plans,plan_code,' . $this->editingPlanId . ',id,tenant_id,' . $tenantId;
        } else {
            $codeRule .= '|unique:service_plans,plan_code,NULL,id,tenant_id,' . $tenantId;
        }

        return [
            'plan_code' => $codeRule,
            'name' => 'required|string|max:255',
            'monthly_price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|string|max:30',
            'download_speed' => 'required|string|max:50',
            'upload_speed' => 'required|string|max:50',
            'tax_rule_id' => 'nullable|integer|exists:tax_rules,id',
            'is_active' => 'boolean',
        ];
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->isCreateModalOpen = true;
    }

    public function closeCreateModal(): void
    {
        $this->isCreateModalOpen = false;
    }

    public function resetForm(): void
    {
        $this->editingPlanId = null;
        $this->plan_code = '';
        $this->name = '';
        $this->monthly_price = '0.0000';
        $this->billing_cycle = 'monthly';
        $this->download_speed = '';
        $this->upload_speed = '';
        $this->tax_rule_id = null;
        $this->is_active = true;
        $this->resetValidation();
    }

    public function createPlan(): void
    {
        $this->validate();

        $tenantId = $this->tenantId;

        ServicePlan::create([
            'tenant_id' => $tenantId,
            'plan_code' => $this->plan_code,
            'name' => $this->name,
            'monthly_price' => $this->monthly_price,
            'billing_cycle' => $this->billing_cycle,
            'download_speed' => $this->download_speed,
            'upload_speed' => $this->upload_speed,
            'tax_rule_id' => $this->tax_rule_id ?: null,
            'is_active' => $this->is_active,
        ]);

        $this->isCreateModalOpen = false;
        $this->resetForm();
        session()->flash('message', 'Service plan created successfully.');
    }

    public function editPlan(int $id): void
    {
        $this->resetForm();
        $tenantId = $this->tenantId;

        $plan = ServicePlan::where('tenant_id', $tenantId)->findOrFail($id);

        $this->editingPlanId = $plan->id;
        $this->plan_code = $plan->plan_code;
        $this->name = $plan->name;
        $this->monthly_price = (string)$plan->monthly_price;
        $this->billing_cycle = $plan->billing_cycle;
        $this->download_speed = $plan->download_speed;
        $this->upload_speed = $plan->upload_speed;
        $this->tax_rule_id = $plan->tax_rule_id;
        $this->is_active = $plan->is_active;

        $this->isEditModalOpen = true;
    }

    public function updatePlan(): void
    {
        $this->validate();

        $tenantId = $this->tenantId;
        $plan = ServicePlan::where('tenant_id', $tenantId)->findOrFail($this->editingPlanId);

        $plan->update([
            'plan_code' => $this->plan_code,
            'name' => $this->name,
            'monthly_price' => $this->monthly_price,
            'billing_cycle' => $this->billing_cycle,
            'download_speed' => $this->download_speed,
            'upload_speed' => $this->upload_speed,
            'tax_rule_id' => $this->tax_rule_id ?: null,
            'is_active' => $this->is_active,
        ]);

        $this->isEditModalOpen = false;
        $this->resetForm();
        session()->flash('message', 'Service plan updated successfully.');
    }

    public function toggleActive(int $id): void
    {
        $tenantId = $this->tenantId;
        $plan = ServicePlan::where('tenant_id', $tenantId)->findOrFail($id);
        $plan->is_active = !$plan->is_active;
        $plan->save();

        session()->flash('message', 'Service plan status updated successfully.');
    }

    public function render()
    {
        $tenantId = $this->tenantId;

        $plans = ServicePlan::where('tenant_id', $tenantId)
            ->when($this->search, function ($query) {
                $query->where(function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('plan_code', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('id', 'desc')
            ->paginate(10);

        $taxRules = TaxRule::where('tenant_id', $tenantId)
            ->where('active', true)
            ->get();

        return view('customer::livewire.service-plan-crud', [
            'plans' => $plans,
            'taxRules' => $taxRules,
        ]);
    }
}
