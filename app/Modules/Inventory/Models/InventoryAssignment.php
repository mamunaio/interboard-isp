<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryAssignment extends Model
{
    protected $table = 'inventory_assignments';

    protected $fillable = [
        'inventory_serial_id',
        'assigned_to_type',
        'assigned_to_id',
        'assigned_at',
        'returned_at',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function serial(): BelongsTo
    {
        return $this->belongsTo(InventorySerial::class, 'inventory_serial_id');
    }

    public function assignee(): MorphTo
    {
        return $this->morphTo('assigned_to');
    }
}
