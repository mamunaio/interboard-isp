<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Http\Livewire;

use App\Modules\Tenant\Models\Tenant;
use Livewire\Component;

class SaaSDashboard extends Component
{
    public int $totalTenants = 0;
    public int $activeTenants = 0;
    public int $suspendedTenants = 0;
    public int $totalCustomers = 0;
    public int $totalLeads = 0;

    public function mount(): void
    {
        $this->totalTenants = Tenant::count();
        $this->activeTenants = Tenant::where('status', 'active')->count();
        $this->suspendedTenants = Tenant::where('status', 'suspended')->count();

        // Count across all tenants
        $this->totalCustomers = \DB::table('customers')->count();
        $this->totalLeads = \DB::table('leads')->count();
    }

    public function render()
    {
        $recentTenants = Tenant::orderBy('created_at', 'desc')->limit(5)->get();

        return view('tenant::livewire.saas-dashboard', [
            'recentTenants' => $recentTenants,
        ])->layout('layouts.saas');
    }
}
