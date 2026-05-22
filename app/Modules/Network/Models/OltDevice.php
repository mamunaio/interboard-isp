<?php

declare(strict_types=1);

namespace App\Modules\Network\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Core\Audit\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OltDevice extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $table = 'olt_devices';

    protected $fillable = [
        'tenant_id',
        'name',
        'ip_address',
        'port',
        'username',
        'password',
        'vendor',
        'status',
    ];

    /**
     * Get the ONUs registered under this OLT device.
     */
    public function onus(): HasMany
    {
        return $this->hasMany(OnuDevice::class, 'olt_device_id');
    }
}
