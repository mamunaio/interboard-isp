<?php

declare(strict_types=1);

namespace App\Modules\Network\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadCheck extends Model
{
    use BelongsToTenant;

    protected $table = 'radcheck';

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'username',
        'attribute',
        'op',
        'value',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(CustomerServiceSubscription::class, 'subscription_id');
    }
}
