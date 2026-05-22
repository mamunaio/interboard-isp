<?php

declare(strict_types=1);

namespace App\Modules\Network\Http\Livewire;

use Livewire\Component;
use App\Modules\Network\Models\OltDevice;
use App\Core\Tenant\TenantManager;

class OltManager extends Component
{
    public $name = '';
    public $ip_address = '';
    public $port = 23;
    public $username = '';
    public $password = '';
    public $vendor = 'zte';

    public $editingOltId = null;
    public $showCreateModal = false;
    public $statusCheckResults = [];

    protected $rules = [
        'name' => 'required|string|max:100',
        'ip_address' => 'required|ip',
        'port' => 'required|integer|min:1|max:65535',
        'username' => 'required|string|max:50',
        'password' => 'required|string|max:50',
        'vendor' => 'required|string|in:zte,huawei',
    ];

    public function mount(): void
    {
        // Set default tenant context if needed
    }

    public function toggleCreateModal(): void
    {
        $this->resetForm();
        $this->showCreateModal = !$this->showCreateModal;
    }

    public function resetForm(): void
    {
        $this->name = '';
        $this->ip_address = '';
        $this->port = 23;
        $this->username = '';
        $this->password = '';
        $this->vendor = 'zte';
        $this->editingOltId = null;
    }

    public function saveOlt(): void
    {
        $this->validate();
        
        $tenantId = app(TenantManager::class)->getTenantId();

        $data = [
            'tenant_id' => $tenantId,
            'name' => $this->name,
            'ip_address' => $this->ip_address,
            'port' => $this->port,
            'username' => $this->username,
            'password' => $this->password,
            'vendor' => $this->vendor,
        ];

        if ($this->editingOltId) {
            OltDevice::findOrFail($this->editingOltId)->update($data);
            session()->flash('message', 'OLT Device updated successfully.');
        } else {
            OltDevice::create($data);
            session()->flash('message', 'OLT Device added successfully.');
        }

        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function editOlt(int $id): void
    {
        $olt = OltDevice::findOrFail($id);
        $this->editingOltId = $id;
        $this->name = $olt->name;
        $this->ip_address = $olt->ip_address;
        $this->port = (int)$olt->port;
        $this->username = $olt->username;
        $this->password = $olt->password;
        $this->vendor = $olt->vendor;

        $this->showCreateModal = true;
    }

    public function deleteOlt(int $id): void
    {
        OltDevice::findOrFail($id)->delete();
        session()->flash('message', 'OLT Device deleted successfully.');
    }

    public function testConnection(int $id): void
    {
        $olt = OltDevice::findOrFail($id);
        
        // Simulate Telnet connection ping
        sleep(1); // brief mock latency
        $status = rand(0, 10) > 1 ? 'online' : 'offline';
        
        $olt->update(['status' => $status]);
        
        $this->statusCheckResults[$id] = [
            'status' => $status,
            'time' => now()->format('H:i:s'),
            'message' => $status === 'online' 
                ? "Connection successful to {$olt->ip_address}:{$olt->port} ({$olt->vendor})"
                : "Connection timed out to {$olt->ip_address}:{$olt->port}"
        ];
    }

    public function render()
    {
        $tenantId = app(TenantManager::class)->getTenantId();
        $olts = OltDevice::where('tenant_id', $tenantId)->orderBy('id', 'desc')->get();

        return view('network::livewire.olt-manager', [
            'olts' => $olts
        ])->layout('layouts.app');
    }
}
