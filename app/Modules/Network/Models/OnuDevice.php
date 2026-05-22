<?php

declare(strict_types=1);

namespace App\Modules\Network\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Core\Audit\Traits\LogsActivity;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnuDevice extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $table = 'onu_devices';

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'olt_device_id',
        'serial_number',
        'gpon_port',
        'onu_id',
        'line_profile',
        'service_profile',
        'status',
    ];

    /**
     * Get the OLT device this ONU belongs to.
     */
    public function oltDevice(): BelongsTo
    {
        return $this->belongsTo(OltDevice::class, 'olt_device_id');
    }

    /**
     * Get the subscription associated with this ONU.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(CustomerServiceSubscription::class, 'subscription_id');
    }
}
