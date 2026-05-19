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
    case MANUAL_PAYMENT_RECEIPT = 'manual_payment_receipt';
    case PAYMENT_RECEIPT_REVERSAL = 'payment_receipt_reversal';
    case PAYMENT_ALLOCATION_REVERSAL = 'payment_allocation_reversal';
    case CREDIT_ADJUSTMENT = 'credit_adjustment';
    case DEBIT_ADJUSTMENT = 'debit_adjustment';
    case WAIVER = 'waiver';
    case PENALTY = 'penalty';
}

