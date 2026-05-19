<?php

declare(strict_types=1);

namespace App\Modules\Customer\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Core\Audit\Traits\LogsActivity;
use App\Modules\Customer\Enums\ContactType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerContact extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $table = 'customer_contacts';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'full_name',
        'designation',
        'phone',
        'alternate_phone',
        'email',
        'type',
        'notes',
        'is_primary',
    ];

    protected $casts = [
        'type' => ContactType::class,
        'is_primary' => 'boolean',
    ];

    /**
     * Get the customer that owns the contact.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
