<?php

declare(strict_types=1);

namespace App\Modules\Customer\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Core\Audit\Traits\LogsActivity;
use App\Modules\Customer\Enums\ContractStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerContract extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $table = 'customer_contracts';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'contract_number',
        'title',
        'start_date',
        'end_date',
        'auto_renew',
        'renewal_term_days',
        'early_termination_fee',
        'status',
        'terms_snapshot',
        'signed_at',
        'notes',
    ];

    protected $casts = [
        'status' => ContractStatus::class,
        'terms_snapshot' => 'array',
        'auto_renew' => 'boolean',
        'signed_at' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
        'early_termination_fee' => 'decimal:2',
        'renewal_term_days' => 'integer',
    ];

    /**
     * Get the customer that owns the contract.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
