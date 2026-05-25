<?php

declare(strict_types=1);

namespace App\Livewire\Support;

use App\Modules\Support\Models\Ticket;
use App\Modules\Customer\Models\Customer;
use App\Modules\Network\Models\Pop;
use App\Modules\Support\Enums\TicketCategory;
use App\Modules\Support\Enums\TicketPriority;
use App\Modules\Support\Enums\TicketStatus;
use App\Modules\Tenant\Models\Tenant;
use Livewire\Component;

class TicketManager extends Component
{
    /**
     * Component properties.
     */
    public $tickets;
    public string $priority = 'medium'; // low, medium, high
    public string $category = 'technical'; // technical, billing, sales, general
    public string $title = '';
    public string $description = ''; // Saved in ticket_replies as the first message
    
    /**
     * Target customer/POP configurations.
     */
    public ?int $customerId = null;
    public ?int $selectedPopId = null;

    /**
     * Filter collections.
     */
    public $customers;
    public $pops;

    /**
     * Initialize the component.
     */
    public function mount(): void
    {
        $this->pops = Pop::all();
        $this->customers = Customer::orderBy('full_name', 'asc')->get();
        
        if ($this->customers->isNotEmpty()) {
            $this->customerId = $this->customers->first()->id;
        }

        $this->loadTickets();
    }

    /**
     * Load all tickets with their customer and pop.
     */
    public function loadTickets(): void
    {
        $query = Ticket::with(['customer.distributionBox.pop', 'assignee'])
            ->orderByRaw('CASE WHEN status = "resolved" THEN 1 ELSE 0 END ASC')
            ->orderBy('sla_due_at', 'asc'); // Overdue/closest SLA first

        if ($this->selectedPopId) {
            $query->whereHas('customer.distributionBox', function ($q) {
                $q->where('pop_id', $this->selectedPopId);
            });
        }

        $this->tickets = $query->get();
    }

    /**
     * Create a new ticket and calculate SLA due date.
     */
    public function createTicket(): void
    {
        $this->validate([
            'customerId' => 'required|exists:customers,id',
            'title' => 'required|string|min:3|max:255',
            'category' => 'required|in:technical,billing,sales,general',
            'priority' => 'required|in:low,medium,high,urgent',
            'description' => 'required|string|min:5',
        ], [
            'customerId.required' => 'Please select a customer.',
            'title.required' => 'Ticket Title is required.',
            'description.required' => 'Ticket Description is required.',
        ]);

        // Calculate SLA Due Date
        // 'high' = 4 hours, 'medium' = 24 hours, 'low' = 48 hours
        $hours = match ($this->priority) {
            'urgent' => 2,
            'high' => 4,
            'medium' => 24,
            'low' => 48,
            default => 24,
        };

        $slaDueAt = now()->addHours($hours);
        $tenant = Tenant::first();

        $ticket = Ticket::create([
            'tenant_id' => $tenant ? $tenant->id : 1,
            'customer_id' => $this->customerId,
            'title' => trim($this->title),
            'category' => TicketCategory::from($this->category),
            'status' => TicketStatus::OPEN,
            'priority' => TicketPriority::from($this->priority),
            'sla_due_at' => $slaDueAt,
        ]);

        // Save the ticket description as the first internal reply
        $ticket->replies()->create([
            'message' => trim($this->description),
            'is_customer_reply' => false,
            'is_internal' => false,
        ]);

        $this->reset(['title', 'description']);
        $this->priority = 'medium';
        $this->category = 'technical';

        $this->loadTickets();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Ticket #{$ticket->id} created successfully with {$hours}-hour SLA!"
        ]);
    }

    /**
     * Resolve a ticket.
     */
    public function resolveTicket(int $id): void
    {
        $ticket = Ticket::find($id);

        if ($ticket) {
            $ticket->update([
                'status' => TicketStatus::RESOLVED,
                'resolved_at' => now(),
            ]);

            $this->loadTickets();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Ticket #{$ticket->id} has been marked as Resolved."
            ]);
        }
    }

    /**
     * Render the component view.
     */
    public function render()
    {
        return view('livewire.support.ticket-manager');
    }
}
