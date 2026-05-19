<?php

declare(strict_types=1);

namespace App\Modules\Customer\Enums;

enum ContactType: string
{
    case GENERAL = 'general';
    case BILLING = 'billing';
    case TECHNICAL = 'technical';
    case EMERGENCY = 'emergency';
}
