<?php

declare(strict_types=1);

namespace App\Modules\Billing\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class FinancialPeriod extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'start_date',
        'end_date',
        'period_name',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}
