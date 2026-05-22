<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Modules\Inventory\Enums\InventoryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventorySerial extends Model
{
    protected $table = 'inventory_serials';

    protected $fillable = [
        'inventory_item_id',
        'serial_number',
        'mac_address',
        'status',
    ];

    protected $casts = [
        'status' => InventoryStatus::class,
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(InventoryAssignment::class);
    }
}
