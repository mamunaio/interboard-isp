<?php

declare(strict_types=1);

namespace App\Modules\Billing\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Core\Audit\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLineItem extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $table = 'invoice_line_items';

    protected $fillable = [
        'invoice_id',
        'tenant_id',
        'line_type',
        'description',
        'quantity',
        'unit_price',
        'line_subtotal',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'line_subtotal' => 'decimal:4',
        'metadata' => 'array',
    ];

    /**
     * Get the invoice that owns the line item.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
