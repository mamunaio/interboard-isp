<?php

declare(strict_types=1);

namespace App\Modules\Billing\Enums;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case ISSUED = 'issued';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case VOID = 'void';
    case CANCELLED = 'cancelled';

    /**
     * Enforce explicit lifecycle status transitions.
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::DRAFT => in_array($newStatus, [self::ISSUED, self::CANCELLED], true),
            self::ISSUED => in_array($newStatus, [self::PARTIALLY_PAID, self::PAID, self::VOID], true),
            self::PARTIALLY_PAID => in_array($newStatus, [self::PAID, self::VOID], true),
            default => false, // paid, void, and cancelled are terminal states for this phase
        };
    }
}

