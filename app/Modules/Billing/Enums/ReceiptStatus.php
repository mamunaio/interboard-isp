<?php

declare(strict_types=1);

namespace App\Modules\Billing\Enums;

enum ReceiptStatus: string
{
    case POSTED = 'posted';
}
