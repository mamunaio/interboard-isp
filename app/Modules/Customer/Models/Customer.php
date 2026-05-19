<?php

declare(strict_types=1);

namespace App\Modules\Customer\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Core\Audit\Traits\LogsActivity;
use App\Modules\Customer\Enums\CustomerType;
use App\Modules\Customer\Enums\CustomerStatus;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $table = 'customers';

    protected $fillable = [
        'tenant_id',
        'customer_code',
        'type',
        'full_name',
        'company_name',
        'phone',
        'alternate_phone',
        'email',
        'national_id',
        'national_id_hash',
        'business_registration',
        'business_registration_hash',
        'date_of_birth',
        'notes',
        'tags',
        'referral_source',
        'status',
    ];

    protected $casts = [
        'type' => CustomerType::class,
        'status' => CustomerStatus::class,
        'tags' => 'array',
        'national_id' => 'encrypted',
        'business_registration' => 'encrypted',
        'date_of_birth' => 'encrypted',
    ];

    /**
     * Get the addresses for the customer.
     */
    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /**
     * Get the contacts for the customer.
     */
    public function contacts()
    {
        return $this->hasMany(CustomerContact::class);
    }

    /**
     * Get the KYC documents for the customer.
     */
    public function documents()
    {
        return $this->hasMany(CustomerDocument::class);
    }

    /**
     * Get the contracts for the customer.
     */
    public function contracts()
    {
        return $this->hasMany(CustomerContract::class);
    }

    /**
     * Get the subscriptions for the customer.
     */
    public function subscriptions()
    {
        return $this->hasMany(CustomerServiceSubscription::class);
    }
}




