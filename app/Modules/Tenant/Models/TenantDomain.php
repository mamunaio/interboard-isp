<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantDomain extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'tenant_id',
        'domain',
        'is_primary',
        'ssl_status',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    /**
     * Get the tenant that owns this domain.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
