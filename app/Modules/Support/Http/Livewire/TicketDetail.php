<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Livewire;

use App\Core\Tenant\TenantManager;
use App\Modules\Support\Enums\TicketStatus;
use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Services\TicketService;
use Livewire\Component;

class TicketDetail extends Component
{
    public Ticket $ticket;
    public ?int $tenantId = null;

    public string $replyMessage = '';
    public bool $isInternalNote = false;

    public function mount(int $id): void
    {
        $this->tenantId = app(TenantManager::class)->getTenantId();
        $this->ticket = Ticket::with(['customer', 'assignee', 'replies.user'])->where('tenant_id', $this->tenantId)->findOrFail($id);
    }

    public function postReply(TicketService $ticketService): void
    {
        $this->validate([
            'replyMessage' => 'required|string',
        ]);

        $user = auth()->user() ?? auth('staff')->user();

        $ticketService->addReply(
            ticket: $this->ticket,
            message: $this->replyMessage,
            userId: $user ? $user->id : null,
            isCustomerReply: false,
            isInternal: $this->isInternalNote
        );

        $this->replyMessage = '';
        $this->isInternalNote = false;
        
        $this->ticket->refresh();
        session()->flash('message', 'Reply posted successfully.');
    }

    public function closeTicket(TicketService $ticketService): void
    {
        $ticketService->updateStatus($this->ticket, TicketStatus::CLOSED);
        $this->ticket->refresh();
        session()->flash('message', 'Ticket closed successfully.');
    }

    public function render()
    {
        return view('support::livewire.ticket-detail');
    }
}
