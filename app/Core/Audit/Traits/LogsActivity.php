<?php

declare(strict_types=1);

namespace App\Core\Audit\Traits;

use App\Core\Audit\Jobs\ProcessAuditLogJob;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    /**
     * Boot the LogsActivity trait for the model.
     */
    public static function bootLogsActivity(): void
    {
        static::created(fn (Model $model) => static::queueAuditLog($model, 'created'));
        static::updated(fn (Model $model) => static::queueAuditLog($model, 'updated'));
        static::deleted(fn (Model $model) => static::queueAuditLog($model, 'deleted'));
    }

    /**
     * Package active model mutations and dispatch a queued log processing job.
     */
    protected static function queueAuditLog(Model $model, string $event): void
    {
        // 1. Gather old and new attributes
        $oldValues = null;
        $newValues = null;

        if ($event === 'created') {
            $newValues = $model->getAttributes();
            // Exclude sensitive fields
            unset($newValues['password'], $newValues['remember_token'], $newValues['two_factor_secret']);
        } elseif ($event === 'updated') {
            $newValues = $model->getChanges();
            $oldValues = array_intersect_key($model->getRawOriginal(), $newValues);
            
            unset($newValues['password'], $newValues['remember_token'], $newValues['two_factor_secret']);
            unset($oldValues['password'], $oldValues['remember_token'], $oldValues['two_factor_secret']);

            // If no actual changes recorded, exit
            if (empty($newValues)) {
                return;
            }
        } elseif ($event === 'deleted') {
            $oldValues = $model->getRawOriginal();
            unset($oldValues['password'], $oldValues['remember_token'], $oldValues['two_factor_secret']);
        }

        // 2. Resolve request contexts
        $ipAddress = request()->ip();
        $userAgent = request()->userAgent();
        $user = auth()->user();

        $userId = $user?->id;
        $userType = $user ? get_class($user) : null;

        // 3. Dispatch to Queue
        ProcessAuditLogJob::dispatch([
            'tenant_id' => $model->tenant_id ?? app(\App\Core\Tenant\TenantManager::class)->getTenantId(),
            'user_type' => $userType,
            'user_id' => $userId,
            'event' => $event,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }
}
