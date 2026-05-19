<?php

declare(strict_types=1);

namespace App\Core\Tenant;

use App\Modules\Tenant\Models\Tenant;

class TenantManager
{
    /**
     * The active resolved tenant context.
     */
    private ?Tenant $currentTenant = null;

    /**
     * Whether the tenant scope should be temporarily bypassed (e.g. for superadmins or system jobs).
     */
    private bool $bypassed = false;

    /**
     * Set the current active tenant.
     */
    public function setTenant(?Tenant $tenant): void
    {
        $this->currentTenant = $tenant;
    }


    /**
     * Get the current active tenant.
     */
    public function getTenant(): ?Tenant
    {
        return $this->currentTenant;
    }

    /**
     * Get the current active tenant ID.
     */
    public function getTenantId(): ?int
    {
        return $this->currentTenant?->id;
    }

    /**
     * Check if a tenant is currently resolved.
     */
    public function hasTenant(): bool
    {
        return $this->currentTenant !== null;
    }

    /**
     * Determine if the tenant scope is bypassed.
     */
    public function isBypassed(): bool
    {
        return $this->bypassed;
    }

    /**
     * Enable or disable the tenant scope bypass.
     */
    public function setBypassed(bool $bypassed): void
    {
        $this->bypassed = $bypassed;
    }

    /**
     * Execute a callback with the tenant scope temporarily bypassed.
     */
    public function withoutTenantScope(callable $callback)
    {
        $previous = $this->bypassed;
        $this->bypassed = true;
        try {
            return $callback();
        } finally {
            $this->bypassed = $previous;
        }
    }
}

