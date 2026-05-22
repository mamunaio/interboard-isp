<?php

declare(strict_types=1);

namespace App\Modules\Support\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Models\User;
use App\Modules\Customer\Models\Customer;
use App\Modules\Support\Enums\WorkOrderPriority;
use App\Modules\Support\Enums\WorkOrderStatus;
use App\Modules\Support\Enums\WorkOrderType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrder extends Model
{
    use BelongsToTenant;

    protected $table = 'work_orders';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'assigned_to',
        'type',
        'status',
        'priority',
        'description',
        'resolution_notes',
        'scheduled_date',
        'completed_at',
    ];

    protected $casts = [
        'type' => WorkOrderType::class,
        'status' => WorkOrderStatus::class,
        'priority' => WorkOrderPriority::class,
        'scheduled_date' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
