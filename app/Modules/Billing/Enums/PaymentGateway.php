<?php

declare(strict_types=1);

namespace App\Modules\Billing\Enums;

enum PaymentGateway: string
{
    case BKASH = 'bkash';
    case NAGAD = 'nagad';
    case SSLCOMMERZ = 'sslcommerz';
}
