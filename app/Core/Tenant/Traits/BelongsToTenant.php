<?php

declare(strict_types=1);

namespace App\Core\Tenant\Traits;

use App\Core\Tenant\TenantManager;
use App\Core\Tenant\TenantScope;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    /**
     * Boot the BelongsToTenant trait for the model.
     */
    public static function bootBelongsToTenant(): void
    {
        // Automatically inject current tenant_id when creating new models
        static::creating(function (Model $model) {
            if (empty($model->tenant_id)) {
                $tenantManager = app(TenantManager::class);
                if ($tenantManager->hasTenant()) {
                    $model->tenant_id = $tenantManager->getTenantId();
                } elseif (!app()->runningInConsole()) {
                    // Safe guard: throw an exception to prevent saving a tenant-scoped record without a tenant context
                    throw new \RuntimeException("Accidental tenant scope omission: Attempted to create a tenant-scoped model [" . get_class($model) . "] without a resolved active tenant context.");
                }
            }
        });

        // Apply global tenant scope for all queries
        static::addGlobalScope(new TenantScope());
    }

    /**
     * Get the tenant that owns this model.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
