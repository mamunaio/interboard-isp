<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;

class TrustedDevice extends Model
{
    protected $table = 'trusted_devices';

    protected $fillable = [
        'user_type',
        'user_id',
        'device_hash',
        'ip_address',
        'user_agent',
        'last_active_at',
    ];

    protected $casts = [
        'last_active_at' => 'datetime',
    ];
}
