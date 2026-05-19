<?php

declare(strict_types=1);

namespace App\Modules\Customer\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Core\Audit\Traits\LogsActivity;
use App\Modules\Customer\Enums\ScheduledChangeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionScheduledChange extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $table = 'subscription_scheduled_changes';

    protected $fillable = [
        'tenant_id',
        'customer_service_subscription_id',
        'target_service_package_id',
        'scheduled_for',
        'payload',
        'status',
        'executed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'status' => ScheduledChangeStatus::class,
        'payload' => 'array',
        'scheduled_for' => 'date',
        'executed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the subscription that owns the change.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(CustomerServiceSubscription::class, 'customer_service_subscription_id');
    }

    /**
     * Get the target service package for the scheduled change.
     */
    public function targetPackage(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class, 'target_service_package_id');
    }
}
