<?php

declare(strict_types=1);

namespace App\Modules\Billing\Models;

use App\Core\Audit\Traits\LogsActivity;
use App\Core\Tenant\Traits\BelongsToTenant;
use App\Modules\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GatewayPaymentAttempt extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $table = 'gateway_payment_attempts';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'gateway',
        'attempt_reference',
        'gateway_transaction_id',
        'requested_amount',
        'currency',
        'status',
        'gateway_payload',
        'verified_at',
        'payment_receipt_id',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:4',
        'gateway_payload' => 'array',
        'verified_at' => 'datetime',
    ];

    /**
     * The customer associated with the attempt.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * The payment receipt created upon successful verification.
     */
    public function paymentReceipt(): BelongsTo
    {
        return $this->belongsTo(PaymentReceipt::class, 'payment_receipt_id');
    }
}
