<?php

declare(strict_types=1);

namespace App\Modules\Billing\Enums;

enum TaxCalculationMode: string
{
    case EXCLUSIVE = 'exclusive';
    case INCLUSIVE = 'inclusive';
}
