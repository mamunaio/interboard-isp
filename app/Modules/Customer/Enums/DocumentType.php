<?php

declare(strict_types=1);

namespace App\Modules\Customer\Enums;

enum DocumentType: string
{
    case NATIONAL_ID = 'national_id';
    case PASSPORT = 'passport';
    case DRIVING_LICENSE = 'driving_license';
    case TRADE_LICENSE = 'trade_license';
    case TAX_CERTIFICATE = 'tax_certificate';
    case UTILITY_BILL = 'utility_bill';
    case CONTRACT_ATTACHMENT = 'contract_attachment';
    case OTHER = 'other';
}
