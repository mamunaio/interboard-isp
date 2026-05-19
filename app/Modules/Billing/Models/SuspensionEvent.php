<?php

declare(strict_types=1);

namespace App\Modules\Billing\Models;

use App\Core\Audit\Traits\LogsActivity;
use App\Core\Tenant\Traits\BelongsToTenant;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuspensionEvent extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $table = 'suspension_events';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'customer_service_subscription_id',
        'action_type',
        'reason',
        'triggering_metadata',
        'reconnection_invoice_id',
        'created_by_type',
        'created_by_id',
    ];

    protected $casts = [
        'triggering_metadata' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        // Auto-populate created_at on creation
        static::creating(function (self $model) {
            $model->created_at = $model->freshTimestamp();
        });

        // Enforce strict immutability: block updates and deletes
        static::updating(function (self $model) {
            throw new \RuntimeException('Suspension event history is strictly immutable and cannot be updated.');
        });

        static::deleting(function (self $model) {
            throw new \RuntimeException('Suspension event history is strictly immutable and cannot be deleted.');
        });
    }

    /**
     * Get the subscription associated with the suspension event.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(CustomerServiceSubscription::class, 'customer_service_subscription_id');
    }

    /**
     * Get the reconnection invoice associated with the event.
     */
    public function reconnectionInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'reconnection_invoice_id');
    }

    public function actor(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'created_by_type', 'created_by_id');
    }
}
