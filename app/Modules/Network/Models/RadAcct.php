<?php

declare(strict_types=1);

namespace App\Modules\Network\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadAcct extends Model
{
    use BelongsToTenant;

    protected $table = 'radacct';
    protected $primaryKey = 'radacctid';

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'acctsessionid',
        'acctuniqueid',
        'username',
        'groupname',
        'realm',
        'nasipaddress',
        'nasportid',
        'nasporttype',
        'acctstarttime',
        'acctupdatetime',
        'acctstoptime',
        'acctinterval',
        'acctsessiontime',
        'acctauthentic',
        'connectinfo_start',
        'connectinfo_stop',
        'acctinputoctets',
        'acctoutputoctets',
        'calledstationid',
        'callingstationid',
        'acctterminatecause',
        'servicetype',
        'framedprotocol',
        'framedipaddress',
    ];

    protected $casts = [
        'acctstarttime' => 'datetime',
        'acctupdatetime' => 'datetime',
        'acctstoptime' => 'datetime',
        'acctinterval' => 'integer',
        'acctsessiontime' => 'integer',
        'acctinputoctets' => 'integer',
        'acctoutputoctets' => 'integer',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(CustomerServiceSubscription::class, 'subscription_id');
    }
}
