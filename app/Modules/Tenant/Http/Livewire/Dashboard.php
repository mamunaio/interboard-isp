<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Http\Livewire;

use App\Modules\Tenant\Models\AuditLog;
use Livewire\Component;

class Dashboard extends Component
{
    public array $stats = [];
    public array $recentLogs = [];
    public string $trafficSpeed = '45.8';

    /**
     * Get the listeners for real-time Reverb broadcasts.
     */
    public function getListeners(): array
    {
        return [
            "echo-private:tenant.dashboard,BandwidthUpdated" => 'refreshTraffic',
        ];
    }

    /**
     * Mount and gather initial data.
     */
    public function mount(): void
    {
        $this->refreshStats();
        $this->refreshLogs();
    }

    /**
     * Refresh system status stats.
     */
    public function refreshStats(): void
    {
        $this->stats = [
            'active_customers' => 1420,
            'suspended_customers' => 12,
            'gpon_onu_online' => 842,
            'gpon_onu_offline' => 5,
        ];
    }

    /**
     * Fetch recent activity audit logs.
     */
    public function refreshLogs(): void
    {
        $this->recentLogs = AuditLog::orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    /**
     * Handler invoked when Reverb private channel pushes traffic update.
     */
    public function refreshTraffic(array $event): void
    {
        $this->trafficSpeed = $event['speed'] ?? '45.8';
        $this->refreshLogs();
    }

    /**
     * Render the Livewire blade layout.
     */
    public function render()
    {
        return view('tenant::livewire.dashboard');
    }
}
