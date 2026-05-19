<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use BelongsToTenant;

    protected $table = 'audit_logs';

    protected $fillable = [
        'tenant_id',
        'user_type',
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Get the tenant associated with this audit log.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the owning auditable model.
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who performed this action.
     */
    public function user(): MorphTo
    {
        return $this->morphTo();
    }
}
