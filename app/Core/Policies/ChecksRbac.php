<?php

declare(strict_types=1);

namespace App\Core\Policies;

use App\Modules\Auth\Models\StaffUser;
use App\Core\Services\PermissionCacheService;

trait ChecksRbac
{
    /**
     * Highly-optimized permission validator. 
     * Uses PermissionCacheService to store active user roles/permissions arrays 
     * to perform verification without repeating SQL queries.
     */
    protected function authorizeUser(StaffUser $user, string $permission): bool
    {
        // 1. Instantly block inactive users
        if ($user->status !== 'active') {
            return false;
        }

        // 2. Fetch cached permissions from service abstraction
        $cacheService = app(PermissionCacheService::class);
        $permissions = $cacheService->getCachedPermissions($user->tenant_id, $user->id);

        if (empty($permissions)) {
            // Cold cache: Load from database, cache in Redis, and return
            $permissions = [];
            
            // Load roles and their permissions
            $user->load('roles.permissions');
            foreach ($user->roles as $role) {
                // If it's a tenant owner or super-admin by slug, instantly allow all
                if ($role->slug === 'tenant-owner' || $role->slug === 'super-admin') {
                    $permissions = ['*'];
                    break;
                }
                
                foreach ($role->permissions as $perm) {
                    $permissions[] = $perm->slug;
                }
            }

            $permissions = array_unique($permissions);

            if (!empty($permissions)) {
                $cacheService->cachePermissions($user->tenant_id, $user->id, $permissions);
            }
        }

        // 3. Return evaluation
        if (in_array('*', $permissions, true)) {
            return true;
        }

        return in_array($permission, $permissions, true);
    }
}

