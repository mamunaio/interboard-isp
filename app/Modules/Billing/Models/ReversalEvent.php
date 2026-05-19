<?php

declare(strict_types=1);

namespace App\Modules\Billing\Models;

use App\Core\Audit\Traits\LogsActivity;
use App\Core\Tenant\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReversalEvent extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $table = 'reversal_events';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'original_entity_type',
        'original_entity_id',
        'reversal_type',
        'amount',
        'currency',
        'reason',
        'reversal_ledger_entry_id',
        'idempotency_key',
        'created_by_type',
        'created_by_id',
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
            throw new \RuntimeException('Reversal events are strictly immutable.');
        });

        static::deleting(function (self $model) {
            throw new \RuntimeException('Reversal events are strictly immutable.');
        });
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class, 'reversal_ledger_entry_id');
    }
}
