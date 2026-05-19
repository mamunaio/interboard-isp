<?php

declare(strict_types=1);

namespace App\Modules\Billing\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Core\Audit\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAllocation extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $table = 'payment_allocations';

    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'wallet_id',
        'ledger_entry_id',
        'payment_receipt_id',
        'allocation_amount',
        'allocation_order',
        'idempotency_key',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'allocation_amount' => 'decimal:4',
        'allocation_order' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the net allocation amount after subtracting all reversals.
     */
    public function getNetAmountAttribute()
    {
        $reversalsSum = $this->reversals()->sum('amount') ?? '0.0000';
        return bcsub((string)$this->allocation_amount, (string)$reversalsSum, 4);
    }

    /**
     * Check if the allocation is fully reversed.
     */
    public function isReversed(): bool
    {
        return bccomp($this->net_amount, '0.0000', 4) <= 0;
    }

    /**
     * The reversals associated with the allocation.
     */
    public function reversals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PaymentAllocationReversal::class, 'payment_allocation_id');
    }

    /**
     * The receipt associated with the allocation.
     */
    public function receipt(): BelongsTo
    {
        return $this->belongsTo(PaymentReceipt::class, 'payment_receipt_id');
    }

    /**
     * The invoice associated with the allocation.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    /**
     * The wallet that funded the allocation.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    /**
     * The wallet ledger entry associated with the allocation debit.
     */
    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class, 'ledger_entry_id');
    }
}
