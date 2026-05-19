<?php

declare(strict_types=1);

namespace App\Modules\Customer\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Core\Audit\Traits\LogsActivity;
use App\Modules\Billing\Models\TaxRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePlan extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $table = 'service_plans';

    protected $fillable = [
        'tenant_id',
        'plan_code',
        'name',
        'monthly_price',
        'billing_cycle',
        'download_speed',
        'upload_speed',
        'tax_rule_id',
        'is_active',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    /**
     * Get the tax rule associated with the service plan.
     */
    public function taxRule(): BelongsTo
    {
        return $this->belongsTo(TaxRule::class, 'tax_rule_id');
    }
}
