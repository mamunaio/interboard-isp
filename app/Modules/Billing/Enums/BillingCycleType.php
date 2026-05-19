<?php

declare(strict_types=1);

namespace App\Modules\Billing\Enums;

enum BillingCycleType: string
{
    case ANNIVERSARY = 'anniversary';
    case CALENDAR = 'calendar';
}
