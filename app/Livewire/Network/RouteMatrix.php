<?php

declare(strict_types=1);

namespace App\Livewire\Network;

use App\Modules\Network\Models\Pop;
use App\Modules\Network\Models\DistributionBox;
use Livewire\Component;

class RouteMatrix extends Component
{
    /**
     * Selected POP/Zone ID for filtering.
     *
     * @var int|string|null
     */
    public $selectedPopId = null;

    /**
     * All available POPs.
     *
     * @var \Illuminate\Database\Eloquent\Collection
     */
    public $pops;

    /**
     * Mapped box utilization data.
     *
     * @var array
     */
    public array $boxData = [];

    /**
     * Initialize the component.
     */
    public function mount(): void
    {
        $this->pops = Pop::all();

        // Auto-select the first POP zone if available
        if ($this->pops->isNotEmpty()) {
            $this->selectedPopId = $this->pops->first()->id;
            $this->loadMatrix();
        }
    }

    /**
     * Trigger matrix loading when selected POP ID is updated.
     */
    public function updatedSelectedPopId(): void
    {
        $this->loadMatrix();
    }

    /**
     * Query active boxes and map utilization stats efficiently.
     */
    public function loadMatrix(): void
    {
        if (empty($this->selectedPopId)) {
            $this->boxData = [];
            return;
        }

        // Fetch distribution boxes under selected POP, counting customers
        $boxes = DistributionBox::withCount('customers')
            ->where('pop_id', $this->selectedPopId)
            ->get();

        $this->boxData = $boxes->map(function (DistributionBox $box) {
            $totalCapacity = $box->port_capacity;
            $usedPorts = $box->customers_count;
            $availablePorts = max(0, $totalCapacity - $usedPorts);
            
            $utilization = $totalCapacity > 0 
                ? round(($usedPorts / $totalCapacity) * 100, 1) 
                : 0.0;

            return [
                'id' => $box->id,
                'name' => $box->name,
                'location_details' => $box->location_details ?: 'Not Specified',
                'total_capacity' => $totalCapacity,
                'used_ports' => $usedPorts,
                'available_ports' => $availablePorts,
                'utilization_percentage' => $utilization,
            ];
        })->toArray();
    }

    /**
     * Render the component view.
     */
    public function render()
    {
        $activePop = $this->selectedPopId ? Pop::find($this->selectedPopId) : null;

        return view('livewire.network.route-matrix', [
            'activePop' => $activePop,
        ]);
    }
}
