<?php

declare(strict_types=1);

namespace App\Modules\Customer\Enums;

enum ScheduledChangeStatus: string
{
    case PENDING = 'pending';
    case EXECUTED = 'executed';
    case CANCELLED = 'cancelled';
}
