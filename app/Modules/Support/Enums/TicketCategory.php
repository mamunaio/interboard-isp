<?php

declare(strict_types=1);

namespace App\Modules\Support\Enums;

enum TicketCategory: string
{
    case TECHNICAL = 'technical';
    case BILLING = 'billing';
    case SALES = 'sales';
    case GENERAL = 'general';

    public function label(): string
    {
        return match($this) {
            self::TECHNICAL => 'Technical Support',
            self::BILLING => 'Billing & Payments',
            self::SALES => 'Sales Inquiry',
            self::GENERAL => 'General Issue',
        };
    }
}
