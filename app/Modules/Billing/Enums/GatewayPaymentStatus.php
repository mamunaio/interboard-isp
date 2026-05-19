<?php

declare(strict_types=1);

namespace App\Modules\Billing\Enums;

enum GatewayPaymentStatus: string
{
    case INITIATED = 'initiated';
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case FAILED = 'failed';
    case EXPIRED = 'expired';
}
