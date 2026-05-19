<?php

declare(strict_types=1);

namespace App\Modules\Customer\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Core\Audit\Traits\LogsActivity;
use App\Modules\Customer\Enums\AddressType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $table = 'customer_addresses';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'type',
        'label',
        'address_line_1',
        'address_line_2',
        'city',
        'state_region',
        'postal_code',
        'country',
        'landmark',
        'latitude',
        'longitude',
        'is_default_billing',
        'is_default_installation',
    ];

    protected $casts = [
        'type' => AddressType::class,
        'is_default_billing' => 'boolean',
        'is_default_installation' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Get the customer that owns the address.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
