<?php

declare(strict_types=1);

namespace App\Modules\Customer\Http\Livewire;

use App\Core\Tenant\TenantManager;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\CustomerAddress;
use App\Modules\Customer\Enums\CustomerStatus;
use App\Modules\Customer\Enums\CustomerType;
use App\Modules\Customer\Enums\AddressType;
use App\Modules\Support\Services\WorkOrderService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class CustomerIndex extends Component
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
    public ?int $editingCustomerId = null;

    // Form fields
    public string $customer_code = '';
    public string $full_name = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $national_id = '';
    public string $status = 'active';
    public string $type = 'residential';
    public string $company_name = '';

    protected function rules(): array
    {
        $tenantId = $this->tenantId;
        
        $customerCodeRule = 'required|string|max:50';
        $phoneRule = 'required|string|max:50';

        if ($this->editingCustomerId) {
            $customerCodeRule .= '|unique:customers,customer_code,' . $this->editingCustomerId . ',id,tenant_id,' . $tenantId;
            $phoneRule .= '|unique:customers,phone,' . $this->editingCustomerId . ',id,tenant_id,' . $tenantId;
        } else {
            $customerCodeRule .= '|unique:customers,customer_code,NULL,id,tenant_id,' . $tenantId;
            $phoneRule .= '|unique:customers,phone,NULL,id,tenant_id,' . $tenantId;
        }

        return [
            'customer_code' => $customerCodeRule,
            'full_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => $phoneRule,
            'address' => 'required|string|max:500',
            'national_id' => 'nullable|string|max:50',
            'status' => 'required|string',
            'type' => 'required|string',
            'company_name' => 'nullable|string|max:255',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        
        // Pre-generate a code if sequences or random
        $tenantId = $this->tenantId;
        $count = Customer::where('tenant_id', $tenantId)->count() + 1;
        $this->customer_code = 'CUST-' . str_pad((string)$count, 5, '0', STR_PAD_LEFT);
        
        $this->isCreateModalOpen = true;
    }

    public function closeCreateModal(): void
    {
        $this->isCreateModalOpen = false;
    }

    public function resetForm(): void
    {
        $this->editingCustomerId = null;
        $this->customer_code = '';
        $this->full_name = '';
        $this->email = '';
        $this->phone = '';
        $this->address = '';
        $this->national_id = '';
        $this->status = 'active';
        $this->type = 'residential';
        $this->company_name = '';
        $this->resetValidation();
    }

    public function createCustomer(): void
    {
        $this->validate();

        $tenantId = $this->tenantId;

        DB::transaction(function () use ($tenantId) {
            $customer = Customer::create([
                'tenant_id' => $tenantId,
                'customer_code' => $this->customer_code,
                'full_name' => $this->full_name,
                'email' => $this->email ?: null,
                'phone' => $this->phone,
                'national_id' => $this->national_id ?: null,
                'status' => CustomerStatus::from($this->status),
                'type' => CustomerType::from($this->type),
                'company_name' => $this->company_name ?: null,
            ]);

            // Save address
            CustomerAddress::create([
                'tenant_id' => $tenantId,
                'customer_id' => $customer->id,
                'type' => AddressType::BOTH,
                'label' => 'Primary',
                'address_line_1' => $this->address,
                'city' => 'Dhaka',
                'country' => 'Bangladesh',
                'is_default_billing' => true,
                'is_default_installation' => true,
            ]);

            // Auto-generate Installation Work Order
            app(WorkOrderService::class)->createInstallationOrder($customer);
        });

        $this->isCreateModalOpen = false;
        $this->resetForm();
        session()->flash('message', 'Customer created successfully.');
    }

    public function editCustomer(int $id): void
    {
        $this->resetForm();
        $tenantId = $this->tenantId;
        
        $customer = Customer::where('tenant_id', $tenantId)->findOrFail($id);
        
        $this->editingCustomerId = $customer->id;
        $this->customer_code = $customer->customer_code;
        $this->full_name = $customer->full_name;
        $this->email = $customer->email ?? '';
        $this->phone = $customer->phone;
        $this->national_id = $customer->national_id ?? '';
        $this->status = $customer->status->value;
        $this->type = $customer->type->value;
        $this->company_name = $customer->company_name ?? '';

        $primaryAddress = $customer->addresses()->where('label', 'Primary')->first();
        $this->address = $primaryAddress ? $primaryAddress->address_line_1 : '';

        $this->isEditModalOpen = true;
    }

    public function updateCustomer(): void
    {
        $this->validate();

        $tenantId = $this->tenantId;

        DB::transaction(function () use ($tenantId) {
            $customer = Customer::where('tenant_id', $tenantId)->findOrFail($this->editingCustomerId);

            $customer->update([
                'customer_code' => $this->customer_code,
                'full_name' => $this->full_name,
                'email' => $this->email ?: null,
                'phone' => $this->phone,
                'national_id' => $this->national_id ?: null,
                'status' => CustomerStatus::from($this->status),
                'type' => CustomerType::from($this->type),
                'company_name' => $this->company_name ?: null,
            ]);

            // Update primary address
            $addressRecord = $customer->addresses()->where('label', 'Primary')->first();
            if ($addressRecord) {
                $addressRecord->update([
                    'address_line_1' => $this->address,
                ]);
            } else {
                CustomerAddress::create([
                    'tenant_id' => $tenantId,
                    'customer_id' => $customer->id,
                    'type' => AddressType::BOTH,
                    'label' => 'Primary',
                    'address_line_1' => $this->address,
                    'city' => 'Dhaka',
                    'country' => 'Bangladesh',
                    'is_default_billing' => true,
                    'is_default_installation' => true,
                ]);
            }
        });

        $this->isEditModalOpen = false;
        $this->resetForm();
        session()->flash('message', 'Customer updated successfully.');
    }

    public function render()
    {
        $tenantId = $this->tenantId;

        $customers = Customer::where('tenant_id', $tenantId)
            ->when($this->search, function ($query) {
                $query->where(function ($sub) {
                    $sub->where('full_name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('customer_code', 'like', '%' . $this->search . '%')
                        ->orWhere('phone', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('customer::livewire.customer-index', [
            'customers' => $customers,
        ]);
    }
}
