<?php

declare(strict_types=1);

namespace App\Modules\Billing\Enums;

enum TransactionType: string
{
    case MANUAL_CREDIT = 'manual_credit';
    case MANUAL_DEBIT = 'manual_debit';
    case ADJUSTMENT = 'adjustment';
    case REFUND_CREDIT = 'refund_credit';
    case RESELLER_COLLECTION = 'reseller_collection';
    case MIGRATION_BALANCE = 'migration_balance';
    case PAYMENT_ALLOCATION = 'payment_allocation';
}

