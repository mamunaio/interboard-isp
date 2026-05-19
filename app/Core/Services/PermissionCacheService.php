<?php

declare(strict_types=1);

namespace App\Core\Services;

use Illuminate\Support\Facades\Redis;

class PermissionCacheService
{
    /**
     * Retrieve cached permissions for a user from Redis.
     */
    public function getCachedPermissions(int $tenantId, int $userId): array
    {
        $cacheKey = $this->getCacheKey($tenantId, $userId);
        
        try {
            return Redis::smembers($cacheKey) ?: [];
        } catch (\Throwable $e) {
            // Graceful fallback if Redis is down
            return [];
        }
    }

    /**
     * Store user permissions in Redis cache.
     */
    public function cachePermissions(int $tenantId, int $userId, array $permissions): void
    {
        if (empty($permissions)) {
            return;
        }

        $cacheKey = $this->getCacheKey($tenantId, $userId);

        try {
            Redis::sadd($cacheKey, ...$permissions);
            Redis::expire($cacheKey, 3600); // 1-hour sliding expiration
        } catch (\Throwable $e) {
            // Graceful fallback if Redis is down
        }
    }

    /**
     * Clear cached permissions for a user (e.g. after role or permission update).
     */
    public function clearCachedPermissions(int $tenantId, int $userId): void
    {
        $cacheKey = $this->getCacheKey($tenantId, $userId);

        try {
            Redis::del($cacheKey);
        } catch (\Throwable $e) {
            // Graceful fallback if Redis is down
        }
    }

    /**
     * Build the standard permission cache key.
     */
    protected function getCacheKey(int $tenantId, int $userId): string
    {
        return "tenant:{$tenantId}:user:{$userId}:permissions";
    }
}
