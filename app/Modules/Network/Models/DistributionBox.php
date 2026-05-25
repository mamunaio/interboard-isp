<?php

declare(strict_types=1);

namespace App\Modules\Network\Models;

use App\Modules\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DistributionBox extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'distribution_boxes';

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
        'port_capacity' => 'integer',
    ];

    /**
     * Get the POP that owns the distribution box.
     *
     * @return BelongsTo<Pop, $this>
     */
    public function pop(): BelongsTo
    {
        return $this->belongsTo(Pop::class, 'pop_id');
    }

    /**
     * Get the customers connected to this distribution box.
     *
     * @return HasMany<Customer, $this>
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'distribution_box_id');
    }
}
