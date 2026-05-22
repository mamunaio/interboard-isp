<?php

declare(strict_types=1);

namespace App\Modules\Notification\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Modules\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use BelongsToTenant;

    protected $table = 'notification_logs';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'channel',
        'recipient',
        'message',
        'status',
        'error_message',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
