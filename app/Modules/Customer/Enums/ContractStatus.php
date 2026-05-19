<?php

declare(strict_types=1);

namespace App\Modules\Customer\Enums;

enum ContractStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case TERMINATED = 'terminated';

    /**
     * Define strict contract status transition rules.
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::DRAFT => in_array($newStatus, [self::ACTIVE, self::TERMINATED], true),
            self::ACTIVE => in_array($newStatus, [self::EXPIRED, self::TERMINATED], true),
            self::EXPIRED => in_array($newStatus, [self::ACTIVE, self::TERMINATED], true),
            self::TERMINATED => false, // terminal state
        };
    }
}
