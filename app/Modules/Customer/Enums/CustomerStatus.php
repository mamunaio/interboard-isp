<?php

declare(strict_types=1);

namespace App\Modules\Customer\Enums;

enum CustomerStatus: string
{
    case LEAD = 'lead';
    case PROSPECT = 'prospect';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case DISCONNECTED = 'disconnected';
    case TERMINATED = 'terminated';
    case BLACKLISTED = 'blacklisted';

    /**
     * Determine if a status transition is permitted under explicit lifecycle rules.
     */
    public function canTransitionTo(self $newStatus): bool
    {
        if ($this === $newStatus) {
            return true;
        }

        return match ($this) {
            self::LEAD => in_array($newStatus, [self::PROSPECT, self::TERMINATED, self::BLACKLISTED], true),
            self::PROSPECT => in_array($newStatus, [self::ACTIVE, self::LEAD, self::TERMINATED, self::BLACKLISTED], true),
            self::ACTIVE => in_array($newStatus, [self::SUSPENDED, self::DISCONNECTED, self::TERMINATED, self::BLACKLISTED], true),
            self::SUSPENDED => in_array($newStatus, [self::ACTIVE, self::DISCONNECTED, self::TERMINATED, self::BLACKLISTED], true),
            self::DISCONNECTED => in_array($newStatus, [self::ACTIVE, self::TERMINATED, self::BLACKLISTED], true),
            self::TERMINATED => in_array($newStatus, [self::PROSPECT, self::BLACKLISTED], true),
            self::BLACKLISTED => in_array($newStatus, [self::TERMINATED], true),
        };
    }
}
