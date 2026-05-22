<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Livewire;

use App\Core\Tenant\TenantManager;
use App\Modules\Customer\Models\Customer;
use App\Modules\Support\Enums\TicketCategory;
use App\Modules\Support\Enums\TicketPriority;
use App\Modules\Support\Enums\TicketStatus;
use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Services\TicketService;
use Livewire\Component;
use Livewire\WithPagination;

class TicketIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';
    public string $filterPriority = '';
    
    public ?int $tenantId = null;

    // Create Modal State
    public bool $isCreateModalOpen = false;
    public string $customerId = '';
    public string $title = '';
    public string $category = 'technical';
    public string $priority = 'medium';
    public string $message = '';

    public function mount(): void
    {
        $this->tenantId = app(TenantManager::class)->getTenantId();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterPriority(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->customerId = '';
        $this->title = '';
        $this->category = 'technical';
        $this->priority = 'medium';
        $this->message = '';
        $this->isCreateModalOpen = true;
    }

    public function closeCreateModal(): void
    {
        $this->isCreateModalOpen = false;
    }

    public function createTicket(TicketService $ticketService): void
    {
        $this->validate([
            'customerId' => 'required|exists:customers,id',
            'title' => 'required|string|max:255',
            'category' => 'required|string',
            'priority' => 'required|string',
            'message' => 'required|string',
        ]);

        $customer = Customer::where('tenant_id', $this->tenantId)->findOrFail($this->customerId);
        $user = auth()->user() ?? auth('staff')->user();

        $ticketService->createTicket(
            customer: $customer,
            title: $this->title,
            initialMessage: $this->message,
            category: TicketCategory::from($this->category),
            priority: TicketPriority::from($this->priority),
            creatorUserId: $user ? $user->id : null,
            isCustomerCreated: false
        );

        $this->closeCreateModal();
        session()->flash('message', 'Support ticket created successfully.');
    }

    public function render()
    {
        $query = Ticket::with(['customer', 'assignee'])->where('tenant_id', $this->tenantId);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhereHas('customer', function ($c) {
                      $c->where('full_name', 'like', '%' . $this->search . '%')
                        ->orWhere('customer_code', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterPriority) {
            $query->where('priority', $this->filterPriority);
        }

        $tickets = $query->orderBy('updated_at', 'desc')->paginate(15);
        $customers = Customer::where('tenant_id', $this->tenantId)->orderBy('full_name')->get();

        return view('support::livewire.ticket-index', [
            'tickets' => $tickets,
            'customers' => $customers,
            'statuses' => TicketStatus::cases(),
            'priorities' => TicketPriority::cases(),
            'categories' => TicketCategory::cases(),
        ]);
    }
}
