<?php

declare(strict_types=1);

namespace App\Modules\Billing\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case MOBILE_BANKING_MANUAL = 'mobile_banking_manual';
    case MANUAL_ADJUSTMENT = 'manual_adjustment';

    // Explicit Gateway-Backed Methods
    case BKASH_GATEWAY = 'bkash_gateway';
    case NAGAD_GATEWAY = 'nagad_gateway';
    case SSLCOMMERZ_GATEWAY = 'sslcommerz_gateway';
}
