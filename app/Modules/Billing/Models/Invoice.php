<?php

declare(strict_types=1);

namespace App\Modules\Billing\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Core\Audit\Traits\LogsActivity;
use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Billing\Enums\InvoiceType;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $table = 'invoices';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'customer_service_subscription_id',
        'invoice_number',
        'invoice_type',
        'issue_date',
        'due_date',
        'currency',
        'subtotal',
        'tax_total',
        'discount_total',
        'grand_total',
        'amount_paid',
        'amount_due',
        'billing_period_start',
        'billing_period_end',
        'status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'status' => InvoiceStatus::class,
        'invoice_type' => InvoiceType::class,
        'issue_date' => 'date',
        'due_date' => 'date',
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'subtotal' => 'decimal:4',
        'tax_total' => 'decimal:4',
        'discount_total' => 'decimal:4',
        'grand_total' => 'decimal:4',
        'amount_paid' => 'decimal:4',
        'amount_due' => 'decimal:4',
        'metadata' => 'array',
    ];

    /**
     * Get the customer that owns the invoice.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the subscription that owns the invoice.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(CustomerServiceSubscription::class, 'customer_service_subscription_id');
    }

    /**
     * Get the line items for the invoice.
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class);
    }

    /**
     * Get the payment allocations applied to this invoice.
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class, 'invoice_id');
    }
}
