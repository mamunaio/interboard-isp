<?php

declare(strict_types=1);

namespace App\Modules\Customer\Enums;

enum CustomerType: string
{
    case RESIDENTIAL = 'residential';
    case BUSINESS = 'business';
    case ENTERPRISE = 'enterprise';
}
