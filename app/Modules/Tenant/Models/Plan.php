<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price_monthly',
        'price_yearly',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
    ];

    /**
     * Get features of this plan.
     */
    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    /**
     * Get the tenants currently on this plan.
     */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }
}
