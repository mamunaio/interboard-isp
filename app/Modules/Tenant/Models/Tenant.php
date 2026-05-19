<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    protected $fillable = [
        'uuid',
        'slug',
        'name',
        'status',
        'plan_id',
        'trial_ends_at',
        'suspended_at',
        'suspended_reason',
        'timezone',
        'currency',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'suspended_at' => 'datetime',
    ];

    /**
     * Get the domains owned by this tenant.
     */
    public function domains(): HasMany
    {
        return $this->hasMany(TenantDomain::class);
    }

    /**
     * Get the primary domain of this tenant.
     */
    public function primaryDomain(): HasOne
    {
        return $this->hasOne(TenantDomain::class)->where('is_primary', true);
    }

    /**
     * Get the branding details for this tenant.
     */
    public function branding(): HasOne
    {
        return $this->hasOne(TenantBranding::class);
    }

    /**
     * Get the current subscription plan of this tenant.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
