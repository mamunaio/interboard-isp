<?php

declare(strict_types=1);

namespace App\Modules\CRM\Http\Livewire;

use App\Core\Tenant\TenantManager;
use App\Modules\CRM\Enums\InteractionType;
use App\Modules\CRM\Enums\LeadStatus;
use App\Modules\CRM\Models\Lead;
use App\Modules\CRM\Services\LeadService;
use Livewire\Component;

class LeadDetail extends Component
{
    public Lead $lead;
    public ?int $tenantId = null;

    // Interaction form
    public string $interactionType = 'call';
    public string $interactionSummary = '';
    public string $nextFollowUpDate = '';

    public function mount(int $id): void
    {
        $this->tenantId = app(TenantManager::class)->getTenantId();
        $this->lead = Lead::with(['assignee', 'interactions.user', 'expectedPlan'])->where('tenant_id', $this->tenantId)->findOrFail($id);
    }

    public function updateStatus(string $status): void
    {
        $this->lead->update(['status' => LeadStatus::from($status)]);
        $this->lead->refresh();
        session()->flash('message', 'Lead status updated.');
    }

    public function logInteraction(LeadService $leadService): void
    {
        $this->validate([
            'interactionType' => 'required|string',
            'interactionSummary' => 'required|string',
            'nextFollowUpDate' => 'nullable|date',
        ]);

        $leadService->addInteraction(
            lead: $this->lead,
            type: InteractionType::from($this->interactionType),
            summary: $this->interactionSummary,
            nextFollowUpDate: $this->nextFollowUpDate ?: null,
            userId: auth()->id() ?? auth('staff')->id()
        );

        $this->interactionSummary = '';
        $this->nextFollowUpDate = '';
        
        $this->lead->refresh();
        session()->flash('message', 'Interaction logged successfully.');
    }

    public function convertToCustomer(LeadService $leadService): void
    {
        try {
            $customer = $leadService->convertToCustomer($this->lead);
            $this->lead->refresh();
            session()->flash('message', "Lead converted! Customer created with code: {$customer->customer_code}");
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('c_r_m::livewire.lead-detail', [
            'interactionTypes' => InteractionType::cases(),
            'statuses' => LeadStatus::cases(),
        ]);
    }
}
