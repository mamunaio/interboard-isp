<?php

declare(strict_types=1);

namespace App\Modules\Support\Enums;

enum TicketStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case WAITING = 'waiting';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match($this) {
            self::OPEN => 'Open',
            self::IN_PROGRESS => 'In Progress',
            self::WAITING => 'Waiting on Customer',
            self::CLOSED => 'Closed',
        };
    }
}
