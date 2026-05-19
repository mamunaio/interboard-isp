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
