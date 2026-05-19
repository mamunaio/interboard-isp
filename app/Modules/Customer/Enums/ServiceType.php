<?php

declare(strict_types=1);

namespace App\Modules\Customer\Enums;

enum ServiceType: string
{
    case BROADBAND = 'broadband';
    case DEDICATED_INTERNET = 'dedicated_internet';
    case IPTV = 'iptv';
    case VOIP = 'voip';
    case OTHER = 'other';
}
