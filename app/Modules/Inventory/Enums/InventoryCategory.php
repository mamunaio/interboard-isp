<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Enums;

enum InventoryCategory: string
{
    case ONU = 'onu';
    case ROUTER = 'router';
    case SWITCH = 'switch';
    case CABLE = 'cable';
    case ACCESSORIES = 'accessories';

    public function label(): string
    {
        return match($this) {
            self::ONU => 'ONU',
            self::ROUTER => 'Wi-Fi Router',
            self::SWITCH => 'Switch',
            self::CABLE => 'Cable/Fiber',
            self::ACCESSORIES => 'Accessories',
        };
    }
}
