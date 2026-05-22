<?php

declare(strict_types=1);

namespace App\Modules\Network\Http\Livewire;

use Livewire\Component;
use App\Modules\Network\Models\OltDevice;
use App\Modules\Network\Models\OnuDevice;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Network\Services\OltProvisioningService;
use App\Core\Tenant\TenantManager;

class OnuProvisioner extends Component
{
    public $selectedOltId = null;
    public $discoveredOnus = [];
    public $activeOnus = [];

    // Form fields for provisioning
    public $selectedSerialNumber = '';
    public $selectedGponPort = '';
    public $subscriptionId = '';
    public $lineProfile = 'GPON-LINE-DEFAULT';
    public $serviceProfile = 'GPON-SERV-DEFAULT';
    
    public $showProvisionModal = false;
    public $activeTab = 'discovery'; // discovery, active_onus
    public $cliLogs = '';
    
    // Optical power cache
    public $opticalReadings = [];

    protected $rules = [
        'selectedSerialNumber' => 'required|string',
        'selectedGponPort' => 'required|string',
        'subscriptionId' => 'required|exists:customer_service_subscriptions,id',
        'lineProfile' => 'required|string',
        'serviceProfile' => 'required|string',
    ];

    public function mount(): void
    {
        $tenantId = app(TenantManager::class)->getTenantId();
        $firstOlt = OltDevice::where('tenant_id', $tenantId)->first();
        if ($firstOlt) {
            $this->selectedOltId = $firstOlt->id;
            $this->loadData();
        }
    }

    public function updatedSelectedOltId(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        if (!$this->selectedOltId) return;

        $tenantId = app(TenantManager::class)->getTenantId();
        $olt = OltDevice::where('tenant_id', $tenantId)->findOrFail($this->selectedOltId);
        $service = app(OltProvisioningService::class);

        // 1. Discover ONUs
        $this->discoveredOnus = $service->discoverOnus($olt);

        // 2. Load active configured ONUs on this OLT
        $this->activeOnus = OnuDevice::with('subscription.customer')
            ->where('tenant_id', $tenantId)
            ->where('olt_device_id', $this->selectedOltId)
            ->get();
    }

    public function startProvision(string $sn, string $port): void
    {
        $this->selectedSerialNumber = $sn;
        $this->selectedGponPort = $port;
        $this->showProvisionModal = true;
    }

    public function provision(): void
    {
        $this->validate();
        
        $tenantId = app(TenantManager::class)->getTenantId();
        
        // 1. Create OnuDevice database entry
        $onu = OnuDevice::create([
            'tenant_id' => $tenantId,
            'olt_device_id' => $this->selectedOltId,
            'subscription_id' => $this->subscriptionId,
            'serial_number' => $this->selectedSerialNumber,
            'gpon_port' => $this->selectedGponPort,
            'line_profile' => $this->lineProfile,
            'service_profile' => $this->serviceProfile,
            'status' => 'pending',
        ]);

        // 2. Execute simulated CLI provisioning
        $service = app(OltProvisioningService::class);
        $result = $service->provisionOnu($onu);

        $this->cliLogs = $result['log'];
        session()->flash('message', "ONU successfully authorized. Assigned ID: {$result['onu_id']}");
        
        $this->showProvisionModal = false;
        $this->loadData();
    }

    public function readSignal(int $onuId): void
    {
        $onu = OnuDevice::findOrFail($onuId);
        $service = app(OltProvisioningService::class);
        
        $this->opticalReadings[$onuId] = $service->getOpticalPower($onu);
    }

    public function suspendOnu(int $onuId): void
    {
        $onu = OnuDevice::findOrFail($onuId);
        $service = app(OltProvisioningService::class);
        
        $service->suspendOnu($onu);
        session()->flash('message', "ONU {$onu->serial_number} has been administratively disabled.");
        $this->loadData();
    }

    public function reactivateOnu(int $onuId): void
    {
        $onu = OnuDevice::findOrFail($onuId);
        $service = app(OltProvisioningService::class);
        
        $service->reactivateOnu($onu);
        session()->flash('message', "ONU {$onu->serial_number} has been re-enabled.");
        $this->loadData();
    }

    public function deleteOnu(int $onuId): void
    {
        $onu = OnuDevice::findOrFail($onuId);
        $service = app(OltProvisioningService::class);
        
        $service->deleteOnu($onu);
        session()->flash('message', "ONU {$onu->serial_number} deprovisioned and deleted.");
        $this->loadData();
    }

    public function render()
    {
        $tenantId = app(TenantManager::class)->getTenantId();
        $olts = OltDevice::where('tenant_id', $tenantId)->get();
        
        // Load eligible active broadband subscriptions without an assigned ONU
        $assignedSubIds = OnuDevice::where('tenant_id', $tenantId)
            ->whereNotNull('subscription_id')
            ->pluck('subscription_id')
            ->toArray();

        $subscriptions = CustomerServiceSubscription::with('customer')
            ->where('tenant_id', $tenantId)
            ->whereNotIn('id', $assignedSubIds)
            ->get();

        return view('network::livewire.onu-provisioner', [
            'olts' => $olts,
            'subscriptions' => $subscriptions,
        ])->layout('layouts.app');
    }
}
