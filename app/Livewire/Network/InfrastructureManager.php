<?php

declare(strict_types=1);

namespace App\Livewire\Network;

use App\Modules\Network\Models\Pop;
use App\Modules\Network\Models\DistributionBox;
use Livewire\Component;

class InfrastructureManager extends Component
{
    /**
     * All POP zones.
     *
     * @var \Illuminate\Database\Eloquent\Collection
     */
    public $pops;

    /**
     * Selected POP ID.
     *
     * @var int|null
     */
    public $selectedPopId = null;

    /**
     * Input fields for adding a Distribution Box.
     */
    public string $boxName = '';
    public string $boxLocation = '';
    public int $boxPortCapacity = 8;

    /**
     * Initialize the component.
     */
    public function mount(): void
    {
        $this->loadPops();
        
        // Auto-select the first POP if available
        if ($this->pops->isNotEmpty()) {
            $this->selectedPopId = $this->pops->first()->id;
        }
    }

    /**
     * Load all POPs with their distribution boxes from the database.
     */
    public function loadPops(): void
    {
        $this->pops = Pop::withCount('distributionBoxes')->get();
    }

    /**
     * Select a specific POP.
     */
    public function selectPop(int $popId): void
    {
        $this->selectedPopId = $popId;
        $this->resetValidation();
    }

    /**
     * Create a new POP record and refresh the list.
     */
    public function addPop(string $name): void
    {
        $name = trim($name);
        
        if (empty($name)) {
            $this->addError('new_pop_name', 'The POP name is required.');
            return;
        }

        $pop = Pop::create([
            'name' => $name,
            'status' => true,
        ]);

        $this->loadPops();
        $this->selectedPopId = $pop->id;

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "POP \"{$name}\" created successfully!"
        ]);
    }

    /**
     * Create a new DistributionBox under the selected POP.
     */
    public function addBox(): void
    {
        $this->validate([
            'selectedPopId' => 'required|exists:pops,id',
            'boxName' => 'required|string|min:2|max:255',
            'boxLocation' => 'nullable|string|max:255',
            'boxPortCapacity' => 'required|integer|min:1|max:256',
        ], [
            'boxName.required' => 'Box Name is required.',
            'boxName.min' => 'Box Name must be at least 2 characters.',
            'boxPortCapacity.required' => 'Port Capacity is required.',
            'boxPortCapacity.integer' => 'Port Capacity must be a number.',
            'selectedPopId.required' => 'Please select a Point of Presence (POP).',
        ]);

        $box = DistributionBox::create([
            'pop_id' => $this->selectedPopId,
            'name' => trim($this->boxName),
            'location_details' => trim($this->boxLocation) ?: null,
            'port_capacity' => $this->boxPortCapacity,
        ]);

        $this->reset(['boxName', 'boxLocation']);
        $this->boxPortCapacity = 8;

        $this->loadPops();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Distribution Box \"{$box->name}\" added successfully!"
        ]);
    }

    /**
     * Find and delete a distribution box.
     */
    public function deleteBox(int $id): void
    {
        $box = DistributionBox::find($id);

        if ($box) {
            $boxName = $box->name;
            $box->delete();

            $this->loadPops();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Distribution Box \"{$boxName}\" has been deleted."
            ]);
        }
    }

    /**
     * Render the component view.
     */
    public function render()
    {
        $selectedPop = $this->selectedPopId
            ? Pop::with('distributionBoxes')->find($this->selectedPopId)
            : null;

        return view('livewire.network.infrastructure-manager', [
            'selectedPop' => $selectedPop,
        ]);
    }
}
