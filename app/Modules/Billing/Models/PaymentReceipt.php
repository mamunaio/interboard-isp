<?php

declare(strict_types=1);

namespace App\Modules\Billing\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Modules\Billing\Enums\PaymentMethod;
use App\Modules\Billing\Enums\ReceiptStatus;
use App\Modules\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReceipt extends Model
{
    use BelongsToTenant;

    protected $table = 'payment_receipts';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'receipt_number',
        'payment_method',
        'amount',
        'currency',
        'reference',
        'notes',
        'status',
        'wallet_ledger_entry_id',
        'created_by_type',
        'created_by_id',
    ];

    protected $casts = [
        'payment_method' => PaymentMethod::class,
        'status' => ReceiptStatus::class,
        'amount' => 'decimal:4',
        'created_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        // Populate fresh timestamp
        static::creating(function (self $model) {
            $model->created_at = $model->freshTimestamp();
        });

        // Enforce strict model level immutability (No updates, no deletes)
        static::updating(function (self $model) {
            throw new \RuntimeException('Payment receipts are strictly immutable and cannot be updated.');
        });

        static::deleting(function (self $model) {
            throw new \RuntimeException('Payment receipts are strictly immutable and cannot be deleted.');
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class, 'wallet_ledger_entry_id');
    }
}
