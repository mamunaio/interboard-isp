<?php

declare(strict_types=1);

namespace App\Modules\Provisioning\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Provisioning\Enums\ProvisioningActionType;
use App\Modules\Provisioning\Enums\ProvisioningStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceProvisioningEvent extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'subscription_id',
        'action_type',
        'status',
        'provider',
        'request_payload_json',
        'response_payload_json',
        'error_message',
        'retry_count',
        'executed_at',
    ];

    protected $casts = [
        'action_type' => ProvisioningActionType::class,
        'status' => ProvisioningStatus::class,
        'request_payload_json' => 'array',
        'response_payload_json' => 'array',
        'executed_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(CustomerServiceSubscription::class, 'subscription_id');
    }
}
