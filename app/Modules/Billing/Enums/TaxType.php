<?php

declare(strict_types=1);

namespace App\Modules\Billing\Enums;

enum TaxType: string
{
    case PERCENTAGE = 'percentage';
    case FIXED = 'fixed';
}
