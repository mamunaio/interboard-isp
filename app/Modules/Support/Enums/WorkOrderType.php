<?php

declare(strict_types=1);

namespace App\Modules\Support\Enums;

enum WorkOrderType: string
{
    case INSTALLATION = 'installation';
    case MAINTENANCE = 'maintenance';
    case RELOCATION = 'relocation';
    case DISCONNECTION = 'disconnection';

    public function label(): string
    {
        return match($this) {
            self::INSTALLATION => 'Installation',
            self::MAINTENANCE => 'Maintenance',
            self::RELOCATION => 'Relocation',
            self::DISCONNECTION => 'Disconnection',
        };
    }
}
