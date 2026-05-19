<?php

declare(strict_types=1);

namespace App\Modules\Customer\Enums;

enum SubscriptionStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case DISCONNECTED = 'disconnected';
    case TERMINATED = 'terminated';

    /**
     * Enforce explicit lifecycle status transitions.
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::PENDING => in_array($newStatus, [self::ACTIVE, self::TERMINATED], true),
            self::ACTIVE => in_array($newStatus, [self::SUSPENDED, self::DISCONNECTED, self::TERMINATED], true),
            self::SUSPENDED => in_array($newStatus, [self::ACTIVE, self::DISCONNECTED, self::TERMINATED], true),
            self::DISCONNECTED => in_array($newStatus, [self::ACTIVE, self::SUSPENDED, self::TERMINATED], true),
            self::TERMINATED => false, // terminal state
        };
    }
}
