<?php

declare(strict_types=1);

namespace App\Modules\Support\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Models\User;
use App\Modules\Customer\Models\Customer;
use App\Modules\Support\Enums\TicketCategory;
use App\Modules\Support\Enums\TicketPriority;
use App\Modules\Support\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use BelongsToTenant;

    protected $table = 'tickets';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'assigned_to_id',
        'work_order_id',
        'title',
        'category',
        'status',
        'priority',
        'sla_due_at',
        'resolved_at',
    ];

    protected $casts = [
        'category' => TicketCategory::class,
        'status' => TicketStatus::class,
        'priority' => TicketPriority::class,
        'sla_due_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->orderBy('created_at', 'asc');
    }
}
