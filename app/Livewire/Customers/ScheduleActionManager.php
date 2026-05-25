<?php

declare(strict_types=1);

namespace App\Livewire\Customers;

use App\Models\ScheduledAction;
use Livewire\Component;

class ScheduleActionManager extends Component
{
    /**
     * Target customer ID.
     */
    public int $customerId;

    /**
     * Input fields for creating a scheduled action.
     */
    public string $actionType = 'suspend'; // suspend, reactivate, change_package
    public string $targetDate = '';
    public string $remarks = '';
    
    /**
     * Additional payload settings (like targeted package ID).
     */
    public ?int $selectedPackageId = null;

    /**
     * List of packages for change_package option.
     *
     * @var \Illuminate\Support\Collection
     */
    public $packages;

    /**
     * Initialize the component.
     */
    public function mount(int $customerId): void
    {
        $this->customerId = $customerId;
        $this->targetDate = now()->addDay()->format('Y-m-d'); // Default tomorrow

        // Load available service packages/plans if existing in customer module
        if (class_exists(\App\Modules\Customer\Models\ServicePlan::class)) {
            $this->packages = \App\Modules\Customer\Models\ServicePlan::all();
        } else {
            $this->packages = collect([]);
        }
    }

    /**
     * Validate and save the scheduled action.
     */
    public function save(): void
    {
        $rules = [
            'actionType' => 'required|in:suspend,reactivate,change_package',
            'targetDate' => 'required|date|after_or_equal:today',
            'remarks' => 'nullable|string|max:255',
        ];

        if ($this->actionType === 'change_package') {
            $rules['selectedPackageId'] = 'required|integer';
        }

        $this->validate($rules, [
            'targetDate.after_or_equal' => 'The target date must be today or in the future.',
            'selectedPackageId.required' => 'Please select a service package to change to.',
        ]);

        $payload = null;
        if ($this->actionType === 'change_package') {
            $payload = ['package_id' => $this->selectedPackageId];
        }

        $action = ScheduledAction::create([
            'customer_id' => $this->customerId,
            'action_type' => $this->actionType,
            'target_date' => $this->targetDate,
            'payload' => $payload,
            'remarks' => trim($this->remarks) ?: null,
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        // Reset inputs
        $this->reset(['remarks', 'selectedPackageId']);
        $this->actionType = 'suspend';
        $this->targetDate = now()->addDay()->format('Y-m-d');

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Action scheduled successfully for " . $action->target_date->format('Y-m-d')
        ]);
    }

    /**
     * Render the component view.
     */
    public function render()
    {
        // Fetch pending actions scheduled for this customer
        $scheduledActions = ScheduledAction::where('customer_id', $this->customerId)
            ->where('status', 'pending')
            ->orderBy('target_date', 'asc')
            ->get();

        return view('livewire.customers.schedule-action-manager', [
            'scheduledActions' => $scheduledActions,
        ]);
    }
}
