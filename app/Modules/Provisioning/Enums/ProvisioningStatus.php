<?php

declare(strict_types=1);

namespace App\Modules\Provisioning\Enums;

enum ProvisioningStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
