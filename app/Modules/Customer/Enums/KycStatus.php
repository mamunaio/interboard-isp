<?php

declare(strict_types=1);

namespace App\Modules\Customer\Enums;

enum KycStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';
}
