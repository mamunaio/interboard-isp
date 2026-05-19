<?php

declare(strict_types=1);

namespace App\Modules\Billing\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Core\Audit\Traits\LogsActivity;
use App\Modules\Billing\Enums\TransactionType;
use App\Modules\Billing\Enums\TransactionDirection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LedgerEntry extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $table = 'ledger_entries';

    protected $fillable = [
        'tenant_id',
        'wallet_id',
        'transaction_type',
        'direction',
        'amount',
        'balance_before',
        'balance_after',
        'reference_type',
        'reference_id',
        'idempotency_key',
        'notes',
        'metadata',
        'created_by_type',
        'created_by_id',
    ];

    protected $casts = [
        'transaction_type' => TransactionType::class,
        'direction' => TransactionDirection::class,
        'amount' => 'decimal:4',
        'balance_before' => 'decimal:4',
        'balance_after' => 'decimal:4',
        'metadata' => 'array',
    ];

    /**
     * Parent wallet of this ledger entry.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    /**
     * Polymorphic reference associated with the ledger entry.
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Polymorphic actor who initiated the ledger transaction.
     */
    public function createdBy(): MorphTo
    {
        return $this->morphTo('created_by');
    }
}
