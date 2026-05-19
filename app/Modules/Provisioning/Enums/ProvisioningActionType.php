<?php

declare(strict_types=1);

namespace App\Modules\Provisioning\Enums;

enum ProvisioningActionType: string
{
    case PROVISION = 'provision';
    case SUSPEND = 'suspend';
    case REACTIVATE = 'reactivate';
    case TERMINATE = 'terminate';
    case PROFILE_CHANGE = 'profile_change';
}
