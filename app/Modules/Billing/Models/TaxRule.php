<?php

declare(strict_types=1);

namespace App\Modules\Billing\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Core\Audit\Traits\LogsActivity;
use App\Modules\Billing\Enums\TaxType;
use App\Modules\Billing\Enums\TaxCalculationMode;
use Illuminate\Database\Eloquent\Model;

class TaxRule extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $table = 'tax_rules';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'tax_type',
        'calculation_mode',
        'is_compound',
        'compound_order',
        'rate',
        'fixed_amount',
        'applies_to_invoice_types',
        'active',
    ];

    protected $casts = [
        'tax_type' => TaxType::class,
        'calculation_mode' => TaxCalculationMode::class,
        'is_compound' => 'boolean',
        'compound_order' => 'integer',
        'rate' => 'decimal:4',
        'fixed_amount' => 'decimal:4',
        'applies_to_invoice_types' => 'array',
        'active' => 'boolean',
    ];
}
