<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Http\Livewire;

use App\Modules\Tenant\Models\Tenant;
use App\Modules\Tenant\Models\TenantDomain;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;

class TenantManager extends Component
{
    use WithPagination;

    public string $search = '';

    // Create Modal
    public bool $isCreateModalOpen = false;
    public string $tenantName = '';
    public string $tenantDomain = '';
    public string $adminEmail = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->tenantName = '';
        $this->tenantDomain = '';
        $this->adminEmail = '';
        $this->isCreateModalOpen = true;
    }

    public function closeCreateModal(): void
    {
        $this->isCreateModalOpen = false;
    }

    public function createTenant(): void
    {
        $this->validate([
            'tenantName' => 'required|string|max:255',
            'tenantDomain' => 'required|string|max:255|unique:tenant_domains,domain',
            'adminEmail' => 'required|email',
        ]);

        \DB::transaction(function () {
            $tenant = Tenant::create([
                'name' => $this->tenantName,
                'slug' => Str::slug($this->tenantName),
                'status' => 'active',
            ]);

            TenantDomain::create([
                'tenant_id' => $tenant->id,
                'domain' => $this->tenantDomain,
            ]);

            // Create the first staff user for this tenant
            \App\Modules\Auth\Models\StaffUser::create([
                'tenant_id' => $tenant->id,
                'name' => 'Admin',
                'email' => $this->adminEmail,
                'password' => bcrypt('password'), // Default password, should be changed on first login
                'role' => 'admin',
            ]);
        });

        $this->closeCreateModal();
        session()->flash('message', "Tenant '{$this->tenantName}' created successfully with domain '{$this->tenantDomain}'.");
    }

    public function toggleStatus(int $tenantId): void
    {
        $tenant = Tenant::findOrFail($tenantId);
        $newStatus = $tenant->status === 'active' ? 'suspended' : 'active';
        $tenant->update(['status' => $newStatus]);

        session()->flash('message', "Tenant '{$tenant->name}' is now {$newStatus}.");
    }

    public function render()
    {
        $query = Tenant::with('domains');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('slug', 'like', '%' . $this->search . '%')
                  ->orWhereHas('domains', function ($dq) {
                      $dq->where('domain', 'like', '%' . $this->search . '%');
                  });
            });
        }

        $tenants = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('tenant::livewire.tenant-manager', [
            'tenants' => $tenants,
        ])->layout('layouts.saas');
    }
}
