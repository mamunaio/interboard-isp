<?php

declare(strict_types=1);

namespace App\Modules\Network\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pop extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pops';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Get the distribution boxes for the POP.
     *
     * @return HasMany<DistributionBox, $this>
     */
    public function distributionBoxes(): HasMany
    {
        return $this->hasMany(DistributionBox::class, 'pop_id');
    }
}
