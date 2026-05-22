<?php

declare(strict_types=1);

namespace App\Modules\Support\Services;

use App\Modules\Customer\Models\Customer;
use App\Modules\Support\Enums\TicketCategory;
use App\Modules\Support\Enums\TicketPriority;
use App\Modules\Support\Enums\TicketStatus;
use App\Modules\Support\Models\Ticket;
use App\Modules\Support\Models\TicketReply;
use Illuminate\Support\Facades\DB;

class TicketService
{
    /**
     * Create a new support ticket.
     */
    public function createTicket(
        Customer $customer,
        string $title,
        string $initialMessage,
        TicketCategory $category = TicketCategory::TECHNICAL,
        TicketPriority $priority = TicketPriority::MEDIUM,
        ?int $assignedToId = null,
        ?int $creatorUserId = null,
        bool $isCustomerCreated = false
    ): Ticket {
        return DB::transaction(function () use (
            $customer, $title, $initialMessage, $category, $priority, $assignedToId, $creatorUserId, $isCustomerCreated
        ) {
            $ticket = Ticket::create([
                'tenant_id' => $customer->tenant_id,
                'customer_id' => $customer->id,
                'assigned_to_id' => $assignedToId,
                'title' => $title,
                'category' => $category,
                'priority' => $priority,
                'status' => TicketStatus::OPEN,
            ]);

            TicketReply::create([
                'ticket_id' => $ticket->id,
                'user_id' => $creatorUserId,
                'is_customer_reply' => $isCustomerCreated,
                'message' => $initialMessage,
                'is_internal' => false,
            ]);

            return $ticket;
        });
    }

    /**
     * Add a reply or internal note to an existing ticket.
     */
    public function addReply(
        Ticket $ticket,
        string $message,
        ?int $userId = null,
        bool $isCustomerReply = false,
        bool $isInternal = false
    ): TicketReply {
        return DB::transaction(function () use ($ticket, $message, $userId, $isCustomerReply, $isInternal) {
            $reply = TicketReply::create([
                'ticket_id' => $ticket->id,
                'user_id' => $userId,
                'is_customer_reply' => $isCustomerReply,
                'message' => $message,
                'is_internal' => $isInternal,
            ]);

            // Update ticket status based on who replied (if it's not an internal note)
            if (!$isInternal) {
                if ($isCustomerReply) {
                    $ticket->update(['status' => TicketStatus::OPEN]);
                } else {
                    $ticket->update(['status' => TicketStatus::WAITING]);
                }
            }

            return $reply;
        });
    }

    /**
     * Update the status of a ticket.
     */
    public function updateStatus(Ticket $ticket, TicketStatus $status): bool
    {
        return $ticket->update(['status' => $status]);
    }
}
