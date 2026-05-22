<?php

declare(strict_types=1);

namespace App\Modules\CRM\Enums;

enum InteractionType: string
{
    case CALL = 'call';
    case EMAIL = 'email';
    case MEETING = 'meeting';
    case NOTE = 'note';

    public function label(): string
    {
        return match($this) {
            self::CALL => 'Phone Call',
            self::EMAIL => 'Email',
            self::MEETING => 'In-Person Meeting',
            self::NOTE => 'Internal Note',
        };
    }
}
