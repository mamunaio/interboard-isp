<?php

declare(strict_types=1);

namespace App\Modules\Customer\Enums;

enum AddressType: string
{
    case BILLING = 'billing';
    case INSTALLATION = 'installation';
    case BOTH = 'both';
}
