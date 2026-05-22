<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Models\User;
use App\Modules\Customer\Models\ServicePlan;
use App\Modules\CRM\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use BelongsToTenant;

    protected $table = 'leads';

    protected $fillable = [
        'tenant_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'address',
        'status',
        'assigned_to_id',
        'expected_plan_id',
        'notes',
        'is_converted',
    ];

    protected $casts = [
        'status' => LeadStatus::class,
        'is_converted' => 'boolean',
    ];

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function expectedPlan(): BelongsTo
    {
        return $this->belongsTo(ServicePlan::class, 'expected_plan_id');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(LeadInteraction::class)->orderBy('created_at', 'asc');
    }
}
