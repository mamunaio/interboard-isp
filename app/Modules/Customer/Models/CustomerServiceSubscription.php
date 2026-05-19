<?php

declare(strict_types=1);

namespace App\Modules\Customer\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Core\Audit\Traits\LogsActivity;
use App\Modules\Customer\Enums\SubscriptionStatus;
use App\Modules\Customer\Enums\ServiceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerServiceSubscription extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $table = 'customer_service_subscriptions';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'service_package_id',
        'subscription_code',
        'service_type_snapshot',
        'status',
        'activated_at',
        'suspended_at',
        'disconnected_at',
        'terminated_at',
        'notes',
    ];

    protected $casts = [
        'status' => SubscriptionStatus::class,
        'service_type_snapshot' => ServiceType::class,
        'activated_at' => 'datetime',
        'suspended_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'terminated_at' => 'datetime',
    ];

    /**
     * Get the customer that owns the subscription.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the service package for the subscription.
     */
    public function servicePackage(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class);
    }

    /**
     * Get the scheduled changes for the subscription.
     */
    public function scheduledChanges(): HasMany
    {
        return $this->hasMany(SubscriptionScheduledChange::class, 'customer_service_subscription_id');
    }
}
