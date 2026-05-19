<?php

declare(strict_types=1);

namespace App\Core\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantManager = app(TenantManager::class);

        if ($tenantManager->isBypassed()) {
            return;
        }

        if ($tenantManager->hasTenant()) {
            $builder->where($model->getTable() . '.tenant_id', $tenantManager->getTenantId());
        } elseif (!app()->runningInConsole()) {
            // If the user is authenticated as a global superadmin, allow bypassing the scope
            $user = auth()->user();
            if ($user && ($user instanceof \App\Models\User || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()))) {
                return;
            }

            // Fail-safe query boundary: if no tenant context is resolved in an HTTP request, force an empty result set
            $builder->whereRaw('1 = 0');
        }
    }
}

