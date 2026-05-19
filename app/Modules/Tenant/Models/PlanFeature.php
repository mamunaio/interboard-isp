<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanFeature extends Model
{
    protected $fillable = [
        'plan_id',
        'feature_key',
        'feature_value',
    ];

    /**
     * Get the plan that owns this feature.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
