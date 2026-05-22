<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Enums;

enum InventoryStatus: string
{
    case IN_STOCK = 'in_stock';
    case DEPLOYED = 'deployed';
    case WITH_TECHNICIAN = 'with_technician';
    case DEFECTIVE = 'defective';
    case RETIRED = 'retired';
    case PENDING_RECOVERY = 'pending_recovery';

    public function label(): string
    {
        return match($this) {
            self::IN_STOCK => 'In Stock',
            self::DEPLOYED => 'Deployed to Customer',
            self::WITH_TECHNICIAN => 'With Technician',
            self::DEFECTIVE => 'Defective',
            self::RETIRED => 'Retired',
            self::PENDING_RECOVERY => 'Pending Recovery',
        };
    }
}
