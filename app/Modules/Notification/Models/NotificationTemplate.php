<?php

declare(strict_types=1);

namespace App\Modules\Notification\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use BelongsToTenant;

    protected $table = 'notification_templates';

    protected $fillable = [
        'tenant_id',
        'event_name',
        'channel',
        'subject',
        'body',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
