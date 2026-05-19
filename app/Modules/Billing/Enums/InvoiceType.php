<?php

declare(strict_types=1);

namespace App\Modules\Billing\Enums;

enum InvoiceType: string
{
    case SUBSCRIPTION = 'subscription';
    case INSTALLATION = 'installation';
    case HARDWARE = 'hardware';
    case MANUAL_ADJUSTMENT = 'manual_adjustment';
    case OTHER = 'other';
    case RECONNECTION_FEE = 'reconnection_fee';
}
