<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Customer\Models\Customer;
use App\Modules\Auth\Models\StaffUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledAction extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'scheduled_actions';

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
        'target_date' => 'date',
        'payload' => 'array',
    ];

    /**
     * Get the customer associated with the scheduled action.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the staff user who created the scheduled action.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(StaffUser::class, 'created_by');
    }
}
