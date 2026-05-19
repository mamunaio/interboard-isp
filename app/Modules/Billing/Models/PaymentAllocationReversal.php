<?php

declare(strict_types=1);

namespace App\Modules\Billing\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAllocationReversal extends Model
{
    use BelongsToTenant;

    protected $table = 'payment_allocation_reversals';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'payment_allocation_id',
        'amount',
        'reason',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'created_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            $model->created_at = $model->freshTimestamp();
        });

        static::updating(function (self $model) {
            throw new \RuntimeException('Payment allocation reversals are strictly immutable.');
        });

        static::deleting(function (self $model) {
            throw new \RuntimeException('Payment allocation reversals are strictly immutable.');
        });
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(PaymentAllocation::class, 'payment_allocation_id');
    }
}
