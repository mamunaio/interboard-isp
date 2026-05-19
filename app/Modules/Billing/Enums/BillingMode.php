<?php

declare(strict_types=1);

namespace App\Modules\Billing\Enums;

enum BillingMode: string
{
    case PREPAID = 'prepaid';
    case POSTPAID = 'postpaid';
}
