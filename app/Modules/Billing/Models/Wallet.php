<?php

declare(strict_types=1);

namespace App\Modules\Billing\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Core\Audit\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $table = 'wallets';

    protected $fillable = [
        'tenant_id',
        'owner_type',
        'owner_id',
        'currency',
        'current_balance',
        'active',
        'metadata',
    ];

    protected $casts = [
        'current_balance' => 'decimal:4',
        'active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Polymorphic owner of the wallet.
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Ledger entries associated with the wallet.
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class, 'wallet_id');
    }
}
