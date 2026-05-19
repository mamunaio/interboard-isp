<?php

declare(strict_types=1);

namespace App\Modules\Billing\Enums;

enum TransactionDirection: string
{
    case CREDIT = 'credit';
    case DEBIT = 'debit';
}
