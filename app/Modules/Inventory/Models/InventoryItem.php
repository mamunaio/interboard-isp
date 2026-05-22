<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Modules\Inventory\Enums\InventoryCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use BelongsToTenant;

    protected $table = 'inventory_items';

    protected $fillable = [
        'tenant_id',
        'name',
        'sku',
        'category',
        'track_serial',
        'low_stock_threshold',
        'current_stock',
    ];

    protected $casts = [
        'category' => InventoryCategory::class,
        'track_serial' => 'boolean',
    ];

    public function serials(): HasMany
    {
        return $this->hasMany(InventorySerial::class);
    }
}
